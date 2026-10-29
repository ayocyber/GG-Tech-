<?php

namespace App\Providers;

use App\Enums\Market;
use App\Http\Middleware\SetLocale;
use App\Models\Category;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Livewire::addPersistentMiddleware([SetLocale::class]);

        View::composer(['components.layouts.store', 'home', 'products.show'], function (ViewInstance $view) {
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
