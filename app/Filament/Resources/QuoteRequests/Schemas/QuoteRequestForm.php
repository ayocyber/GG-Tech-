<?php

namespace App\Filament\Resources\QuoteRequests\Schemas;

use App\Enums\QuoteStatus;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuoteRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Quote follow-up'))
                    ->columnSpanFull()
                    ->schema([
                        Select::make('status')
                            ->label(__('Status'))
                            ->options(QuoteStatus::class)
                            ->required(),
                    ]),
            ]);
    }
}
