<?php

use App\Livewire\QuickAdd;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Baskets\Cart;
use Livewire\Livewire;

function productWithOptions(): Product
{
    return Product::factory()
        ->has(ProductVariant::factory()->labelled('6U')->outOfStock()->state(['position' => 0]), 'variants')
        ->has(ProductVariant::factory()->labelled('9U')->state(['position' => 1, 'price_xof' => 90000]), 'variants')
        ->has(ProductVariant::factory()->labelled('12U')->state(['position' => 2, 'price_xof' => 120000]), 'variants')
        ->create();
}

it('adds a single-option product straight to the cart', function () {
    $product = Product::factory()->priced()->create();

    Livewire::test(QuickAdd::class)
        ->dispatch('quick-add', productId: $product->id)
        ->assertDispatched('basket-updated')
        ->assertDispatched('quick-add-notice', wasAdded: true)
        ->assertDispatched('quick-add-finished')
        ->assertSet('productId', null);

    expect(app(Cart::class)->quantities())->toBe([$product->variants()->value('id') => 1]);
});

it('asks for an option first when the product has a choice, starting on one that can be bought', function () {
    $product = productWithOptions();
    $nineU = $product->variants()->where('position', 1)->value('id');

    Livewire::test(QuickAdd::class)
        ->dispatch('quick-add', productId: $product->id)
        ->assertSet('variantId', $nineU)
        ->assertSee('Choisissez une option')
        ->assertDispatched('quick-add-finished')
        ->assertNotDispatched('basket-updated');

    expect(app(Cart::class)->isEmpty())->toBeTrue();
});

it('adds the chosen option and quantity from the picker, then closes it', function () {
    $product = productWithOptions();
    $twelveU = $product->variants()->where('position', 2)->value('id');

    Livewire::test(QuickAdd::class)
        ->dispatch('quick-add', productId: $product->id)
        ->call('selectVariant', $twelveU)
        ->set('quantity', 3)
        ->call('addToCart')
        ->assertDispatched('basket-updated')
        ->assertDispatched('quick-add-notice', wasAdded: true)
        ->assertSet('productId', null)
        ->assertDontSee('Choisissez une option');

    expect(app(Cart::class)->quantities())->toBe([$twelveU => 3]);
});

it('refuses an out-of-stock option chosen in the picker', function () {
    $product = productWithOptions();

    Livewire::test(QuickAdd::class)
        ->dispatch('quick-add', productId: $product->id)
        ->call('selectVariant', $product->variants()->where('position', 0)->value('id'))
        ->call('addToCart')
        ->assertHasErrors('cart')
        ->assertNotDispatched('basket-updated');

    expect(app(Cart::class)->isEmpty())->toBeTrue();
});

it('ignores an option that belongs to another product', function () {
    $product = productWithOptions();
    $nineU = $product->variants()->where('position', 1)->value('id');
    $otherProductVariant = Product::factory()->priced()->create()->variants()->value('id');

    Livewire::test(QuickAdd::class)
        ->dispatch('quick-add', productId: $product->id)
        ->call('selectVariant', $otherProductVariant)
        ->assertSet('variantId', $nineU);
});

it('refuses products that cannot be bought online and tells the customer', function (Closure $makeProduct) {
    $product = $makeProduct();

    Livewire::test(QuickAdd::class)
        ->dispatch('quick-add', productId: $product->id)
        ->assertDispatched('quick-add-notice', wasAdded: false)
        ->assertDispatched('quick-add-finished')
        ->assertNotDispatched('basket-updated')
        ->assertSet('productId', null);

    expect(app(Cart::class)->isEmpty())->toBeTrue();
})->with([
    'out of stock' => fn () => Product::factory()->has(ProductVariant::factory()->outOfStock(), 'variants')->create(),
    'price on request' => fn () => Product::factory()->priceOnRequest()->create(),
    'unpublished' => fn () => Product::factory()->draft()->priced()->create(),
]);

it('shows the right action on each product card', function (Closure $makeProduct, string $expectedAction, array $absentActions) {
    $makeProduct();

    $this->get(route('shop'))
        ->assertOk()
        ->assertSee($expectedAction)
        ->assertDontSee($absentActions);
})->with([
    'one option' => [
        fn () => Product::factory()->priced()->create(),
        'Ajouter au panier',
        ['Choisir les options', 'Voir le produit'],
    ],
    'several options' => [
        fn () => productWithOptions(),
        'Choisir les options',
        ['Ajouter au panier', 'Voir le produit'],
    ],
    'price on request' => [
        fn () => Product::factory()->priceOnRequest()->create(),
        'Voir le produit',
        ['Ajouter au panier', 'Choisir les options'],
    ],
]);
