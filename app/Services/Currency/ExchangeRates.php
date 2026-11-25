<?php

namespace App\Services\Currency;

use App\Models\Currency;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Illuminate\Support\defer;

/**
 * Keeps the stored automatic rates up to date. Visitors never wait for the provider:
 * stale rates are refreshed after the response is sent, and when the provider is down
 * the last saved rates stay in use.
 */
class ExchangeRates
{
    private const RefreshLock = 'exchange-rates:refreshing';

    /**
     * @param  string|null  $providerUrl  empty turns automatic updates off (the test suite does this)
     */
    public function __construct(
        private ?string $providerUrl,
        private int $staleAfterHours,
    ) {}

    /**
     * Fetches fresh rates for every stored currency that has an automatic source.
     *
     * @return bool false when the provider could not be reached; stored rates are then left as they were
     */
    public function refresh(): bool
    {
        $currencies = Currency::query()->get();

        foreach ($currencies->filter(fn (Currency $currency) => array_key_exists($currency->code, Currency::PeggedRates)) as $currency) {
            $currency->update([
                'auto_rate' => Currency::PeggedRates[$currency->code],
                'auto_source' => Currency::SourcePeg,
                'auto_updated_at' => now(),
            ]);
        }

        $fromProvider = $currencies->filter(fn (Currency $currency) => in_array($currency->code, Currency::ProviderCurrencies, true));

        if ($fromProvider->isEmpty()) {
            return true;
        }

        $ratesPerEuro = $this->fetchRatesPerEuro($fromProvider->pluck('code')->all());

        if ($ratesPerEuro === null) {
            return false;
        }

        foreach ($fromProvider as $currency) {
            $perEuro = (float) ($ratesPerEuro[$currency->code] ?? 0);

            if ($perEuro > 0) {
                $currency->update([
                    'auto_rate' => round(Currency::PeggedRates['EUR'] / $perEuro, 8),
                    'auto_source' => Currency::SourceProvider,
                    'auto_updated_at' => now(),
                ]);
            }
        }

        return true;
    }

    /**
     * Schedules a refresh after the response when an automatic rate is older than allowed.
     */
    public function refreshIfStale(): void
    {
        if (blank($this->providerUrl) || ! $this->hasStaleRates() || ! Cache::add(self::RefreshLock, true, now()->addMinutes(10))) {
            return;
        }

        defer(fn () => $this->refresh());
    }

    public function hasStaleRates(): bool
    {
        return Currency::query()
            ->whereIn('code', Currency::ProviderCurrencies)
            ->where('is_enabled', true)
            ->where(fn ($query) => $query
                ->whereNull('auto_updated_at')
                ->orWhere('auto_updated_at', '<', now()->subHours($this->staleAfterHours)))
            ->exists();
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, float>|null
     */
    private function fetchRatesPerEuro(array $codes): ?array
    {
        if (blank($this->providerUrl)) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->get(rtrim($this->providerUrl, '/').'/latest', ['base' => 'EUR', 'symbols' => implode(',', $codes)]);
        } catch (ConnectionException $exception) {
            Log::warning('Exchange rates provider unreachable; keeping the saved rates.', ['error' => $exception->getMessage()]);

            return null;
        }

        $rates = $response->json('rates');

        if ($response->failed() || ! is_array($rates)) {
            Log::warning('Exchange rates provider returned no rates; keeping the saved rates.', ['status' => $response->status()]);

            return null;
        }

        return $rates;
    }
}
