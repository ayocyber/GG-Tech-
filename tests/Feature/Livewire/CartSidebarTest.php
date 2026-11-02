<?php

use App\Livewire\CartPage;
use App\Livewire\CartSidebar;
use App\Livewire\CheckoutForm;
use App\Models\Product;
use App\Services\Baskets\Cart;
use Livewire\Livewire;

it('shows the name, price and quantity of every line', function () {
    $product = Product::factory()->priced(45000)->create(['name' => ['fr' => 'Toner HP 26A', 'en' => 'HP 26A toner']]);
    app(Cart::class)->add($product->variants->first(), 2);

    Livewire::test(CartSidebar::class)
        ->assertSee('Toner HP 26A')
        ->assertSee("45\u{202F}000 FCFA")
        ->assertSee("90\u{202F}000 FCFA")
        ->assertSee('Votre panier (2)')
        ->assertSee(route('cart'))
        ->assertDontSee('Votre panier est vide.');
});

it('shows a clear message when the cart is empty', function () {
    Livewire::test(CartSidebar::class)
        ->assertSee('Votre panier est vide.')
        ->assertDontSee(route('checkout'));
});

it('leaves out products that were unpublished after being added', function () {
    $product = Product::factory()->priced()->create(['name' => ['fr' => 'Article retiré', 'en' => 'Withdrawn item']]);
    app(Cart::class)->add($product->variants->first());
    $product->update(['status' => 'draft']);

    Livewire::test(CartSidebar::class)
        ->assertDontSee('Article retiré')
        ->assertSee('Votre panier est vide.');
});

it('increases and decreases the quantity of a line', function () {
    $variant = Product::factory()->priced(10000)->create()->variants->first();
    app(Cart::class)->add($variant, 2);

    $sidebar = Livewire::test(CartSidebar::class)
        ->call('increment', $variant->id)
        ->assertDispatched('basket-updated')
        ->assertSee('Votre panier (3)');

    expect(app(Cart::class)->quantities())->toBe([$variant->id => 3]);

    $sidebar->call('decrement', $variant->id)->assertSee('Votre panier (2)');

    expect(app(Cart::class)->quantities())->toBe([$variant->id => 2]);
});

it('keeps a line at quantity one when decreasing', function () {
    $variant = Product::factory()->priced()->create()->variants->first();
    app(Cart::class)->add($variant);

    Livewire::test(CartSidebar::class)->call('decrement', $variant->id);

    expect(app(Cart::class)->quantities())->toBe([$variant->id => 1]);
});

it('does not add a product that is not in the cart when adjusting quantities', function () {
    $variant = Product::factory()->priced()->create()->variants->first();

    Livewire::test(CartSidebar::class)->call('increment', $variant->id);

    expect(app(Cart::class)->isEmpty())->toBeTrue();
});

it('removes a line and falls back to the empty message', function () {
    $variant = Product::factory()->priced()->create()->variants->first();
    app(Cart::class)->add($variant);

    Livewire::test(CartSidebar::class)
        ->call('remove', $variant->id)
        ->assertDispatched('basket-updated')
        ->assertSee('Votre panier est vide.');

    expect(app(Cart::class)->isEmpty())->toBeTrue();
});

it('updates when a product is added from elsewhere on the site', function () {
    $product = Product::factory()->priced()->create(['name' => ['fr' => 'Onduleur APC', 'en' => 'APC UPS']]);
    $sidebar = Livewire::test(CartSidebar::class)->assertDontSee('Onduleur APC');

    app(Cart::class)->add($product->variants->first());

    $sidebar->dispatch('basket-updated')->assertSee('Onduleur APC');
});

it('keeps the full cart page in step with changes made in the sidebar', function () {
    $product = Product::factory()->priced(10000)->create();
    $cartPage = Livewire::test(CartPage::class)->assertSee('Votre panier est vide.');

    app(Cart::class)->add($product->variants->first(), 2);

    $cartPage->dispatch('basket-updated')->assertSee("20\u{202F}000 FCFA");
});

it('sends the customer back to the cart when the sidebar empties it during checkout', function () {
    $variant = Product::factory()->priced()->create()->variants->first();
    app(Cart::class)->add($variant);
    $checkout = Livewire::test(CheckoutForm::class);

    app(Cart::class)->remove($variant->id);

    $checkout->dispatch('basket-updated')->assertRedirect(route('cart'));
});

it('sends the customer to the cart instead of failing when placing an order with an empty cart', function () {
    Livewire::test(CheckoutForm::class)
        ->call('placeOrder')
        ->assertRedirect(route('cart'));
});

it('opens the sidebar from the cart icon while keeping the link to the full cart', function () {
    $this->get(route('shop'))
        ->assertOk()
        ->assertSee('open-cart-sidebar', false)
        ->assertSee('Votre panier (0)');
});
