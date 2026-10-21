<?php

namespace Database\Factories;

use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'GG-'.fake()->unique()->numberBetween(1001, 99999),
            'locale' => 'fr',
            'market' => Market::Togo,
            'currency' => 'XOF',
            'customer_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '+228 90 00 00 00',
            'fulfilment' => FulfilmentMethod::Pickup,
            'subtotal' => 50000,
            'delivery_fee' => 0,
            'total' => 50000,
            'status' => OrderStatus::New,
            'payment_method' => 'pending',
        ];
    }
}
