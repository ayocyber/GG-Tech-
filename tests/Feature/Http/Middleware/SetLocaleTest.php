<?php

use App\Models\Category;

it('renders the storefront in French by default', function () {
    $this->get('/fr')
        ->assertOk()
        ->assertSee('<html lang="fr">', escape: false)
        ->assertSee('Voir le catalogue');
});

it('renders the storefront in English under the en prefix', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('<html lang="en">', escape: false)
        ->assertSee('Browse the catalogue');
});

it('returns 404 for an unsupported language prefix', function () {
    $this->get('/de')->assertNotFound();
});

it('links to the same page and query in the other language', function () {
    Category::factory()->create(['slug' => 'toners']);

    $this->get('/fr/shop/toners?q=26a')
        ->assertOk()
        ->assertSee('href="'.e(url('/en/shop/toners?q=26a')).'"', escape: false);
});

it('sends the site root to the last language the visitor used', function () {
    $this->get('/en')->assertOk();

    $this->get('/')->assertRedirect('/en');
});
