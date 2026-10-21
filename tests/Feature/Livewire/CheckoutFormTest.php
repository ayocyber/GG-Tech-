<?php

use App\Enums\FulfilmentMethod;
use App\Livewire\CheckoutForm;
use App\Mail\NewOrderAlert;
use App\Mail\OrderReceived;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Services\Baskets\Cart;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function fillCustomer(Testable $checkout): Testable
{
    return $checkout
        ->set('customer_name', 'Ama Koffi')
        ->set('email', 'ama@example.com')
        ->set('phone', '+228 90 12 34 56');
}

it('redirects to the cart when the cart is empty', function () {
    $this->get('/fr/checkout')->assertRedirect('/fr/cart');
});

it('places a free in-store pickup order and empties the cart', function () {
    Mail::fake();
    $product = Product::factory()->priced(45000)->create(['name' => ['fr' => 'Toner HP 26A', 'en' => 'HP 26A toner']]);
    app(Cart::class)->add($product->variants->first(), 2);

    fillCustomer(Livewire::test(CheckoutForm::class))
        ->set('fulfilment', 'pickup')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->with('items')->sole();

    expect($order)
        ->number->toBe('GG-'.($order->id + 1000))
        ->fulfilment->toBe(FulfilmentMethod::Pickup)
        ->subtotal->toBe(90000)
        ->delivery_fee->toBe(0)
        ->total->toBe(90000)
        ->and($order->items->sole())
        ->product_name->toBe('Toner HP 26A')
        ->quantity->toBe(2)
        ->and(app(Cart::class)->isEmpty())->toBeTrue();

    Mail::assertQueued(OrderReceived::class, fn (OrderReceived $mail) => $mail->hasTo('ama@example.com'));
    Mail::assertQueued(NewOrderAlert::class, fn (NewOrderAlert $mail) => $mail->hasTo(config('store.email')));
});

it('adds the delivery zone fee to the total', function () {
    Mail::fake();
    $zone = DeliveryZone::factory()->create(['fee' => 2000]);
    $product = Product::factory()->priced(30000)->create();
    app(Cart::class)->add($product->variants->first());

    fillCustomer(Livewire::test(CheckoutForm::class))
        ->set('fulfilment', 'delivery')
        ->set('delivery_zone_id', $zone->id)
        ->set('address', 'Rue 12, maison bleue')
        ->set('city', 'Tokoin')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::query()->sole())
        ->delivery_fee->toBe(2000)
        ->total->toBe(32000)
        ->city->toBe('Tokoin');
});

it('leaves the delivery fee for staff to confirm when the cart has a heavy item', function () {
    Mail::fake();
    $zone = DeliveryZone::factory()->create(['fee' => 2000]);
    $copier = Product::factory()->heavy()->priced(1250000)->create();
    app(Cart::class)->add($copier->variants->first());

    fillCustomer(Livewire::test(CheckoutForm::class))
        ->set('fulfilment', 'delivery')
        ->set('delivery_zone_id', $zone->id)
        ->set('address', 'Zone portuaire')
        ->set('city', 'Lomé')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::query()->sole())
        ->delivery_fee->toBeNull()
        ->total->toBe(1250000);
});

it('requires a zone, address and city for delivery', function () {
    $product = Product::factory()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    fillCustomer(Livewire::test(CheckoutForm::class))
        ->set('fulfilment', 'delivery')
        ->call('placeOrder')
        ->assertHasErrors(['delivery_zone_id' => 'required', 'address' => 'required', 'city' => 'required'])
        ->assertSee('Le champ zone de livraison est obligatoire.');

    expect(Order::query()->count())->toBe(0);
});

it('rejects a delivery zone that staff switched off', function () {
    $closedZone = DeliveryZone::factory()->inactive()->create();
    $product = Product::factory()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    fillCustomer(Livewire::test(CheckoutForm::class))
        ->set('fulfilment', 'delivery')
        ->set('delivery_zone_id', $closedZone->id)
        ->set('address', 'Rue 1')
        ->set('city', 'Lomé')
        ->call('placeOrder')
        ->assertHasErrors(['delivery_zone_id' => 'exists']);
});

it('starts checkout on free in-store pickup', function () {
    $product = Product::factory()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    Livewire::test(CheckoutForm::class)
        ->assertSet('fulfilment', 'pickup')
        ->assertSet('phone', '+228 ');
});

it('rejects a phone number that is too short', function () {
    $product = Product::factory()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    Livewire::test(CheckoutForm::class)
        ->set('customer_name', 'Ama Koffi')
        ->set('email', 'ama@example.com')
        ->set('phone', '1234')
        ->set('fulfilment', 'pickup')
        ->call('placeOrder')
        ->assertHasErrors(['phone' => 'regex']);
});
