<?php

namespace App\Filament\Resources\QuoteRequests\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuoteRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make(__('Requested items'))->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('description')->label(__('Item'))->columnSpan(3),
                                TextEntry::make('quantity')->label(__('Qty')),
                            ]),
                    ]),
                    Section::make(__('Customer notes'))->schema([
                        TextEntry::make('notes')->hiddenLabel()->placeholder('—'),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('Status'))->schema([
                        TextEntry::make('status')->label(__('Status'))->badge(),
                        TextEntry::make('created_at')->label(__('Received'))->dateTime('d/m/Y H:i'),
                        TextEntry::make('needed_by')->label(__('Needed by'))->date('d/m/Y')->placeholder('—'),
                    ]),
                    Section::make(__('Company'))->schema([
                        TextEntry::make('company_name')->label(__('Company name')),
                        TextEntry::make('tax_id')->label(__('Tax ID (NIF)'))->placeholder('—'),
                        TextEntry::make('contact_name')->label(__('Contact name')),
                        TextEntry::make('job_title')->label(__('Role'))->placeholder('—'),
                        TextEntry::make('phone')->label(__('Phone'))->copyable(),
                        TextEntry::make('email')->label(__('Email'))->copyable(),
                        TextEntry::make('city')->label(__('Delivery city'))->placeholder('—'),
                    ]),
                ]),
            ]);
    }
}
