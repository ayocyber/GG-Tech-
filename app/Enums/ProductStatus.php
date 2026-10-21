<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Published => __('Published'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
        };
    }
}
