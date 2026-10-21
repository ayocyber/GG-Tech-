<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Order follow-up'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('status')
                            ->label(__('Status'))
                            ->options(OrderStatus::class)
                            ->required(),
                        Select::make('payment_method')
                            ->label(__('Payment'))
                            ->options(OrderResource::paymentMethods())
                            ->required(),
                        TextInput::make('delivery_fee')
                            ->label(__('Delivery fee'))
                            ->numeric()
                            ->minValue(0)
                            ->suffix('FCFA')
                            ->helperText(__('Leave empty until the fee is confirmed. The total is recalculated.')),
                    ]),
            ]);
    }
}
