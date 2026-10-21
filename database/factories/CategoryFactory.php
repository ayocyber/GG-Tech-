<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => str($name)->slug()->toString(),
            'name' => ['fr' => ucfirst($name).' (FR)', 'en' => ucfirst($name)],
            'description' => ['fr' => 'Description française.', 'en' => 'English description.'],
            'icon' => 'accessory',
            'position' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
