<?php

use App\Filament\Resources\Currencies\Pages\ManageCurrencies;
use App\Models\Currency;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    config(['services.exchange_rates.url' => 'https://rates.test/v1']);
    Http::preventStrayRequests();
});

function currency(string $code): Currency
{
    return Currency::query()->where('code', $code)->sole();
}

it('shows the rates in use with their source', function () {
    Currency::factory()->manual(800)->create(['code' => 'GBP']);

    $this->get('/admin/currencies')
        ->assertOk()
        ->assertSee('Exchange rates')
        ->assertSee('1 EUR = 655.957 FCFA')
        ->assertSee('Fixed rate')
        ->assertSee('Manual')
        ->assertSee('Waiting for the first update');
});

it('lets staff override a rate and go back to the automatic one', function () {
    $usd = currency('USD');
    $usd->update(['auto_rate' => 600, 'auto_source' => Currency::SourceProvider, 'auto_updated_at' => now()]);

    Livewire::test(ManageCurrencies::class)
        ->callAction(TestAction::make('edit')->table($usd), data: ['manual_rate' => 610])
        ->assertHasNoActionErrors();

    expect($usd->refresh())
        ->rate()->toBe(610.0)
        ->isManual()->toBeTrue()
        ->manual_updated_at->not->toBeNull();

    Livewire::test(ManageCurrencies::class)
        ->callAction(TestAction::make('useAutomaticRate')->table($usd))
        ->assertHasNoActionErrors();

    expect($usd->refresh())
        ->rate()->toBe(600.0)
        ->isManual()->toBeFalse();
});

it('adds a currency and fetches its automatic rate straight away', function () {
    Http::fake(['rates.test/*' => Http::response(['base' => 'EUR', 'rates' => ['USD' => 1.25, 'GBP' => 0.8]])]);

    Livewire::test(ManageCurrencies::class)
        ->callAction('create', data: ['code' => 'GBP', 'is_enabled' => true])
        ->assertHasNoActionErrors();

    expect(currency('GBP')->rate())->toBe(819.94625);
});

it('requires a manual rate for a currency with no automatic source', function () {
    Livewire::test(ManageCurrencies::class)
        ->callAction('create', data: ['code' => 'NGN', 'is_enabled' => true])
        ->assertHasActionErrors(['manual_rate' => 'required']);

    Http::fake();

    Livewire::test(ManageCurrencies::class)
        ->callAction('create', data: ['code' => 'NGN', 'manual_rate' => 0.39, 'is_enabled' => true])
        ->assertHasNoActionErrors();

    expect(currency('NGN')->rate())->toBe(0.39);
});

it('refuses a currency twice or one outside the supported list', function (string $code) {
    Livewire::test(ManageCurrencies::class)
        ->callAction('create', data: ['code' => $code, 'manual_rate' => 1])
        ->assertHasActionErrors(['code']);
})->with(['already added' => 'EUR', 'not supported' => 'ABC', 'FCFA itself' => 'XOF']);

it('refreshes the rates on request', function () {
    Http::fake(['rates.test/*' => Http::response(['base' => 'EUR', 'rates' => ['USD' => 1.25]])]);

    Livewire::test(ManageCurrencies::class)
        ->callAction('refreshRates')
        ->assertNotified(__('Exchange rates updated'));

    expect(currency('USD')->rate())->toBe(524.7656);
});

it('warns staff and keeps the saved rates when the rate service is down', function () {
    Http::fake(['rates.test/*' => Http::response('Down', 503)]);
    currency('USD')->update(['auto_rate' => 600, 'auto_source' => Currency::SourceProvider, 'auto_updated_at' => now()->subDays(3)]);

    Livewire::test(ManageCurrencies::class)
        ->callAction('refreshRates')
        ->assertNotified(__('The rate service could not be reached'));

    expect(currency('USD')->rate())->toBe(600.0);
});

it('lets staff stop offering a currency to visitors', function () {
    $usd = currency('USD');

    Livewire::test(ManageCurrencies::class)
        ->call('updateTableColumnState', 'is_enabled', (string) $usd->getKey(), false);

    expect($usd->refresh()->is_enabled)->toBeFalse();
});
