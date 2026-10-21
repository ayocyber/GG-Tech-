<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'brand_id' => null,
            'slug' => str($name)->slug()->toString(),
            'name' => ['fr' => ucfirst($name).' FR', 'en' => ucfirst($name)],
            'short_description' => ['fr' => 'Résumé du produit.', 'en' => 'Product summary.'],
            'description' => null,
            'model' => strtoupper(fake()->bothify('??-###')),
            'compatibility' => null,
            'price_on_request' => false,
            'is_heavy' => false,
            'is_featured' => false,
            'needs_review' => false,
            'status' => ProductStatus::Published,
        ];
    }

    /**
     * Gives the product one in-stock variant at the given FCFA price.
     */
    public function priced(int $priceXof = 50000): static
    {
        return $this->has(ProductVariant::factory()->state(['price_xof' => $priceXof]), 'variants');
    }

    public function draft(): static
    {
        return $this->state(['status' => ProductStatus::Draft]);
    }

    public function priceOnRequest(): static
    {
        return $this->state(['price_on_request' => true])
            ->has(ProductVariant::factory()->state(['price_xof' => null]), 'variants');
    }

    public function heavy(): static
    {
        return $this->state(['is_heavy' => true]);
    }
}
