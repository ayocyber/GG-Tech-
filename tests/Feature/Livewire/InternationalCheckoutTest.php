<?php

use App\Actions\PlaceOrder;
use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Enums\PaymentStatus;
use App\Livewire\CheckoutForm;
use App\Mail\NewOrderAlert;
use App\Mail\OrderReceived;
use App\Models\DeliveryCountry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Baskets\Cart;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
});

function internationalCheckout(string $countryCode = 'FR'): Testable
{
    return Livewire::test(CheckoutForm::class)
        ->set('customer_name', 'Kossi Mensah')
        ->set('email', 'kossi@example.com')
        ->set('phone', '+33 6 12 34 56 78')
        ->set('fulfilment', 'international')
        ->set('delivery_country', $countryCode)
        ->set('address', '12 rue de la Paix')
        ->set('city', 'Paris')
        ->set('region', 'Île-de-France')
        ->set('postal_code', '75002');
}

it('offers international delivery only once staff enabled a country', function () {
    $product = Product::factory()->international()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    Livewire::test(CheckoutForm::class)->assertDontSee('Livraison internationale');

    DeliveryCountry::factory()->create(['country_code' => 'FR']);

    Livewire::test(CheckoutForm::class)
        ->assertSee('Livraison internationale')
        ->set('fulfilment', 'international')
        ->assertSee('France')
        ->assertDontSee('Ghana');
});

it('places an international order with the fixed fee of the destination country', function () {
    DeliveryCountry::factory()->create(['country_code' => 'FR', 'fee' => 25000]);
    $product = Product::factory()->international()->priced(40000)->create();
    app(Cart::class)->add($product->variants->first(), 2);

    internationalCheckout()
        ->assertSee('France — 25')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirectContains('/thank-you');

    $order = Order::query()->sole();

    expect($order)
        ->fulfilment->toBe(FulfilmentMethod::International)
        ->delivery_country->toBe('FR')
        ->delivery_zone_id->toBeNull()
        ->subtotal->toBe(80000)
        ->delivery_fee->toBe(25000)
        ->total->toBe(105000)
        ->and($order->deliveryAddress())->toBe('12 rue de la Paix, 75002 Paris, Île-de-France, France');

    Mail::assertQueued(OrderReceived::class, fn (OrderReceived $mail) => str_contains($mail->render(), 'France'));
    Mail::assertQueued(NewOrderAlert::class, fn (NewOrderAlert $mail) => str_contains($mail->render(), 'Livraison internationale — France'));
});

it('requires a country, street address and city for international delivery', function () {
    DeliveryCountry::factory()->create(['country_code' => 'FR']);
    $product = Product::factory()->international()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    internationalCheckout()
        ->set('delivery_country', '')
        ->set('address', '')
        ->set('city', '')
        ->call('placeOrder')
        ->assertHasErrors(['delivery_country' => 'required', 'address' => 'required', 'city' => 'required']);

    expect(Order::query()->exists())->toBeFalse();
});

it('refuses a country that is not enabled for delivery', function (string $countryCode) {
    DeliveryCountry::factory()->create(['country_code' => 'FR']);
    DeliveryCountry::factory()->inactive()->create(['country_code' => 'GH']);
    $product = Product::factory()->international()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    internationalCheckout($countryCode)
        ->call('placeOrder')
        ->assertHasErrors(['delivery_country']);

    expect(Order::query()->exists())->toBeFalse();
})->with([
    'switched off by staff' => 'GH',
    'never added' => 'JP',
]);

it('refuses international delivery when no country is enabled', function () {
    $product = Product::factory()->international()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    internationalCheckout()
        ->call('placeOrder')
        ->assertHasErrors(['fulfilment']);
});

it('names the Togo-only products that block an international order', function () {
    DeliveryCountry::factory()->create(['country_code' => 'FR']);
    $togoOnly = Product::factory()->priced()->create(['name' => ['fr' => 'Photocopieur Canon', 'en' => 'Canon copier']]);
    $international = Product::factory()->international()->priced()->create();
    app(Cart::class)->add($togoOnly->variants->first());
    app(Cart::class)->add($international->variants->first());

    internationalCheckout()
        ->assertSee('Ces produits ne peuvent être livrés qu’au Togo')
        ->assertSee('Photocopieur Canon')
        ->assertDontSee('Choisissez un pays')
        ->call('placeOrder')
        ->assertHasErrors(['delivery_country']);

    expect(Order::query()->exists())->toBeFalse();
});

it('lets the customer remove a Togo-only product and send the rest abroad', function () {
    DeliveryCountry::factory()->create(['country_code' => 'FR', 'fee' => 25000]);
    $togoOnly = Product::factory()->priced(90000)->create();
    $international = Product::factory()->international()->priced(30000)->create();
    app(Cart::class)->add($togoOnly->variants->first());
    app(Cart::class)->add($international->variants->first());

    internationalCheckout()
        ->call('removeFromCart', $togoOnly->variants->first()->id)
        ->assertDispatched('basket-updated')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::query()->sole())
        ->subtotal->toBe(30000)
        ->total->toBe(55000);
});

it('lets the customer keep the cart and deliver in Togo instead', function () {
    DeliveryCountry::factory()->create(['country_code' => 'FR']);
    $togoOnly = Product::factory()->priced()->create();
    app(Cart::class)->add($togoOnly->variants->first());

    internationalCheckout()
        ->call('deliverInTogo')
        ->assertSet('fulfilment', FulfilmentMethod::Delivery->value)
        ->assertDontSee('Ces produits ne peuvent être livrés qu’au Togo');
});

it('blocks a Togo-only product abroad even when the form is bypassed', function () {
    DeliveryCountry::factory()->create(['country_code' => 'FR']);
    $togoOnly = Product::factory()->priced()->create();
    app(Cart::class)->add($togoOnly->variants->first());

    expect(fn () => app(PlaceOrder::class)->handle([
        'customer_name' => 'Kossi Mensah',
        'email' => 'kossi@example.com',
        'phone' => '+33 6 12 34 56 78',
        'fulfilment' => 'international',
        'delivery_country' => 'FR',
        'address' => '12 rue de la Paix',
        'city' => 'Paris',
    ], Market::Togo))->toThrow(ValidationException::class);

    expect(Order::query()->exists())->toBeFalse()
        ->and(app(Cart::class)->isEmpty())->toBeFalse();
});

it('charges the product total plus the country fee when paying online', function () {
    config(['store.online_payments_enabled' => true, 'services.paygate.mode' => 'demo']);
    DeliveryCountry::factory()->create(['country_code' => 'BJ', 'fee' => 8000]);
    $product = Product::factory()->international()->priced(45000)->create();
    app(Cart::class)->add($product->variants->first());

    $checkout = internationalCheckout('BJ')
        ->set('payment_option', CheckoutForm::PayOnline)
        ->assertSee('Payer 53')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->sole();

    expect($order)
        ->payment_status->toBe(PaymentStatus::Pending)
        ->currency->toBe('XOF')
        ->total->toBe(53000);

    $checkout->assertRedirect(route('payments.demo', ['order' => $order, 'key' => $order->paymentKey()]));
});

it('shows the destination country to staff', function () {
    $this->actingAs(User::factory()->create());
    $order = Order::factory()->international('FR')->create();

    $this->get("/admin/orders/{$order->number}")
        ->assertOk()
        ->assertSee('France')
        ->assertSee('75002');

    $this->get('/admin/orders')
        ->assertOk()
        ->assertSee('France');
});
