<?php

namespace App\Services\Currency;

use App\Enums\Market;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use NumberFormatter;

/**
 * The currency the visitor sees prices in. It only changes how amounts are shown:
 * prices are stored, ordered and charged in FCFA.
 */
class DisplayCurrency
{
    public const SessionKey = 'currency';

    public const CookieName = 'currency';

    public const Fcfa = 'XOF';

    /** @var Collection<int, Currency>|null */
    private ?Collection $available = null;

    /**
     * Enabled currencies that have a rate, in the order staff chose.
     *
     * @return Collection<int, Currency>
     */
    public function available(): Collection
    {
        return $this->available ??= Currency::query()->enabled()->get()
            ->filter(fn (Currency $currency) => $currency->isUsable())
            ->values();
    }

    /**
     * The codes a visitor may pick, FCFA first.
     *
     * @return list<string>
     */
    public function codes(): array
    {
        return [self::Fcfa, ...$this->available()->pluck('code')->all()];
    }

    public function isAvailable(string $code): bool
    {
        return in_array(strtoupper($code), $this->codes(), true);
    }

    /**
     * The chosen code, or FCFA when nothing was chosen or the chosen currency was switched off.
     */
    public function code(): string
    {
        $chosen = strtoupper((string) session(self::SessionKey));

        return $chosen !== '' && $this->isAvailable($chosen) ? $chosen : self::Fcfa;
    }

    public function isFcfa(): bool
    {
        return $this->code() === self::Fcfa;
    }

    public function hasChosen(): bool
    {
        return session()->has(self::SessionKey);
    }

    public function choose(string $code): void
    {
        session([self::SessionKey => strtoupper($code)]);
    }

    public function current(): ?Currency
    {
        return $this->available()->firstWhere('code', $this->code());
    }

    /**
     * Converts an FCFA amount, rounded the way the currency is usually written (cents for EUR, none for JPY).
     */
    public function convert(int $amountXof, ?string $code = null): float
    {
        $code ??= $this->code();
        $currency = $this->available()->firstWhere('code', $code);

        if ($code === self::Fcfa || $currency === null) {
            return (float) $amountXof;
        }

        return round($amountXof / $currency->rate(), $this->fractionDigits($code));
    }

    /**
     * An FCFA amount written in the visitor's currency, e.g. "68,60 €" or "€68.60".
     */
    public function format(int $amountXof): string
    {
        $code = $this->code();

        if ($code === self::Fcfa) {
            return Market::Togo->format($amountXof);
        }

        return $this->formatter()->formatCurrency($this->convert($amountXof, $code), $code);
    }

    public function label(string $code): string
    {
        return $code === self::Fcfa ? 'FCFA' : $code;
    }

    public function name(string $code): string
    {
        return $code === self::Fcfa ? __('CFA franc') : Currency::nameFor($code);
    }

    private function fractionDigits(string $code): int
    {
        $formatter = new NumberFormatter(app()->getLocale().'@currency='.$code, NumberFormatter::CURRENCY);

        return $formatter->getAttribute(NumberFormatter::FRACTION_DIGITS);
    }

    private function formatter(): NumberFormatter
    {
        return new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY);
    }
}
