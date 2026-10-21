<?php

namespace App\Filament\Resources\Categories;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalogue;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('categories');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('name.fr')->label(__('Name (French)'))->required()->maxLength(120),
                    TextInput::make('name.en')->label(__('Name (English)'))->required()->maxLength(120),
                    Textarea::make('description.fr')->label(__('Description (French)'))->rows(2),
                    Textarea::make('description.en')->label(__('Description (English)'))->rows(2),
                    TextInput::make('slug')->required()->alphaDash()->unique(ignoreRecord: true),
                    Select::make('icon')
                        ->label(__('Icon'))
                        ->options([
                            'toner' => __('Toner'),
                            'printer' => __('Printer'),
                            'network' => __('Network'),
                            'computer' => __('Computer'),
                            'power' => __('Power'),
                            'accessory' => __('Accessory'),
                        ])
                        ->required(),
                    TextInput::make('position')->label(__('Order'))->numeric()->default(0)->required(),
                    Toggle::make('is_active')->label(__('Visible'))->default(true)->inline(false),
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
                    ->label(__('Name'))
                    ->formatStateUsing(fn (Category $record) => $record->translate('name', 'fr'))
                    ->description(fn (Category $record) => $record->translate('name', 'en')),
                TextColumn::make('products_count')->label(__('Products'))->counts('products'),
                IconColumn::make('is_active')->label(__('Visible'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
