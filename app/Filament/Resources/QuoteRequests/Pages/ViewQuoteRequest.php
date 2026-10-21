<?php

namespace App\Filament\Resources\QuoteRequests\Pages;

use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuoteRequest extends ViewRecord
{
    protected static string $resource = QuoteRequestResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing('items');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')
                ->label(__('Reply on WhatsApp'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn () => 'https://wa.me/'.preg_replace('/\D/', '', $this->record->phone).'?text='.rawurlencode(
                    $this->record->locale === 'en'
                        ? "Hello {$this->record->contact_name}, this is GG-TECH about your quote request {$this->record->number}."
                        : "Bonjour {$this->record->contact_name}, ici GG-TECH au sujet de votre demande de devis {$this->record->number}.",
                ))
                ->openUrlInNewTab(),
            Action::make('email')
                ->label(__('Reply by email'))
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->url(fn () => 'mailto:'.$this->record->email.'?subject='.rawurlencode("Devis GG-TECH {$this->record->number}")),
            EditAction::make()->label(__('Update')),
        ];
    }
}
