<?php

use App\Enums\Market;
use App\Livewire\CheckoutForm;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Services\Baskets\Cart;
use App\Services\Currency\DisplayCurrency;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Currency::query()->where('code', 'USD')->update(['auto_rate' => 600, 'auto_source' => Currency::SourceProvider, 'auto_updated_at' => now()]);
});

function euros(float $amount, string $locale = 'fr'): string
{
    return (new NumberFormatter($locale, NumberFormatter::CURRENCY))->formatCurrency($amount, 'EUR');
}

it('shows prices in FCFA until the visitor picks another currency', function () {
    $product = Product::factory()->priced(45000)->create();

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee(Market::Togo->format(45000))
        ->assertDontSee(euros(68.60));
});

it('converts product, cart and checkout prices once the visitor picks euros', function () {
    $product = Product::factory()->priced(45000)->create();

    $this->from(route('products.show', $product))
        ->post(route('currency.switch'), ['currency' => 'EUR'])
        ->assertRedirect(route('products.show', $product))
        ->assertCookie(DisplayCurrency::CookieName, 'EUR');

    $this->get(route('products.show', $product))->assertSee(euros(68.60));

    app(Cart::class)->add($product->variants->first(), 2);

    $this->get(route('cart'))->assertSee(euros(137.20));
    Livewire::test(CheckoutForm::class)->assertSee(euros(137.20));
});

it('keeps the cart when the currency changes', function () {
    $product = Product::factory()->priced(45000)->create();
    app(Cart::class)->add($product->variants->first(), 3);

    $this->post(route('currency.switch'), ['currency' => 'USD']);

    expect(app(Cart::class)->count())->toBe(3);
});

it('refuses a currency that is switched off, has no rate or does not exist', function (string $code) {
    Currency::factory()->disabled()->create(['code' => 'GBP']);
    Currency::factory()->withoutRate()->create(['code' => 'CHF']);

    $this->post(route('currency.switch'), ['currency' => $code])
        ->assertSessionHasErrors('currency');

    expect(session(DisplayCurrency::SessionKey))->toBeNull();
})->with(['switched off' => 'GBP', 'no rate yet' => 'CHF', 'unknown' => 'ABC']);

it('falls back to FCFA when staff switch off the chosen currency', function () {
    $product = Product::factory()->priced(45000)->create();
    $this->post(route('currency.switch'), ['currency' => 'EUR']);

    Currency::query()->where('code', 'EUR')->sole()->update(['is_enabled' => false]);

    $this->get(route('products.show', $product))
        ->assertSee(Market::Togo->format(45000))
        ->assertDontSee(euros(68.60));
});

it('remembers the currency of a returning visitor', function () {
    $product = Product::factory()->priced(45000)->create();

    $this->withCookie(DisplayCurrency::CookieName, 'EUR')
        ->get(route('products.show', $product))
        ->assertSee(euros(68.60));
});

it('suggests a currency only until the visitor has chosen one', function () {
    $this->get('/fr')->assertSee('ggSuggestCurrency', escape: false);

    $this->post(route('currency.switch'), ['currency' => 'XOF']);

    $this->get('/fr')->assertDontSee('ggSuggestCurrency', escape: false);
});

it('rounds each currency the way it is written', function () {
    Currency::factory()->create(['code' => 'JPY', 'auto_rate' => 4.2]);
    $currency = app(DisplayCurrency::class);

    expect($currency->convert(10000, 'JPY'))->toBe(2381.0)
        ->and($currency->convert(45000, 'EUR'))->toBe(68.6)
        ->and($currency->convert(45000, 'XOF'))->toBe(45000.0);
});

it('uses the manual rate staff entered over the automatic one', function () {
    Currency::query()->where('code', 'USD')->update(['manual_rate' => 500, 'manual_updated_at' => now()]);
    $currency = app(DisplayCurrency::class);

    expect($currency->convert(50000, 'USD'))->toBe(100.0);
});

it('shows the amount charged in FCFA at checkout and still charges FCFA', function () {
    Mail::fake();
    config(['store.online_payments_enabled' => true, 'services.paygate.mode' => 'demo']);
    session([DisplayCurrency::SessionKey => 'EUR']);
    $product = Product::factory()->priced(45000)->create();
    app(Cart::class)->add($product->variants->first());

    Livewire::test(CheckoutForm::class)
        ->assertSee('Montant facturé')
        ->assertSee(Market::Togo->format(45000))
        ->assertSee('Payer 45')
        ->set('customer_name', 'Kossi Mensah')
        ->set('email', 'kossi@example.com')
        ->set('phone', '+33 6 12 34 56 78')
        ->set('fulfilment', 'pickup')
        ->set('payment_option', CheckoutForm::PayLater)
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::query()->sole())
        ->currency->toBe('XOF')
        ->total->toBe(45000);
});
