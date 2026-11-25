<?php

namespace App\Providers;

use App\Enums\Market;
use App\Http\Middleware\SetLocale;
use App\Models\Category;
use App\Models\DeliveryCountry;
use App\Services\Currency\DisplayCurrency;
use App\Services\Currency\ExchangeRates;
use App\Services\Delivery\FixedFeePerCountry;
use App\Services\Delivery\InternationalDeliveryPricing;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentGateways;
use App\Support\ShopLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Intervention\Image\ImageManager;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn () => ImageManager::gd());

        $this->app->bind(InternationalDeliveryPricing::class, FixedFeePerCountry::class);

        $this->app->bind(ExchangeRates::class, fn () => new ExchangeRates(
            providerUrl: config('services.exchange_rates.url'),
            staleAfterHours: config('services.exchange_rates.stale_after_hours'),
        ));

        $this->app->scoped(DisplayCurrency::class);

        // New payments use the gateway chosen in the admin; existing ones go through PaymentGateways::forOrder().
        $this->app->bind(PaymentGateway::class, fn ($app) => $app->make(PaymentGateways::class)->active());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Livewire::addPersistentMiddleware([SetLocale::class]);

        View::composer(['components.layouts.store', 'home', 'products.show', 'shop.index'], function (ViewInstance $view) {
            $view->with([
                'menuCategories' => Category::query()->active()->get(),
                'market' => Market::current(),
            ]);
        });

        View::composer(['components.layouts.store', 'home', 'store', 'products.show'], function (ViewInstance $view) {
            $view->with([
                'shopLocations' => ShopLocation::listed(),
                // International delivery is only mentioned once staff have enabled a destination.
                'deliveryCountries' => DeliveryCountry::query()->active()->get(),
            ]);
        });
    }
}
