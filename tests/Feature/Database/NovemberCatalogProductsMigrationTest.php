<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(CategorySeeder::class);
});

function runNovemberCatalogMigration(): void
{
    $migration = require database_path('migrations/2026_11_11_195550_add_november_2026_catalog_products.php');
    $migration->up();
}

it('adds the 41 products from catalogue pages 8 to 27 to an existing store', function () {
    $existing = Product::factory()->create();

    runNovemberCatalogMigration();

    expect(Product::query()->count())->toBe(42)
        ->and($existing->fresh())->not->toBeNull();

    $webcam = Product::query()->where('slug', 'logitech-c920-pro-hd-webcam')->firstOrFail();
    expect($webcam->status)->toBe(ProductStatus::Published)
        ->and($webcam->variants()->value('price_xof'))->toBe(60000);

    expect(Product::query()->where('slug', 'usb-2-0-flash-drive')->firstOrFail()->variants()->count())->toBe(6);
});

it('keeps the products the client still has to confirm as drafts', function () {
    Product::factory()->create();

    runNovemberCatalogMigration();

    expect(Product::query()->where('status', ProductStatus::Draft)->orderBy('slug')->pluck('slug')->all())->toBe([
        'apc-back-ups-650-va',
        'canon-c-exv-11-black-toner-cartridge-original',
        'hp-laserjet-pro-4003dn',
        'tp-link-tl-sf1008p-8-port-10-100-mbps-poe-switch',
        'tp-link-tl-sg1008p-8-port-gigabit-poe-switch',
    ]);
});

it('leaves products that already exist untouched', function () {
    $edited = Product::factory()->create([
        'slug' => 'logitech-c920-pro-hd-webcam',
        'name' => ['fr' => 'Nom modifié', 'en' => 'Edited name'],
    ]);

    runNovemberCatalogMigration();

    expect(Product::query()->count())->toBe(41)
        ->and($edited->fresh()->name['en'])->toBe('Edited name');
});

it('skips fresh installs, which get the products from the catalog seeder', function () {
    runNovemberCatalogMigration();

    expect(Product::query()->count())->toBe(0);
});
