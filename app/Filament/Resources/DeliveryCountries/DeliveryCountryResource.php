<?php

namespace App\Filament\Resources\DeliveryCountries;

use App\Enums\Market;
use App\Filament\NavigationGroup;
use App\Filament\Resources\DeliveryCountries\Pages\ManageDeliveryCountries;
use App\Models\DeliveryCountry;
use App\Support\Countries;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * International delivery: the countries outside Togo GG-TECH ships to, and what each costs.
 */
class DeliveryCountryResource extends Resource
{
    protected static ?string $model = DeliveryCountry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('International delivery');
    }

    public static function getModelLabel(): string
    {
        return __('delivery country');
    }

    public static function getPluralModelLabel(): string
    {
        return __('delivery countries');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('country_code')
                        ->label(__('Country'))
                        ->options(fn () => Countries::all())
                        ->searchable()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->in(fn () => array_keys(Countries::all())),
                    TextInput::make('fee')
                        ->label(__('Delivery fee'))
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->suffix('FCFA')
                        ->required()
                        ->helperText(__('Fixed fee charged for any order delivered to this country.')),
                    TextInput::make('delivery_time.fr')->label(__('Delivery time (French)'))->placeholder('5 à 10 jours')->maxLength(60),
                    TextInput::make('delivery_time.en')->label(__('Delivery time (English)'))->placeholder('5 to 10 days')->maxLength(60),
                    Textarea::make('conditions.fr')
                        ->label(__('Conditions (French)'))
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText(__('Shown to customers at checkout, e.g. customs duties paid on arrival.')),
                    Textarea::make('conditions.en')->label(__('Conditions (English)'))->rows(2)->maxLength(500),
                    TextInput::make('position')->label(__('Order'))->numeric()->integer()->minValue(0)->maxValue(65535)->default(0),
                    Toggle::make('is_active')
                        ->label(__('Delivery enabled'))
                        ->helperText(__('Customers can only choose enabled countries at checkout.'))
                        ->default(true)
                        ->inline(false),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('country_code')
                    ->label(__('Country'))
                    ->formatStateUsing(fn (DeliveryCountry $record) => $record->name())
                    ->description(fn (DeliveryCountry $record) => $record->translate('conditions')),
                TextColumn::make('fee')
                    ->label(__('Delivery fee'))
                    ->formatStateUsing(fn (DeliveryCountry $record) => Market::Togo->format($record->fee)),
                TextColumn::make('delivery_time')
                    ->label(__('Time'))
                    ->formatStateUsing(fn (DeliveryCountry $record) => $record->translate('delivery_time'))
                    ->placeholder('—'),
                ToggleColumn::make('is_active')->label(__('Delivery enabled')),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('Delivery enabled')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('No international destinations yet'))
            ->emptyStateDescription(__('Add the countries GG-TECH delivers to outside Togo. Until then, checkout offers delivery within Togo only.'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDeliveryCountries::route('/'),
        ];
    }
}
