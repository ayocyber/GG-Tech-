<?php

use App\Enums\TonerType;
use App\Livewire\ShopCatalog;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

it('lists published products and hides drafts', function () {
    Product::factory()->priced()->create(['name' => ['fr' => 'Routeur visible', 'en' => 'Visible router']]);
    Product::factory()->priced()->draft()->create(['name' => ['fr' => 'Routeur brouillon', 'en' => 'Draft router']]);

    $this->get('/fr/shop')
        ->assertOk()
        ->assertSee('Routeur visible')
        ->assertDontSee('Routeur brouillon');
});

it('shows only the products of the chosen category', function () {
    $toners = Category::factory()->create(['slug' => 'toners']);
    Product::factory()->priced()->for($toners)->create(['name' => ['fr' => 'Toner 26A', 'en' => 'Toner 26A']]);
    Product::factory()->priced()->create(['name' => ['fr' => 'Switch 8 ports', 'en' => '8-port switch']]);

    $this->get('/fr/shop/toners')
        ->assertOk()
        ->assertSee('Toner 26A')
        ->assertDontSee('Switch 8 ports');
});

it('returns 404 for a hidden category', function () {
    Category::factory()->inactive()->create(['slug' => 'archive']);

    $this->get('/fr/shop/archive')->assertNotFound();
});

it('finds toners by compatible printer model', function () {
    Product::factory()->priced()->create([
        'name' => ['fr' => 'Cartouche HP 26A', 'en' => 'HP 26A cartridge'],
        'compatibility' => 'HP LaserJet Pro M402 series',
    ]);
    Product::factory()->priced()->create(['name' => ['fr' => 'Cartouche HP 85A', 'en' => 'HP 85A cartridge']]);

    $this->get('/fr/shop?q=m402')
        ->assertOk()
        ->assertSee('Cartouche HP 26A')
        ->assertDontSee('Cartouche HP 85A');
});

it('shows the toner type on product cards', function () {
    Product::factory()->priced()->tonerType(TonerType::Original)->create(['name' => ['fr' => 'Toner 26A', 'en' => 'Toner 26A']]);
    Product::factory()->priced()->tonerType(TonerType::PremiumCompatible)->create(['name' => ['fr' => 'Toner 12A', 'en' => 'Toner 12A']]);

    app()->setLocale('en');

    Livewire::test(ShopCatalog::class)
        ->assertSeeInOrder(['Original', 'Toner 26A', 'Premium compatible', 'Toner 12A']);
});

it('sends the old toners category to original toners', function () {
    Category::factory()->create(['slug' => 'original-toners']);

    $this->get('/fr/shop/toners')
        ->assertMovedPermanently()
        ->assertRedirect('/fr/shop/original-toners');
});

it('keeps searches from the old toners category across both toner types', function () {
    $this->get('/fr/shop/toners?q=26a')->assertRedirect('/fr/shop?q=26a');
});

it('returns 404 for an unknown category', function () {
    $this->get('/fr/shop/does-not-exist')->assertNotFound();
});
