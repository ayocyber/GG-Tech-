<?php

namespace Database\Factories;

use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
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

    /**
     * An order sent to a country outside Togo.
     */
    public function international(string $countryCode = 'FR', int $fee = 25000): static
    {
        return $this->state(fn (array $attributes) => [
            'fulfilment' => FulfilmentMethod::International,
            'delivery_country' => $countryCode,
            'address' => '12 rue de la Paix',
            'city' => 'Paris',
            'region' => 'Île-de-France',
            'postal_code' => '75002',
            'delivery_fee' => $fee,
            'total' => $attributes['subtotal'] + $fee,
        ]);
    }

    /**
     * An order the customer chose to pay online, with a payment attempt under way.
     */
    public function payingOnline(PaymentProvider $gateway = PaymentProvider::Demo): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Pending,
            'payment_gateway' => $gateway,
            'payment_identifier' => $attributes['number'].'-TEST01',
        ]);
    }
}
