<?php

namespace App\Filament\Resources\Currencies;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Currencies\Pages\ManageCurrencies;
use App\Models\Currency;
use App\Services\Currency\ExchangeRates;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use UnitEnum;

/**
 * Exchange rates: the currencies visitors may see prices in, and the rate each one uses.
 */
class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Exchange rates');
    }

    public static function getModelLabel(): string
    {
        return __('currency');
    }

    public static function getPluralModelLabel(): string
    {
        return __('currencies');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('code')
                        ->label(__('Currency'))
                        ->options(fn () => Currency::supported())
                        ->searchable()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->in(fn () => array_keys(Currency::supported()))
                        ->live()
                        ->disabledOn('edit'),
                    TextInput::make('manual_rate')
                        ->label(__('Manual rate (FCFA for 1 unit)'))
                        ->numeric()
                        ->minValue(0.000001)
                        ->maxValue(1000000)
                        ->suffix('FCFA')
                        ->required(fn (Get $get) => filled($get('code')) && ! Currency::hasAutomaticSource($get('code')))
                        ->helperText(fn (Get $get) => filled($get('code')) && ! Currency::hasAutomaticSource($get('code'))
                            ? __('There is no automatic rate for this currency: enter it by hand.')
                            : __('Leave empty to use the automatic rate.')),
                    TextInput::make('position')->label(__('Order'))->numeric()->integer()->minValue(0)->maxValue(65535)->default(0),
                    Toggle::make('is_enabled')
                        ->label(__('Visitors can choose it'))
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
            ->description(__('Prices are stored and charged in FCFA. These rates only convert the prices visitors see.'))
            ->columns([
                TextColumn::make('code')
                    ->label(__('Currency'))
                    ->weight('bold')
                    ->description(fn (Currency $record) => $record->name()),
                TextColumn::make('rate')
                    ->label(__('Rate in use'))
                    ->state(fn (Currency $record) => $record->rate() === null
                        ? null
                        : '1 '.$record->code.' = '.Number::format($record->rate(), maxPrecision: 4, locale: app()->getLocale()).' FCFA')
                    ->placeholder(__('No rate yet')),
                TextColumn::make('source')
                    ->label(__('Source'))
                    ->badge()
                    ->state(fn (Currency $record) => self::sourceLabel($record))
                    ->color(fn (Currency $record) => match (true) {
                        $record->isManual() => 'warning',
                        $record->auto_source === null => 'gray',
                        default => 'info',
                    })
                    ->description(fn (Currency $record) => $record->isManual() && $record->auto_rate !== null
                        ? __('Automatic: :rate FCFA', ['rate' => Number::format((float) $record->auto_rate, maxPrecision: 4, locale: app()->getLocale())])
                        : null),
                TextColumn::make('updated')
                    ->label(__('Last updated'))
                    ->state(fn (Currency $record) => $record->rateUpdatedAt())
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                ToggleColumn::make('is_enabled')->label(__('Visitors can choose it')),
            ])
            ->recordActions([
                Action::make('useAutomaticRate')
                    ->label(__('Use automatic rate'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->visible(fn (Currency $record) => $record->isManual() && Currency::hasAutomaticSource($record->code))
                    ->requiresConfirmation()
                    ->action(fn (Currency $record) => $record->update(['manual_rate' => null, 'manual_updated_at' => null])),
                EditAction::make()->mutateDataUsing(self::stampManualRate(...)),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('Prices are shown in FCFA only'))
            ->emptyStateDescription(__('Add a currency so visitors can see prices converted from FCFA.'));
    }

    public static function sourceLabel(Currency $record): string
    {
        return match (true) {
            $record->isManual() => __('Manual'),
            $record->auto_source === Currency::SourcePeg => __('Fixed rate'),
            $record->auto_source === Currency::SourceProvider => __('Automatic (ECB)'),
            default => __('Waiting for the first update'),
        };
    }

    /**
     * Records when staff last typed a rate, so the list can show how old it is.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function stampManualRate(array $data, ?Currency $record = null): array
    {
        $manualRate = filled($data['manual_rate'] ?? null) ? (float) $data['manual_rate'] : null;
        $data['manual_rate'] = $manualRate;

        if ($manualRate === null) {
            $data['manual_updated_at'] = null;
        } elseif ($record === null || $record->manual_rate === null || (float) $record->manual_rate !== $manualRate) {
            $data['manual_updated_at'] = now();
        }

        return $data;
    }

    public static function refreshAction(): Action
    {
        return Action::make('refreshRates')
            ->label(__('Refresh rates now'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->action(function (ExchangeRates $rates) {
                $rates->refresh()
                    ? Notification::make()->title(__('Exchange rates updated'))->success()->send()
                    : Notification::make()
                        ->title(__('The rate service could not be reached'))
                        ->body(__('The last saved rates are still in use. Try again later.'))
                        ->warning()
                        ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCurrencies::route('/'),
        ];
    }
}
