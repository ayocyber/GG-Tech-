<?php

namespace App\Filament\Resources\Products;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Catalogue;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function getModelLabel(): string
    {
        return __('product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('products');
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->translate('name') ?? '';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Product::query()->where('needs_review', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Products to review');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
