<?php

namespace App\Filament\Widgets;

use App\Enums\Market;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\QuoteRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $monthOrders = Order::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('status', '!=', OrderStatus::Cancelled);

        return [
            Stat::make(__('Orders to process'), Order::query()->where('status', OrderStatus::New)->count())
                ->description(__('New orders to confirm'))
                ->icon('heroicon-o-shopping-bag')
                ->color('danger'),
            Stat::make(__('Sales this month'), Market::Togo->format((int) (clone $monthOrders)->sum('total')))
                ->description(trans_choice(':count order this month|:count orders this month', (clone $monthOrders)->count(), ['count' => (clone $monthOrders)->count()]))
                ->icon('heroicon-o-banknotes'),
            Stat::make(__('Pending quotes'), QuoteRequest::query()->where('status', QuoteStatus::New)->count())
                ->description(__('Companies and resellers'))
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning'),
            Stat::make(__('Products to review'), Product::query()->where('needs_review', true)->count())
                ->description(trans_choice(':count product without a photo|:count products without a photo', $withoutPhoto = Product::query()->doesntHave('images')->count(), ['count' => $withoutPhoto]))
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
