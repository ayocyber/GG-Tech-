<?php

use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds the products from catalogue pages 8–27 (new products and the October 2026 catalogue update)
 * that were missing from the first import.
 *
 * Only products that do not exist yet are created, so edits made in the admin are kept.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fresh installs get every product from CatalogSeeder.
        if (Product::query()->doesntExist()) {
            return;
        }

        $existingSlugs = Product::query()->pluck('slug')->flip();

        $entries = collect(CatalogSeeder::entries())
            ->filter(fn (array $entry): bool => ($entry['added'] ?? null) === '2026-11' && ! $existingSlugs->has($entry['slug']))
            ->values()
            ->all();

        (new CatalogSeeder)->import($entries);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = collect(CatalogSeeder::entries())
            ->filter(fn (array $entry): bool => ($entry['added'] ?? null) === '2026-11')
            ->pluck('slug');

        Product::query()->whereIn('slug', $slugs)->get()->each->delete();
    }
};
