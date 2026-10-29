<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * GG-TECH's physical stores. Their addresses and links are edited in the admin (Store settings).
 */
enum Shop: string implements HasLabel
{
    case HeadOffice = 'head_office';
    case SecondStore = 'second_store';

    public function getLabel(): string
    {
        return match ($this) {
            self::HeadOffice => __('Head office'),
            self::SecondStore => __('Second store'),
        };
    }
}
