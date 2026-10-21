<?php

namespace Database\Factories;

use App\Enums\Market;
use App\Enums\QuoteStatus;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteRequest>
 */
class QuoteRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'Q-'.fake()->unique()->numberBetween(1001, 99999),
            'locale' => 'fr',
            'market' => Market::Togo,
            'company_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'email' => fake()->companyEmail(),
            'phone' => '+228 90 00 00 00',
            'status' => QuoteStatus::New,
        ];
    }
}
