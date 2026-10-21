<?php

use App\Livewire\ProductPurchase;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Baskets\Cart;
use App\Services\Baskets\QuoteBasket;
use Livewire\Livewire;

it('adds the selected option and quantity to the cart', function () {
    $product = Product::factory()
        ->has(ProductVariant::factory()->labelled('6U')->state(['position' => 0]), 'variants')
        ->has(ProductVariant::factory()->labelled('9U')->state(['position' => 1]), 'variants')
        ->create();
    $nineU = $product->variants()->where('position', 1)->first();

    Livewire::test(ProductPurchase::class, ['product' => $product->load('variants')])
        ->call('selectVariant', $nineU->id)
        ->set('quantity', 3)
        ->call('addToCart')
        ->assertDispatched('basket-updated')
        ->assertSee('Ajouté à votre panier.');

    expect(app(Cart::class)->quantities())->toBe([$nineU->id => 3]);
});

it('starts on the first option that is in stock', function () {
    $product = Product::factory()
        ->has(ProductVariant::factory()->outOfStock()->state(['position' => 0]), 'variants')
        ->has(ProductVariant::factory()->state(['position' => 1]), 'variants')
        ->create();

    Livewire::test(ProductPurchase::class, ['product' => $product->load('variants')])
        ->assertSet('variantId', $product->variants()->where('position', 1)->value('id'));
});

it('refuses to add an out-of-stock option to the cart', function () {
    $product = Product::factory()->has(ProductVariant::factory()->outOfStock(), 'variants')->create();

    Livewire::test(ProductPurchase::class, ['product' => $product->load('variants')])
        ->call('addToCart')
        ->assertHasErrors('cart');

    expect(app(Cart::class)->isEmpty())->toBeTrue();
});

it('adds price-on-request products to the quote basket instead of the cart', function () {
    $product = Product::factory()->priceOnRequest()->create();

    Livewire::test(ProductPurchase::class, ['product' => $product->load('variants')])
        ->call('addToCart')
        ->assertHasErrors('cart')
        ->call('addToQuote')
        ->assertSee('Ajouté à votre demande de devis.');

    expect(app(Cart::class)->isEmpty())->toBeTrue()
        ->and(app(QuoteBasket::class)->count())->toBe(1);
});
