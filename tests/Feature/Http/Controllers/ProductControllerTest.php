<?php

use App\Enums\TonerType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('labels a toner as original and links to its premium compatible version', function () {
    $original = Product::factory()->priced(45000)->tonerType(TonerType::Original)->create(['slug' => 'hp-26a-original']);
    Product::factory()->priced(10000)->tonerType(TonerType::PremiumCompatible)->create(['slug' => 'hp-26a-premium-compatible']);

    $this->get("/fr/products/{$original->slug}")
        ->assertOk()
        ->assertSee('Fabriqué par le constructeur de l’imprimante')
        ->assertSee('Aussi disponible en Compatible premium')
        ->assertSee("10\u{202F}000 FCFA")
        ->assertSee('/fr/products/hp-26a-premium-compatible');
});

it('does not call a premium compatible toner genuine', function () {
    $product = Product::factory()->priced()->tonerType(TonerType::PremiumCompatible)->create(['slug' => 'hp-12a-premium-compatible']);

    $this->get("/fr/products/{$product->slug}")
        ->assertOk()
        ->assertSee('Produit compatible premium, testé par notre équipe avant livraison.')
        ->assertDontSee('Produit authentique, vérifié')
        ->assertDontSee('Aussi disponible en');
});

it('redirects a toner link shared before the split to the original version', function () {
    Product::factory()->priced()->tonerType(TonerType::PremiumCompatible)->create(['slug' => 'hp-26a-black-toner-cartridge-premium-compatible']);
    Product::factory()->priced()->tonerType(TonerType::Original)->create(['slug' => 'hp-26a-black-toner-cartridge-original']);

    $this->get('/en/products/hp-26a-black-toner-cartridge')
        ->assertMovedPermanently()
        ->assertRedirect('/en/products/hp-26a-black-toner-cartridge-original');
});

it('redirects an old compatible-only toner link to its new address', function () {
    Product::factory()->priced()->tonerType(TonerType::PremiumCompatible)->create(['slug' => 'hp-12a-black-toner-cartridge-premium-compatible']);

    $this->get('/fr/products/hp-12a-black-toner-cartridge')
        ->assertRedirect('/fr/products/hp-12a-black-toner-cartridge-premium-compatible');
});

it('still returns 404 for an unknown product', function () {
    $this->get('/fr/products/does-not-exist')->assertNotFound();
});

it('lets customers browse every photo of a product', function () {
    Storage::fake('local');
    Storage::fake('public');
    $product = Product::factory()->priced()->create();

    foreach (range(1, 3) as $position) {
        Storage::disk('local')->put("products/originals/{$product->id}/photo-{$position}.jpg", UploadedFile::fake()->image('photo.jpg', 600, 600)->getContent());
        $product->images()->create(['original_path' => "products/originals/{$product->id}/photo-{$position}.jpg", 'position' => $position]);
    }

    $this->get("/fr/products/{$product->slug}")
        ->assertOk()
        ->assertSee('aria-roledescription="carousel"', escape: false)
        ->assertSee('/ 3')
        ->assertSee('Photo 3 sur 3')
        ->assertSee('Photo suivante')
        ->assertSee('Photo précédente');
});

it('shows a single photo without carousel controls', function () {
    Storage::fake('local');
    Storage::fake('public');
    $product = Product::factory()->priced()->create();
    Storage::disk('local')->put("products/originals/{$product->id}/photo.jpg", UploadedFile::fake()->image('photo.jpg', 600, 600)->getContent());
    $product->images()->create(['original_path' => "products/originals/{$product->id}/photo.jpg"]);

    $this->get("/fr/products/{$product->slug}")
        ->assertOk()
        ->assertDontSee('Photo suivante');
});
