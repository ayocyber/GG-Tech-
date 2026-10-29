<?php

use App\Http\Controllers\AdminLocaleController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderConfirmationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuoteConfirmationController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StoreLocationController;
use App\Http\Middleware\SetLocale;
use App\Support\LegacyLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('home', ['locale' => session('locale', config('app.locale'))]);
});

Route::get('/admin-language/{locale}', AdminLocaleController::class)->name('admin.locale');

Route::prefix('{locale}')
    ->whereIn('locale', SetLocale::SUPPORTED)
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', HomeController::class)->name('home');
        Route::get('/shop/{category:slug?}', ShopController::class)
            ->name('shop')
            ->missing(fn (Request $request) => LegacyLinks::redirectCategory($request));
        Route::get('/products/{product:slug}', [ProductController::class, 'show'])
            ->name('products.show')
            ->missing(fn (Request $request) => LegacyLinks::redirectProduct($request));
        Route::get('/cart', CartController::class)->name('cart');
        Route::get('/checkout', CheckoutController::class)->name('checkout');
        Route::get('/orders/{order:number}/thank-you', OrderConfirmationController::class)
            ->middleware('signed')
            ->name('orders.thanks');
        Route::get('/quote', QuoteController::class)->name('quote');
        Route::get('/quotes/{quoteRequest:number}/thank-you', QuoteConfirmationController::class)
            ->middleware('signed')
            ->name('quotes.thanks');
        Route::get('/store', StoreLocationController::class)->name('store');
    });
