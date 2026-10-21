<?php

namespace Database\Factories;

use App\Models\QuoteRequest;
use App\Models\QuoteRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteRequestItem>
 */
class QuoteRequestItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote_request_id' => QuoteRequest::factory(),
            'description' => fake()->words(4, true),
            'quantity' => 10,
        ];
    }
}
