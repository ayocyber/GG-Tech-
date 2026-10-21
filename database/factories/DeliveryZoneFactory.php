<?php

namespace Database\Factories;

use App\Enums\Market;
use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'market' => Market::Togo,
            'name' => ['fr' => $name, 'en' => $name],
            'areas' => null,
            'fee' => 2000,
            'delivery_time' => ['fr' => '24 h', 'en' => '24 h'],
            'position' => 0,
            'is_active' => true,
        ];
    }

    public function quoted(): static
    {
        return $this->state(['fee' => null]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
