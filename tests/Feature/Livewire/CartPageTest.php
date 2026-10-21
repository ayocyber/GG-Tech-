<?php

use App\Livewire\CartPage;
use App\Models\Product;
use App\Services\Baskets\Cart;
use App\Services\Baskets\QuoteBasket;
use Livewire\Livewire;

it('shows the subtotal of every line', function () {
    $printer = Product::factory()->priced(180000)->create();
    $toner = Product::factory()->priced(35000)->create();
    app(Cart::class)->add($printer->variants->first(), 1);
    app(Cart::class)->add($toner->variants->first(), 2);

    Livewire::test(CartPage::class)->assertSee("250\u{202F}000 FCFA");
});

it('removes a line when its quantity is set to zero', function () {
    $product = Product::factory()->priced()->create();
    $variant = $product->variants->first();
    app(Cart::class)->add($variant);

    Livewire::test(CartPage::class)
        ->call('updateQuantity', $variant->id, 0)
        ->assertDispatched('basket-updated')
        ->assertSee('Votre panier est vide.');
});

it('leaves out products that were unpublished after being added', function () {
    $product = Product::factory()->priced()->create(['name' => ['fr' => 'Article retiré', 'en' => 'Withdrawn item']]);
    app(Cart::class)->add($product->variants->first());
    $product->update(['status' => 'draft']);

    Livewire::test(CartPage::class)
        ->assertDontSee('Article retiré')
        ->assertSee('Votre panier est vide.');
});

it('turns the cart into a quote request', function () {
    $product = Product::factory()->priced()->create();
    $variant = $product->variants->first();
    app(Cart::class)->add($variant, 12);

    Livewire::test(CartPage::class)
        ->call('convertToQuote')
        ->assertRedirect(route('quote'));

    expect(app(Cart::class)->isEmpty())->toBeTrue()
        ->and(app(QuoteBasket::class)->quantities())->toBe([$variant->id => 12]);
});
