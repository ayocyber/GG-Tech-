<?php

namespace App\Filament\Resources\DeliveryZones;

use App\Enums\Market;
use App\Filament\NavigationGroup;
use App\Filament\Resources\DeliveryZones\Pages\ManageDeliveryZones;
use App\Models\DeliveryZone;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('delivery zone');
    }

    public static function getPluralModelLabel(): string
    {
        return __('delivery zones');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Hidden::make('market')->default(Market::Togo->value),
                    TextInput::make('fee')
                        ->label(__('Fee'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('FCFA')
                        ->helperText(__('Leave empty for “on quote”.'))
                        ->columnSpanFull(),
                    TextInput::make('name.fr')->label(__('Name (French)'))->required()->maxLength(120),
                    TextInput::make('name.en')->label(__('Name (English)'))->required()->maxLength(120),
                    TextInput::make('areas.fr')->label(__('Areas (French)'))->maxLength(255),
                    TextInput::make('areas.en')->label(__('Areas (English)'))->maxLength(255),
                    TextInput::make('delivery_time.fr')->label(__('Delivery time (French)'))->placeholder('24 – 48 h')->maxLength(60),
                    TextInput::make('delivery_time.en')->label(__('Delivery time (English)'))->maxLength(60),
                    TextInput::make('position')->label(__('Order'))->numeric()->default(0),
                    Toggle::make('is_active')->label(__('Active'))->default(true)->inline(false),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Zone'))
                    ->formatStateUsing(fn (DeliveryZone $record) => $record->translate('name'))
                    ->description(fn (DeliveryZone $record) => $record->translate('areas')),
                TextColumn::make('fee')
                    ->label(__('Fee'))
                    ->formatStateUsing(fn (DeliveryZone $record) => $record->isQuoted() ? __('On quote') : $record->market->format($record->fee))
                    ->placeholder(__('On quote')),
                TextColumn::make('delivery_time')
                    ->label(__('Time'))
                    ->formatStateUsing(fn (DeliveryZone $record) => $record->translate('delivery_time')),
                IconColumn::make('is_active')->label(__('Active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDeliveryZones::route('/'),
        ];
    }
}
