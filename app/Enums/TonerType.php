<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TonerType: string implements HasColor, HasLabel
{
    case Original = 'original';
    case PremiumCompatible = 'premium_compatible';

    public function getLabel(): string
    {
        return match ($this) {
            self::Original => __('Original'),
            self::PremiumCompatible => __('Premium compatible'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Original => 'info',
            self::PremiumCompatible => 'success',
        };
    }

    /**
     * Short explanation shown to customers on the product page.
     */
    public function explanation(): string
    {
        return match ($this) {
            self::Original => __('Made by the printer manufacturer, in its original packaging.'),
            self::PremiumCompatible => __('Made by a third-party manufacturer to the same specifications, at a lower price. Tested by our team.'),
        };
    }

    public function other(): self
    {
        return $this === self::Original ? self::PremiumCompatible : self::Original;
    }

    /**
     * Ending added to a toner's slug, e.g. "hp-26a-black-toner-cartridge-premium-compatible".
     */
    public function slugSuffix(): string
    {
        return '-'.str_replace('_', '-', $this->value);
    }

    /**
     * The storefront category that lists this type of toner.
     */
    public function categorySlug(): string
    {
        return match ($this) {
            self::Original => 'original-toners',
            self::PremiumCompatible => 'compatible-toners',
        };
    }
}
