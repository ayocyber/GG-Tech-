<?php

use App\Models\Currency;
use App\Services\Currency\ExchangeRates;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.exchange_rates.url' => 'https://rates.test/v1']);
});

function usd(): Currency
{
    return Currency::query()->where('code', 'USD')->sole();
}

it('stores the provider rate as FCFA for one unit and keeps the euro on its fixed rate', function () {
    Http::fake(['rates.test/*' => Http::response(['base' => 'EUR', 'rates' => ['USD' => 1.25]])]);

    expect(app(ExchangeRates::class)->refresh())->toBeTrue();

    expect(usd())
        ->rate()->toBe(524.7656)
        ->auto_source->toBe(Currency::SourceProvider)
        ->auto_updated_at->not->toBeNull()
        ->and(Currency::query()->where('code', 'EUR')->sole())
        ->rate()->toBe(655.957)
        ->auto_source->toBe(Currency::SourcePeg);

    Http::assertSent(fn (Request $request) => $request['base'] === 'EUR' && $request['symbols'] === 'USD');
});

it('keeps the last saved rate when the provider is unavailable', function (Closure $failure) {
    $failure();
    usd()->update(['auto_rate' => 600, 'auto_source' => Currency::SourceProvider, 'auto_updated_at' => now()->subDays(2)]);

    expect(app(ExchangeRates::class)->refresh())->toBeFalse()
        ->and(usd()->rate())->toBe(600.0);
})->with([
    'server error' => fn () => fn () => Http::fake(['rates.test/*' => Http::response('Down', 503)]),
    'no rates in the answer' => fn () => fn () => Http::fake(['rates.test/*' => Http::response(['message' => 'not found'])]),
    'connection refused' => fn () => fn () => Http::fake(['rates.test/*' => Http::failedConnection()]),
]);

it('refreshes stale rates after the page is sent, once', function () {
    Http::fake(['rates.test/*' => Http::response(['base' => 'EUR', 'rates' => ['USD' => 1.25]])]);
    usd()->update(['auto_rate' => 600, 'auto_source' => Currency::SourceProvider, 'auto_updated_at' => now()->subDays(2)]);

    $this->get('/fr')->assertOk();
    $this->get('/fr/shop')->assertOk();

    expect(usd()->rate())->toBe(524.7656);
    Http::assertSentCount(1);
});

it('does not call the provider while rates are fresh', function () {
    Http::fake();
    usd()->update(['auto_rate' => 600, 'auto_source' => Currency::SourceProvider, 'auto_updated_at' => now()->subHour()]);

    $this->get('/fr')->assertOk();

    Http::assertNothingSent();
});

it('leaves automatic updates off when no provider is configured', function () {
    config(['services.exchange_rates.url' => '']);
    Http::fake();

    $this->get('/fr')->assertOk();

    expect(app(ExchangeRates::class)->refresh())->toBeFalse();
    Http::assertNothingSent();
});
