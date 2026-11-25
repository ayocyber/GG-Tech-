<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FulfilmentMethod: string implements HasLabel
{
    case Delivery = 'delivery';
    case International = 'international';
    case Pickup = 'pickup';

    public function getLabel(): string
    {
        return match ($this) {
            self::Delivery => __('Delivery in Togo'),
            self::International => __('International delivery'),
            self::Pickup => __('In-store pickup'),
        };
    }

    /**
     * Whether the order is sent to the customer's address, in Togo or abroad.
     */
    public function isDelivery(): bool
    {
        return $this !== self::Pickup;
    }
}
