<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
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
            Action::make('uploadPhotos')
                ->label(__('Add several photos'))
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->visible(fn () => config('store.photo_uploads'))
                ->modalDescription(__('Select several photos at once. They are added after the existing ones and watermarked automatically.'))
                ->schema([
                    FileUpload::make('photos')
                        ->hiddenLabel()
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->maxFiles(12)
                        ->disk(config('store.disks.originals'))
                        ->directory('products/originals/uploads')
                        ->visibility('private')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(8192)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->addPhotos($data['photos']);

                    Notification::make()
                        ->title(trans_choice(':count photo added|:count photos added', count($data['photos']), ['count' => count($data['photos'])]))
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function addPhotos(array $paths): void
    {
        $position = (int) $this->record->images()->max('position');

        foreach (array_values($paths) as $index => $path) {
            $this->record->images()->create(['original_path' => $path, 'position' => $position + $index + 1]);
        }

        $photos = $this->form->getComponentByStatePath('images');

        if ($photos instanceof Repeater) {
            $photos->clearCachedExistingRecords();
            $photos->fillFromRelationship();
        }
    }
}
