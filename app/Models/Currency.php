<?php

namespace App\Models;

use App\Services\Currency\DisplayCurrency;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use ResourceBundle;

/**
 * A currency visitors may see prices in. Prices are always stored and charged in FCFA;
 * the rate says how many FCFA one unit of this currency is worth.
 */
#[Fillable(['code', 'auto_rate', 'auto_source', 'auto_updated_at', 'manual_rate', 'manual_updated_at', 'is_enabled', 'position'])]
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * Currencies tied to the FCFA by a fixed rate (BCEAO / BEAC), in FCFA per unit.
     */
    public const PeggedRates = [
        'EUR' => 655.957,
        'XAF' => 1.0,
    ];

    /**
     * Currencies the automatic rate provider (European Central Bank via Frankfurter) publishes.
     */
    public const ProviderCurrencies = [
        'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'GBP', 'HKD', 'HUF', 'IDR', 'ILS', 'INR', 'ISK',
        'JPY', 'KRW', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'SEK', 'SGD', 'THB', 'TRY', 'USD', 'ZAR',
    ];

    /**
     * Currencies with no automatic source: staff enter their rate by hand.
     */
    public const ManualOnlyCurrencies = ['GHS', 'NGN', 'MAD'];

    public const SourcePeg = 'peg';

    public const SourceProvider = 'ecb';

    /**
     * The display currency keeps the list for the request, so a change from the admin must clear it.
     */
    protected static function booted(): void
    {
        static::saved(fn () => app()->forgetInstance(DisplayCurrency::class));
        static::deleted(fn () => app()->forgetInstance(DisplayCurrency::class));
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where('is_enabled', true)->orderBy('position')->orderBy('id');
    }

    /**
     * Every currency staff may add, keyed by code, with its name in the current language.
     *
     * @return array<string, string>
     */
    public static function supported(): array
    {
        $codes = [...array_keys(self::PeggedRates), ...self::ProviderCurrencies, ...self::ManualOnlyCurrencies];
        sort($codes);

        return collect($codes)->mapWithKeys(fn (string $code) => [$code => $code.' — '.self::nameFor($code)])->all();
    }

    public static function nameFor(string $code, ?string $locale = null): string
    {
        $name = ResourceBundle::create($locale ?? app()->getLocale(), 'ICUDATA-curr')?->get('Currencies')?->get($code)?->get(1);

        return is_string($name) ? mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1) : $code;
    }

    public static function hasAutomaticSource(string $code): bool
    {
        return array_key_exists($code, self::PeggedRates) || in_array($code, self::ProviderCurrencies, true);
    }

    public function name(?string $locale = null): string
    {
        return self::nameFor($this->code, $locale);
    }

    /**
     * The rate the website uses: the staff override when there is one, otherwise the automatic rate.
     */
    public function rate(): ?float
    {
        $rate = $this->manual_rate ?? $this->auto_rate;

        return $rate === null ? null : (float) $rate;
    }

    public function isManual(): bool
    {
        return $this->manual_rate !== null;
    }

    public function isUsable(): bool
    {
        return $this->is_enabled && ($this->rate() ?? 0) > 0;
    }

    public function rateUpdatedAt(): ?Carbon
    {
        return $this->isManual() ? $this->manual_updated_at : $this->auto_updated_at;
    }

    protected function casts(): array
    {
        return [
            'auto_rate' => 'decimal:8',
            'manual_rate' => 'decimal:8',
            'auto_updated_at' => 'datetime',
            'manual_updated_at' => 'datetime',
            'is_enabled' => 'boolean',
            'position' => 'integer',
        ];
    }
}
