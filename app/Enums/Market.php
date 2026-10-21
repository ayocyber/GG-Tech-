<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The country the shop sells in. GG-TECH sells in Togo only, in FCFA.
 */
enum Market: string implements HasLabel
{
    case Togo = 'tg';

    public static function current(): self
    {
        return self::Togo;
    }

    public function currency(): string
    {
        return 'XOF';
    }

    public function phonePrefix(): string
    {
        return '+228';
    }

    public function getLabel(): string
    {
        return __('Togo');
    }

    /**
     * Format a whole-unit amount the way customers expect it, e.g. "125 000 FCFA".
     */
    public function format(int $amount): string
    {
        return number_format($amount, 0, ',', "\u{202F}").' FCFA';
    }
}
