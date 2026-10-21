<?php

namespace App\Filament\Resources\Brands;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Brands\Pages\ManageBrands;
use App\Models\Brand;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalogue;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('brand');
    }

    public static function getPluralModelLabel(): string
    {
        return __('brands');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                TextInput::make('slug')->required()->alphaDash()->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable(),
                TextColumn::make('products_count')->label(__('Products'))->counts('products')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBrands::route('/'),
        ];
    }
}
