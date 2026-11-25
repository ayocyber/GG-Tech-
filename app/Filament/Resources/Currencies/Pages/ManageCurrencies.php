<?php

namespace App\Filament\Resources\Currencies\Pages;

use App\Filament\Resources\Currencies\CurrencyResource;
use App\Services\Currency\ExchangeRates;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCurrencies extends ManageRecords
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CurrencyResource::refreshAction(),
            CreateAction::make()
                ->mutateDataUsing(fn (array $data) => CurrencyResource::stampManualRate($data))
                ->after(fn (ExchangeRates $rates) => $rates->refresh()),
        ];
    }
}
