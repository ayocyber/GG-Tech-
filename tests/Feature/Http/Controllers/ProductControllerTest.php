<?php

use App\Models\Product;
use App\Models\ProductVariant;

it('renders a published product with its price in FCFA', function () {
    $product = Product::factory()->priced(125000)->create(['name' => ['fr' => 'Imprimante laser', 'en' => 'Laser printer']]);

    $this->get("/fr/products/{$product->slug}")
        ->assertOk()
        ->assertSee('Imprimante laser')
        ->assertSee("125\u{202F}000 FCFA");
});

it('returns 404 for a draft product', function () {
    $product = Product::factory()->priced()->draft()->create();

    $this->get("/fr/products/{$product->slug}")->assertNotFound();
});

it('offers a WhatsApp price request instead of a price for price-on-request products', function () {
    $product = Product::factory()->priceOnRequest()->create();

    $this->get("/fr/products/{$product->slug}")
        ->assertOk()
        ->assertSee('Prix sur demande')
        ->assertSee('Demander le prix sur WhatsApp')
        ->assertDontSee('Ajouter au panier');
});

it('lists every option with its own price', function () {
    $product = Product::factory()
        ->has(ProductVariant::factory()->labelled('4U')->state(['price_xof' => 50000, 'position' => 0]), 'variants')
        ->has(ProductVariant::factory()->labelled('22U')->state(['price_xof' => 140000, 'position' => 1]), 'variants')
        ->create();

    $this->get("/fr/products/{$product->slug}")
        ->assertOk()
        ->assertSeeInOrder(['4U', "50\u{202F}000 FCFA", '22U', "140\u{202F}000 FCFA"]);
});
