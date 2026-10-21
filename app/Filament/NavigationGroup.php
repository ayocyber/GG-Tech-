<?php

namespace App\Filament;

use Filament\Support\Contracts\HasLabel;

/**
 * Admin sidebar groups, in display order.
 */
enum NavigationGroup implements HasLabel
{
    case Sales;
    case Catalogue;
    case Settings;

    public function getLabel(): string
    {
        return match ($this) {
            self::Sales => __('Sales'),
            self::Catalogue => __('Catalogue'),
            self::Settings => __('Settings'),
        };
    }
}
