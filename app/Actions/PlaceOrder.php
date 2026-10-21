<?php

namespace App\Actions;

use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Mail\NewOrderAlert;
use App\Mail\OrderReceived;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Baskets\BasketLine;
use App\Services\Baskets\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class PlaceOrder
{
    public function __construct(private Cart $cart) {}

    /**
     * @param  array{customer_name: string, email: string, phone: string, fulfilment: string, delivery_zone_id?: int|null, address?: string|null, city?: string|null, landmark?: string|null, notes?: string|null}  $details
     */
    public function handle(array $details, Market $market): Order
    {
        $lines = $this->cart->lines($market)->filter(fn (BasketLine $line) => $line->unitPrice !== null);

        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('The cart is empty.');
        }

        $fulfilment = FulfilmentMethod::from($details['fulfilment']);
        $zone = $fulfilment === FulfilmentMethod::Delivery
            ? DeliveryZone::query()->activeFor($market)->findOrFail($details['delivery_zone_id'] ?? null)
            : null;

        $subtotal = $this->cart->subtotal($lines);
        $deliveryFee = match (true) {
            $fulfilment === FulfilmentMethod::Pickup => 0,
            $zone->isQuoted(), $this->cart->needsDeliveryQuote($lines) => null,
            default => $zone->fee,
        };

        $order = DB::transaction(function () use ($details, $market, $fulfilment, $zone, $lines, $subtotal, $deliveryFee) {
            $order = Order::create([
                'locale' => app()->getLocale(),
                'market' => $market,
                'currency' => $market->currency(),
                'customer_name' => $details['customer_name'],
                'email' => $details['email'],
                'phone' => $details['phone'],
                'fulfilment' => $fulfilment,
                'delivery_zone_id' => $zone?->id,
                'address' => $fulfilment === FulfilmentMethod::Delivery ? ($details['address'] ?? null) : null,
                'city' => $fulfilment === FulfilmentMethod::Delivery ? ($details['city'] ?? null) : null,
                'landmark' => $fulfilment === FulfilmentMethod::Delivery ? ($details['landmark'] ?? null) : null,
                'notes' => $details['notes'] ?? null,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $subtotal + ($deliveryFee ?? 0),
                'payment_method' => 'pending',
            ]);

            $order->update(['number' => 'GG-'.($order->id + 1000)]);

            $order->items()->createMany($lines->map(fn (BasketLine $line) => [
                'product_id' => $line->product()->id,
                'product_variant_id' => $line->variant->id,
                'product_name' => $line->product()->translate('name'),
                'variant_label' => $line->variant->displayLabel(),
                'unit_price' => $line->unitPrice,
                'quantity' => $line->quantity,
                'line_total' => $line->lineTotal(),
            ])->all());

            return $order;
        });

        $this->cart->clear();

        $order->load(['items', 'deliveryZone']);

        Mail::to($order->email)->locale($order->locale)->queue(new OrderReceived($order));
        Mail::to(Setting::value('email'))->queue(new NewOrderAlert($order));

        return $order;
    }
}
