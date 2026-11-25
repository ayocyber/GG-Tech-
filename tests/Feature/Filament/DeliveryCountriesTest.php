<?php

use App\Filament\Resources\DeliveryCountries\Pages\ManageDeliveryCountries;
use App\Models\DeliveryCountry;
use App\Models\User;
use App\Services\Delivery\InternationalDeliveryPricing;
use App\Support\Countries;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('shows the international delivery screen', function () {
    $this->get('/admin/delivery-countries')
        ->assertOk()
        ->assertSee('International delivery');
});

it('lets staff add a country with its delivery fee', function () {
    Livewire::test(ManageDeliveryCountries::class)
        ->callAction('create', data: [
            'country_code' => 'GH',
            'fee' => 30000,
            'delivery_time' => ['fr' => '7 jours', 'en' => '7 days'],
            'conditions' => ['fr' => 'Droits de douane à la charge du client.', 'en' => 'Customs duties paid by the customer.'],
            'is_active' => true,
        ])
        ->assertHasNoActionErrors();

    expect(DeliveryCountry::query()->sole())
        ->country_code->toBe('GH')
        ->fee->toBe(30000)
        ->is_active->toBeTrue()
        ->translate('conditions', 'en')->toBe('Customs duties paid by the customer.');
});

it('lets staff change the fee of a country', function () {
    $country = DeliveryCountry::factory()->create(['country_code' => 'BJ', 'fee' => 10000]);

    Livewire::test(ManageDeliveryCountries::class)
        ->callAction(TestAction::make('edit')->table($country), data: ['fee' => 12500])
        ->assertHasNoActionErrors();

    expect($country->refresh()->fee)->toBe(12500);
});

it('lets staff disable delivery to a country from the list', function () {
    $country = DeliveryCountry::factory()->create(['country_code' => 'FR']);

    Livewire::test(ManageDeliveryCountries::class)
        ->call('updateTableColumnState', 'is_active', (string) $country->getKey(), false);

    expect($country->refresh()->is_active)->toBeFalse()
        ->and(DeliveryCountry::query()->active()->exists())->toBeFalse();
});

it('refuses the same country twice, a missing fee, a negative order and Togo itself', function (array $data, string $field) {
    DeliveryCountry::factory()->create(['country_code' => 'CI']);

    Livewire::test(ManageDeliveryCountries::class)
        ->callAction('create', data: [...['country_code' => 'SN', 'fee' => 20000], ...$data])
        ->assertHasActionErrors([$field]);
})->with([
    'duplicate country' => [['country_code' => 'CI'], 'country_code'],
    'no fee' => [['fee' => null], 'fee'],
    'negative fee' => [['fee' => -1], 'fee'],
    'negative order' => [['position' => -7], 'position'],
    'order too large' => [['position' => 70000], 'position'],
    'Togo' => [['country_code' => 'TG'], 'country_code'],
    'not a country' => [['country_code' => 'EU'], 'country_code'],
]);

it('names countries in the visitor language and leaves Togo out of the list', function () {
    expect(Countries::name('DE', 'fr'))->toBe('Allemagne')
        ->and(Countries::name('DE', 'en'))->toBe('Germany')
        ->and(Countries::all('en'))->toHaveKey('GH')->not->toHaveKey('TG')->not->toHaveKey('EU')
        ->and(Countries::exists('gh'))->toBeTrue()
        ->and(Countries::exists('ZZ'))->toBeFalse();
});

it('charges the fixed fee of the destination country', function () {
    $country = DeliveryCountry::factory()->create(['fee' => 45000]);

    expect(app(InternationalDeliveryPricing::class)->feeFor($country, collect()))->toBe(45000);
});
