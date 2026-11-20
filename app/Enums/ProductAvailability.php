<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a product may be shipped: within Togo only, or also to the countries GG-TECH delivers to abroad.
 */
enum ProductAvailability: string implements HasColor, HasLabel
{
    case TogoOnly = 'togo_only';
    case International = 'international';

    public function getLabel(): string
    {
        return match ($this) {
            self::TogoOnly => __('Togo only'),
            self::International => __('International'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::TogoOnly => 'gray',
            self::International => 'info',
        };
    }

    public function shipsAbroad(): bool
    {
        return $this === self::International;
    }
}
