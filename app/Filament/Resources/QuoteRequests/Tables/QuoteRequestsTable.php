<?php

namespace App\Filament\Resources\QuoteRequests\Tables;

use App\Enums\QuoteStatus;
use App\Models\QuoteRequest;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;

class QuoteRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->label(__('No.'))->searchable()->weight('bold'),
                TextColumn::make('created_at')->label(__('Date'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('company_name')
                    ->label(__('Company'))
                    ->description(fn (QuoteRequest $record) => $record->contact_name.' · '.$record->phone)
                    ->searchable(['company_name', 'contact_name', 'phone', 'email']),
                TextColumn::make('items_count')->label(__('Items')),
                TextColumn::make('needed_by')->label(__('Needed by'))->date('d/m/Y')->placeholder('—')->sortable(),
                TextColumn::make('status')->label(__('Status'))->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Status'))->options(QuoteStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
