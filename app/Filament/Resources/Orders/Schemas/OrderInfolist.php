<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make(__('Items'))->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label(__('Product'))
                                    ->formatStateUsing(fn (OrderItem $record) => $record->product_name.($record->variant_label ? ' — '.$record->variant_label : ''))
                                    ->columnSpan(2),
                                TextEntry::make('quantity')->label(__('Qty')),
                                TextEntry::make('line_total')
                                    ->label(__('Total'))
                                    ->formatStateUsing(fn (OrderItem $record) => $record->order->formatMoney($record->line_total)),
                            ]),
                        Grid::make(3)->schema([
                            TextEntry::make('subtotal')->label(__('Subtotal'))
                                ->formatStateUsing(fn (Order $record) => $record->formatMoney($record->subtotal)),
                            TextEntry::make('delivery_fee')->label(__('Delivery'))
                                ->formatStateUsing(fn (Order $record) => $record->formatMoney($record->delivery_fee))
                                ->placeholder(__('To be confirmed')),
                            TextEntry::make('total')->label(__('Total'))
                                ->formatStateUsing(fn (Order $record) => $record->formatMoney($record->total))
                                ->weight('bold')
                                ->size('lg'),
                        ]),
                    ]),
                    Section::make(__('Delivery'))->columns(2)->schema([
                        TextEntry::make('fulfilment')->label(__('Method'))->badge(),
                        TextEntry::make('pickup_shop')
                            ->label(__('Pickup shop'))
                            ->state(fn (Order $record) => $record->pickupLocation() ? $record->pickupLocation()->shop->getLabel().' · '.$record->pickupLocation()->title() : null)
                            ->placeholder('—'),
                        TextEntry::make('deliveryZone.name')
                            ->label(__('Zone'))
                            ->formatStateUsing(fn (Order $record) => $record->deliveryZone?->translate('name'))
                            ->placeholder('—'),
                        TextEntry::make('address')->label(__('Address'))->placeholder('—'),
                        TextEntry::make('city')->label(__('City / neighbourhood'))->placeholder('—'),
                        TextEntry::make('landmark')->label(__('Landmark'))->placeholder('—'),
                        TextEntry::make('notes')->label(__('Customer instructions'))->placeholder('—')->columnSpanFull(),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('Status'))->schema([
                        TextEntry::make('status')->label(__('Status'))->badge(),
                        TextEntry::make('payment_method')
                            ->label(__('Payment'))
                            ->formatStateUsing(fn (string $state) => OrderResource::paymentMethods()[$state] ?? $state),
                        TextEntry::make('created_at')->label(__('Received'))->dateTime('d/m/Y H:i'),
                    ]),
                    Section::make(__('Customer'))->schema([
                        TextEntry::make('customer_name')->label(__('Name')),
                        TextEntry::make('phone')->label(__('Phone'))->copyable(),
                        TextEntry::make('email')->label(__('Email'))->copyable(),
                        TextEntry::make('locale')->label(__('Language'))->formatStateUsing(fn (string $state) => strtoupper($state)),
                    ]),
                ]),
            ]);
    }
}
