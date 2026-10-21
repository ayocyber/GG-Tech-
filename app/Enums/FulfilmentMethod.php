<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FulfilmentMethod: string implements HasLabel
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function getLabel(): string
    {
        return match ($this) {
            self::Delivery => __('Delivery'),
            self::Pickup => __('In-store pickup'),
        };
    }
}
