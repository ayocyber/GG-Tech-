<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imports database/data/catalog.json, built from the client's October 2026 catalogue workbook.
 *
 * Photos are read from storage/app/private/catalog-import/photos when present.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = json_decode(file_get_contents(database_path('data/catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        $categories = Category::query()->pluck('id', 'slug');
        $photoDirectory = 'catalog-import/photos';

        $this->command?->getOutput()->progressStart(count($catalog));

        foreach ($catalog as $entry) {
            $brand = $entry['brand']
                ? Brand::query()->firstOrCreate(['slug' => Str::slug($entry['brand'])], ['name' => $entry['brand']])
                : null;

            $product = Product::query()->updateOrCreate(['slug' => $entry['slug']], [
                'category_id' => $categories[$entry['category']],
                'brand_id' => $brand?->id,
                'name' => $entry['name'],
                'short_description' => $entry['short_description'],
                'description' => $entry['description'],
                'model' => $entry['model'],
                'compatibility' => $entry['compatibility'],
                'price_on_request' => $entry['price_on_request'],
                'is_heavy' => (bool) preg_match('/copier|imagerunner|rack|\bups\b/i', $entry['name']['en']),
                'is_featured' => $entry['featured'],
                'needs_review' => $entry['needs_review'],
                'status' => str_contains($entry['name']['en'], 'to confirm') ? ProductStatus::Draft : ProductStatus::Published,
                'catalog_page' => $entry['page'],
            ]);

            $product->variants()->delete();
            $product->variants()->createMany(collect($entry['variants'])->map(fn (array $variant, int $position) => [
                'label' => $variant['label'],
                'price_xof' => $variant['price_xof'],
                'in_stock' => true,
                'position' => $position,
            ])->all());

            if ($product->images()->doesntExist()) {
                foreach ($entry['photos'] as $position => $photo) {
                    if (! Storage::disk('local')->exists("{$photoDirectory}/{$photo}")) {
                        continue;
                    }

                    // The source photos are always local; originals may live on R2 in production.
                    $path = "products/originals/{$product->id}/{$photo}";
                    ProductImageProcessor::originalsDisk()->put($path, Storage::disk('local')->get("{$photoDirectory}/{$photo}"));

                    // Saving the image builds its watermarked copies (see ProductImage::booted).
                    $product->images()->create([
                        'original_path' => $path,
                        'position' => $position,
                    ]);
                }
            }

            $this->command?->getOutput()->progressAdvance();
        }

        $this->command?->getOutput()->progressFinish();
    }
}
