<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuoteStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => __('New'),
            self::Sent => __('Quote sent'),
            self::Accepted => __('Accepted'),
            self::Declined => __('Declined'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Sent => 'warning',
            self::Accepted => 'success',
            self::Declined => 'gray',
        };
    }
}
