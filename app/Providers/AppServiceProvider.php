<?php

namespace App\Providers;

use App\Enums\Market;
use App\Http\Middleware\SetLocale;
use App\Models\Category;
use App\Services\Payments\DemoPaymentGateway;
use App\Services\Payments\PayGateGateway;
use App\Services\Payments\PaymentGateway;
use App\Support\ShopLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Intervention\Image\ImageManager;
use Livewire\Livewire;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn () => ImageManager::gd());

        $this->app->bind(PaymentGateway::class, fn () => match (config('services.paygate.mode')) {
            'live' => new PayGateGateway(
                apiKey: config('services.paygate.api_key') ?: throw new RuntimeException('PAYGATE_API_KEY is not set.'),
                baseUrl: config('services.paygate.base_url'),
            ),
            default => new DemoPaymentGateway,
        });
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
            $view->with('shopLocations', ShopLocation::listed());
        });
    }
}
