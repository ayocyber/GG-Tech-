<?php

use App\Enums\PaymentStatus;
use App\Livewire\CheckoutForm;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Services\Baskets\Cart;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    config(['store.online_payments_enabled' => true, 'services.paygate.mode' => 'demo']);
    Mail::fake();
});

function onlineCheckout(): Testable
{
    return Livewire::test(CheckoutForm::class)
        ->set('customer_name', 'Ama Koffi')
        ->set('email', 'ama@example.com')
        ->set('phone', '+228 90 12 34 56');
}

it('offers online payment first when it is enabled', function () {
    $product = Product::factory()->priced(45000)->create();
    app(Cart::class)->add($product->variants->first());

    Livewire::test(CheckoutForm::class)
        ->assertSet('payment_option', CheckoutForm::PayOnline)
        ->assertSee('Payer maintenant avec Flooz ou T-Money')
        ->assertSee('Payer 45');
});

it('sends an online order to the payment page without announcing it yet', function () {
    $product = Product::factory()->priced(45000)->create();
    app(Cart::class)->add($product->variants->first(), 2);

    $checkout = onlineCheckout()
        ->set('fulfilment', 'pickup')
        ->set('payment_option', CheckoutForm::PayOnline)
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->sole();

    expect($order)
        ->payment_status->toBe(PaymentStatus::Pending)
        ->payment_identifier->toStartWith($order->number.'-')
        ->total->toBe(90000)
        ->and(app(Cart::class)->isEmpty())->toBeTrue();

    $checkout->assertRedirect(route('payments.demo', ['order' => $order, 'key' => $order->paymentKey()]));
    Mail::assertNothingQueued();
});

it('keeps paying at pickup available alongside online payment', function () {
    $product = Product::factory()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    onlineCheckout()
        ->set('fulfilment', 'pickup')
        ->set('payment_option', CheckoutForm::PayLater)
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirectContains('/thank-you');

    expect(Order::query()->sole()->paysOnline())->toBeFalse();
});

it('falls back to paying on delivery when the delivery fee still has to be confirmed', function () {
    $zone = DeliveryZone::factory()->quoted()->create();
    $product = Product::factory()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    onlineCheckout()
        ->set('fulfilment', 'delivery')
        ->set('delivery_zone_id', $zone->id)
        ->assertSet('payment_option', CheckoutForm::PayLater);
});

it('never takes online payment for a delivery our team still has to price', function () {
    $zone = DeliveryZone::factory()->quoted()->create();
    $product = Product::factory()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    onlineCheckout()
        ->set('fulfilment', 'delivery')
        ->set('delivery_zone_id', $zone->id)
        ->set('address', 'Rue 12')
        ->set('city', 'Tokoin')
        ->set('payment_option', CheckoutForm::PayOnline)
        ->assertSet('payment_option', CheckoutForm::PayLater)
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirectContains('/thank-you');

    expect(Order::query()->sole()->paysOnline())->toBeFalse();
});

it('refuses online payment while it is switched off', function () {
    config(['store.online_payments_enabled' => false]);
    $product = Product::factory()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    onlineCheckout()
        ->set('fulfilment', 'pickup')
        ->set('payment_option', CheckoutForm::PayOnline)
        ->call('placeOrder')
        ->assertHasErrors('payment_option');

    expect(Order::query()->count())->toBe(0);
});
