<?php

use App\Enums\TonerType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductImageProcessor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Client feedback (October 2026): original and premium compatible toners are sold as separate
 * products in separate categories, each with its own price.
 *
 * A toner that had both an "Original" and a "Premium compatible" option becomes two products;
 * the old "toners" category keeps the inks, ribbons and toner powders.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('toner_type')->nullable()->after('brand_id');
        });

        $toners = Category::query()->where('slug', 'toners')->first();

        // Fresh installs get the new categories from CategorySeeder.
        if (! $toners) {
            return;
        }

        $categories = [
            TonerType::Original->value => Category::query()->updateOrCreate(['slug' => 'original-toners'], [
                'name' => ['fr' => 'Toners originaux', 'en' => 'Original toners'],
                'description' => [
                    'fr' => 'Cartouches de toner HP et Canon d’origine, fabriquées par le constructeur.',
                    'en' => 'Genuine HP and Canon toner cartridges, made by the printer manufacturer.',
                ],
                'icon' => 'toner',
                'is_active' => true,
            ]),
            TonerType::PremiumCompatible->value => Category::query()->updateOrCreate(['slug' => 'compatible-toners'], [
                'name' => ['fr' => 'Toners compatibles premium', 'en' => 'Premium compatible toners'],
                'description' => [
                    'fr' => 'Toners compatibles de qualité premium pour HP et Canon, à prix réduit.',
                    'en' => 'Premium quality compatible toners for HP and Canon, at a lower price.',
                ],
                'icon' => 'toner',
                'is_active' => true,
            ]),
        ];

        $toners->update([
            'slug' => 'inks-ribbons',
            'name' => ['fr' => 'Encres, rubans & consommables', 'en' => 'Inks, ribbons & supplies'],
            'description' => [
                'fr' => 'Encres, rubans, poudres de toner et autres consommables d’impression.',
                'en' => 'Inks, ribbons, toner powders and other printing supplies.',
            ],
        ]);

        foreach (['original-toners', 'compatible-toners', 'inks-ribbons', 'printers', 'networking', 'computing', 'power', 'accessories'] as $position => $slug) {
            Category::query()->where('slug', $slug)->update(['position' => $position]);
        }

        Product::query()
            ->whereBelongsTo($toners)
            ->with(['variants', 'images'])
            ->get()
            ->each(fn (Product $product) => $this->splitToner($product, $categories));
    }

    /**
     * Reverse the migrations.
     *
     * Drops the column only; split products and the new categories are not merged back.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('toner_type');
        });
    }

    /**
     * @param  array<string, Category>  $categories
     */
    private function splitToner(Product $product, array $categories): void
    {
        $variantsByType = $product->variants->groupBy(fn (ProductVariant $variant) => match ($variant->label['en'] ?? null) {
            'Original' => TonerType::Original->value,
            'Premium compatible' => TonerType::PremiumCompatible->value,
            default => 'other',
        });

        if ($variantsByType->has('other')) {
            if (str_contains($product->name['en'] ?? '', 'Original Toner')) {
                $product->update(['toner_type' => TonerType::Original, 'category_id' => $categories[TonerType::Original->value]->id]);
            }

            return;
        }

        $sourceSlug = $product->slug;
        $sourceName = $product->name;
        $sourceSummary = $product->short_description;
        $isFirst = true;

        foreach (TonerType::cases() as $type) {
            if (! $variantsByType->has($type->value)) {
                continue;
            }

            $target = $isFirst ? $product : $product->replicate(['search_text']);

            $target->fill([
                'slug' => $sourceSlug.$type->slugSuffix(),
                'name' => $this->withSuffix($sourceName, $type),
                'short_description' => $this->summaryFor($sourceSummary, $type),
                'toner_type' => $type,
                'category_id' => $categories[$type->value]->id,
            ])->save();

            ProductVariant::query()
                ->whereKey($variantsByType[$type->value]->modelKeys())
                ->update(['product_id' => $target->id, 'label' => null]);

            if (! $isFirst) {
                $this->copyPhotos($product->images, $target);
            }

            $isFirst = false;
        }
    }

    /**
     * @param  array<string, string>  $name
     * @return array<string, string>
     */
    private function withSuffix(array $name, TonerType $type): array
    {
        $suffixes = match ($type) {
            TonerType::Original => ['fr' => 'Original', 'en' => 'Original'],
            TonerType::PremiumCompatible => ['fr' => 'Compatible premium', 'en' => 'Premium Compatible'],
        };

        return collect($name)->map(fn (string $value, string $locale) => "{$value} — {$suffixes[$locale]}")->all();
    }

    /**
     * @param  array<string, string>|null  $summary
     * @return array<string, string>|null
     */
    private function summaryFor(?array $summary, TonerType $type): ?array
    {
        if ($summary === null) {
            return null;
        }

        $endings = match ($type) {
            TonerType::Original => [
                'fr' => ', cartouche d’origine du fabricant de l’imprimante.',
                'en' => ', original cartridge from the printer manufacturer.',
            ],
            TonerType::PremiumCompatible => [
                'fr' => ', cartouche compatible premium — une alternative économique à l’original.',
                'en' => ', premium compatible cartridge — an economical alternative to the original.',
            ],
        };

        return collect($summary)->map(fn (string $value, string $locale) => str_replace(
            [', disponible en original ou compatible premium.', ', available as original or premium compatible.'],
            $endings[$locale],
            $value,
        ))->all();
    }

    /**
     * Gives the new product its own copy of each photo, so deleting one product never removes the other's photos.
     *
     * @param  Collection<int, ProductImage>  $images
     */
    private function copyPhotos(Collection $images, Product $target): void
    {
        $disk = ProductImageProcessor::originalsDisk();

        foreach ($images as $image) {
            if (! $disk->exists($image->original_path)) {
                continue;
            }

            $path = "products/originals/{$target->id}/".basename($image->original_path);
            $disk->copy($image->original_path, $path);

            $target->images()->create(['original_path' => $path, 'position' => $image->position]);
        }
    }
};
