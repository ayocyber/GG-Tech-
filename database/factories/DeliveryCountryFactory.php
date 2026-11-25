<?php

namespace Database\Factories;

use App\Models\DeliveryCountry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryCountry>
 */
class DeliveryCountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_code' => fake()->unique()->randomElement(['BJ', 'GH', 'CI', 'NG', 'BF', 'SN', 'FR', 'BE', 'US', 'GB', 'DE', 'CA']),
            'fee' => 25000,
            'delivery_time' => ['fr' => '5 à 10 jours', 'en' => '5 to 10 days'],
            'conditions' => null,
            'position' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
