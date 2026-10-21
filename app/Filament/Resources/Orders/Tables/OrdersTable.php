<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\FulfilmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label(__('No.'))->searchable()->weight('bold'),
                TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer_name')
                    ->label(__('Customer'))
                    ->description(fn (Order $record) => $record->phone)
                    ->searchable(['customer_name', 'phone', 'email']),
                TextColumn::make('items_count')->label(__('Items')),
                TextColumn::make('total')
                    ->label(__('Total'))
                    ->formatStateUsing(fn (Order $record) => $record->formatMoney($record->total))
                    ->description(fn (Order $record) => $record->delivery_fee === null && $record->fulfilment === FulfilmentMethod::Delivery ? '+ '.__('delivery to be confirmed') : null)
                    ->sortable(),
                TextColumn::make('fulfilment')->label(__('Method'))->badge()->color('gray'),
                TextColumn::make('status')->label(__('Status'))->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Status'))->options(OrderStatus::class),
                SelectFilter::make('fulfilment')->label(__('Method'))->options(FulfilmentMethod::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
