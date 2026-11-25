<?php

namespace App\Filament\Resources\DeliveryCountries\Pages;

use App\Filament\Resources\DeliveryCountries\DeliveryCountryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDeliveryCountries extends ManageRecords
{
    protected static string $resource = DeliveryCountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
