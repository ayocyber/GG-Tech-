<?php

use App\Models\DeliveryCountry;
use App\Models\Product;

it('only talks about delivery in Togo while no country is enabled', function () {
    DeliveryCountry::factory()->inactive()->create(['country_code' => 'FR']);
    $product = Product::factory()->international()->priced()->create();

    $this->get('/fr')
        ->assertSee('Livraison partout au Togo · Retrait gratuit en boutique')
        ->assertDontSee('dans certains pays');

    $this->get('/fr/store')
        ->assertDontSee('Nous livrons à l’étranger uniquement');

    $this->get(route('products.show', $product))
        ->assertDontSee('Livrable aussi à l’étranger');
});

it('announces international delivery to selected countries once one is enabled', function () {
    DeliveryCountry::factory()->create(['country_code' => 'FR', 'fee' => 25000, 'conditions' => ['fr' => 'Droits de douane à la charge du client.', 'en' => 'Customs duties paid by the customer.']]);
    DeliveryCountry::factory()->inactive()->create(['country_code' => 'GH']);

    $this->get('/fr')
        ->assertSee('Livraison partout au Togo et dans certains pays')
        ->assertSee('Partout au Togo, et dans certains pays');

    $this->get('/fr/store')
        ->assertSee('Nous livrons à l’étranger uniquement dans les pays ci-dessous')
        ->assertSee('France')
        ->assertSee('Droits de douane à la charge du client.')
        ->assertDontSee('Ghana');
});

it('tells customers on the product page whether it can be sent abroad', function () {
    DeliveryCountry::factory()->create(['country_code' => 'BJ']);
    $international = Product::factory()->international()->priced()->create();
    $togoOnly = Product::factory()->priced()->create();

    $this->get(route('products.show', $international))
        ->assertSee('Livrable aussi à l’étranger dans certains pays.')
        ->assertSee('Livraison internationale vers : Bénin');

    $this->get(route('products.show', $togoOnly))
        ->assertSee('Vendu pour une livraison au Togo uniquement.')
        ->assertSee('il ne peut pas être envoyé à l’étranger');
});
