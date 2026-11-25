<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * EUR and USD are created by the migration, so factories use other currencies.
 *
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(['GBP', 'CHF', 'CAD', 'JPY', 'CNY', 'ZAR']),
            'auto_rate' => 750.5,
            'auto_source' => Currency::SourceProvider,
            'auto_updated_at' => now(),
            'manual_rate' => null,
            'manual_updated_at' => null,
            'is_enabled' => true,
            'position' => 10,
        ];
    }

    public function disabled(): static
    {
        return $this->state(['is_enabled' => false]);
    }

    public function manual(float $rate): static
    {
        return $this->state(['manual_rate' => $rate, 'manual_updated_at' => now()]);
    }

    public function withoutRate(): static
    {
        return $this->state(['auto_rate' => null, 'auto_source' => null, 'auto_updated_at' => null]);
    }
}
