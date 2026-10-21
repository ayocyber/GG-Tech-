<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(__('View on the website'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => route('products.show', ['locale' => app()->getLocale(), 'product' => $this->record]))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->status === ProductStatus::Published),
            DeleteAction::make(),
        ];
    }
}
