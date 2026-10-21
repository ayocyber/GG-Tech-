<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'label' => null,
            'sku' => null,
            'price_xof' => 50000,
            'in_stock' => true,
            'position' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(['in_stock' => false]);
    }

    public function labelled(string $label): static
    {
        return $this->state(['label' => ['fr' => $label, 'en' => $label]]);
    }
}
