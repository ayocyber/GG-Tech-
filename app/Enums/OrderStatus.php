<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Confirmed => __('Confirmed'),
            self::Ready => __('Ready'),
            self::Delivered => __('Delivered'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Confirmed => 'primary',
            self::Ready => 'warning',
            self::Delivered => 'success',
            self::Cancelled => 'gray',
        };
    }
}
