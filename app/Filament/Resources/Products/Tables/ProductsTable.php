<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\Market;
use App\Enums\ProductStatus;
use App\Enums\TonerType;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'brand', 'coverImage', 'variants']))
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('cover')
                    ->label('')
                    ->state(fn (Product $record) => $record->coverImage?->url(400))
                    ->square()
                    ->imageSize(48),
                TextColumn::make('name')
                    ->label(__('Product'))
                    ->formatStateUsing(fn (Product $record) => $record->translate('name'))
                    ->description(fn (Product $record) => $record->model)
                    ->searchable(query: fn (Builder $query, string $search) => $query->search($search))
                    ->wrap(),
                TextColumn::make('category.slug')
                    ->label(__('Category'))
                    ->formatStateUsing(fn (Product $record) => $record->category->translate('name'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('toner_type')->label(__('Toner type'))->badge()->placeholder('—')->toggleable(),
                TextColumn::make('brand.name')->label(__('Brand'))->toggleable(),
                TextColumn::make('price')
                    ->label(__('Price'))
                    ->state(fn (Product $record) => $record->price_on_request
                        ? __('On request')
                        : ($record->startingPrice(Market::Togo) ? Market::Togo->format($record->startingPrice(Market::Togo)) : '—'))
                    ->description(fn (Product $record) => $record->variants->count() > 1
                        ? trans_choice(':count option|:count options', $record->variants->count(), ['count' => $record->variants->count()])
                        : null),
                IconColumn::make('in_stock')
                    ->label(__('Stock'))
                    ->state(fn (Product $record) => $record->isInStock())
                    ->boolean(),
                TextColumn::make('status')->label(__('Status'))->badge(),
                IconColumn::make('needs_review')
                    ->label(__('Needs review'))
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-check')
                    ->falseColor('gray'),
                IconColumn::make('is_featured')->label(__('Premium'))->boolean()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('catalog_page')->label(__('Catalogue page'))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label(__('Category'))
                    ->options(fn () => Category::query()->active()->get()->mapWithKeys(
                        fn (Category $category) => [$category->id => $category->translate('name')],
                    )),
                SelectFilter::make('toner_type')->label(__('Toner type'))->options(TonerType::class),
                SelectFilter::make('brand_id')->label(__('Brand'))->relationship('brand', 'name')->searchable()->preload(),
                SelectFilter::make('status')->label(__('Status'))->options(ProductStatus::class),
                TernaryFilter::make('needs_review')->label(__('Needs review')),
                TernaryFilter::make('photo')
                    ->label(__('Photo'))
                    ->trueLabel(__('With photo'))
                    ->falseLabel(__('Without photo'))
                    ->queries(
                        true: fn (Builder $query) => $query->has('images'),
                        false: fn (Builder $query) => $query->doesntHave('images'),
                    ),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('View'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Product $record) => route('products.show', ['locale' => app()->getLocale(), 'product' => $record]))
                    ->openUrlInNewTab()
                    ->visible(fn (Product $record) => $record->status === ProductStatus::Published),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label(__('Publish'))
                        ->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['status' => ProductStatus::Published]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->label(__('Move back to draft'))
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['status' => ProductStatus::Draft]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('reviewed')
                        ->label(__('Mark as reviewed'))
                        ->icon('heroicon-o-check-badge')
                        ->action(fn (Collection $records) => $records->each->update(['needs_review' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
