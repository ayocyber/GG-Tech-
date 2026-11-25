<?php

namespace App\Actions;

use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Enums\Shop;
use App\Mail\NewOrderAlert;
use App\Mail\OrderReceived;
use App\Models\DeliveryCountry;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Baskets\BasketLine;
use App\Services\Baskets\Cart;
use App\Services\Delivery\InternationalDeliveryPricing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PlaceOrder
{
    public function __construct(private Cart $cart, private InternationalDeliveryPricing $internationalPricing) {}

    /**
     * @param  array{customer_name: string, email: string, phone: string, fulfilment: string, pickup_shop?: string|null, delivery_zone_id?: int|null, delivery_country?: string|null, address?: string|null, city?: string|null, region?: string|null, postal_code?: string|null, landmark?: string|null, notes?: string|null, pay_online?: bool}  $details
     *
     * @throws ValidationException when the destination country is not enabled or a product may not leave Togo.
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
        $country = $fulfilment === FulfilmentMethod::International
            ? $this->internationalDestination($details['delivery_country'] ?? null, $lines)
            : null;

        $subtotal = $this->cart->subtotal($lines);
        $deliveryFee = match (true) {
            $fulfilment === FulfilmentMethod::Pickup => 0,
            $country !== null => $this->internationalPricing->feeFor($country, $lines),
            $zone->isQuoted(), $this->cart->needsDeliveryQuote($lines) => null,
            default => $zone->fee,
        };

        $paysOnline = (bool) ($details['pay_online'] ?? false);

        if ($paysOnline && $deliveryFee === null) {
            throw new InvalidArgumentException('An order cannot be paid online before its delivery fee is known.');
        }

        $order = DB::transaction(function () use ($details, $market, $fulfilment, $zone, $country, $lines, $subtotal, $deliveryFee, $paysOnline) {
            $order = Order::create([
                'locale' => app()->getLocale(),
                'market' => $market,
                'currency' => $market->currency(),
                'customer_name' => $details['customer_name'],
                'email' => $details['email'],
                'phone' => $details['phone'],
                'fulfilment' => $fulfilment,
                'pickup_shop' => $fulfilment === FulfilmentMethod::Pickup ? ($details['pickup_shop'] ?? Shop::HeadOffice->value) : null,
                'delivery_zone_id' => $zone?->id,
                'delivery_country' => $country?->country_code,
                'address' => $fulfilment->isDelivery() ? ($details['address'] ?? null) : null,
                'city' => $fulfilment->isDelivery() ? ($details['city'] ?? null) : null,
                'region' => $country !== null ? ($details['region'] ?? null) : null,
                'postal_code' => $country !== null ? ($details['postal_code'] ?? null) : null,
                'landmark' => $fulfilment === FulfilmentMethod::Delivery ? ($details['landmark'] ?? null) : null,
                'notes' => $details['notes'] ?? null,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $subtotal + ($deliveryFee ?? 0),
                'payment_method' => 'pending',
            ]);

            $order->update(['number' => 'GG-'.($order->id + 1000)]);

            if ($paysOnline) {
                $order->startPaymentAttempt();
            }

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

        // Online orders are announced once the payment succeeds (SyncOrderPayment).
        if ($order->paysOnline()) {
            return $order;
        }

        // The order is saved and confirmed on WhatsApp either way, so a mail outage must not fail checkout.
        rescue(fn () => Mail::to($order->email)->locale($order->locale)->queue(new OrderReceived($order)));
        rescue(fn () => Mail::to(Setting::value('email'))->queue(new NewOrderAlert($order)));

        return $order;
    }

    /**
     * Checked here as well as in the checkout form, so a forged request cannot ship a Togo-only product abroad.
     *
     * @param  Collection<int, BasketLine>  $lines
     *
     * @throws ValidationException
     */
    private function internationalDestination(?string $countryCode, Collection $lines): DeliveryCountry
    {
        $country = DeliveryCountry::query()->active()->where('country_code', $countryCode)->first();

        if ($country === null) {
            throw ValidationException::withMessages([
                'delivery_country' => __('We do not deliver to this country yet.'),
            ]);
        }

        $restricted = $this->cart->linesSoldInTogoOnly($lines);

        if ($restricted->isNotEmpty()) {
            throw ValidationException::withMessages([
                'delivery_country' => __('These products can only be bought for delivery in Togo: :products.', [
                    'products' => $restricted->map(fn (BasketLine $line) => $line->product()->translate('name'))->implode(', '),
                ]),
            ]);
        }

        return $country;
    }
}
