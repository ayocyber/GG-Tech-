<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $deliveryFee = filled($data['delivery_fee'] ?? null) ? (int) $data['delivery_fee'] : null;

        return [
            ...$data,
            'delivery_fee' => $deliveryFee,
            'total' => $this->record->subtotal + ($deliveryFee ?? 0),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', ['record' => $this->record]);
    }
}
