<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing(['items', 'deliveryZone']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')
                ->label(__('Message the customer on WhatsApp'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn () => 'https://wa.me/'.preg_replace('/\D/', '', $this->record->phone).'?text='.rawurlencode(
                    $this->record->locale === 'en'
                        ? "Hello {$this->record->customer_name}, this is GG-TECH about your order {$this->record->number}."
                        : "Bonjour {$this->record->customer_name}, ici GG-TECH au sujet de votre commande {$this->record->number}.",
                ))
                ->openUrlInNewTab(),
            EditAction::make()->label(__('Update')),
        ];
    }
}
