<?php

namespace App\Models;

use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\Shop;
use App\Support\Countries;
use App\Support\ShopLocation;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'number', 'locale', 'market', 'currency', 'customer_name', 'email', 'phone', 'fulfilment', 'pickup_shop', 'delivery_zone_id',
    'delivery_country', 'address', 'city', 'region', 'postal_code', 'landmark', 'notes', 'subtotal', 'delivery_fee', 'total', 'status', 'payment_method',
    'payment_status', 'payment_gateway', 'payment_identifier', 'payment_transaction_id', 'payment_reference', 'payment_provider_reference',
    'payment_network', 'paid_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->chaperone();
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    /**
     * The store a pickup order is collected from (head office for orders placed before there were two).
     */
    public function pickupLocation(): ?ShopLocation
    {
        if ($this->fulfilment !== FulfilmentMethod::Pickup) {
            return null;
        }

        return ShopLocation::for($this->pickup_shop ?? Shop::HeadOffice);
    }

    public function isInternational(): bool
    {
        return $this->fulfilment === FulfilmentMethod::International;
    }

    public function deliveryCountryName(?string $locale = null): ?string
    {
        return $this->delivery_country ? Countries::name($this->delivery_country, $locale) : null;
    }

    /**
     * Where the order goes: the Togo delivery zone or the destination country.
     */
    public function destination(?string $locale = null): ?string
    {
        return $this->isInternational()
            ? $this->deliveryCountryName($locale)
            : $this->deliveryZone?->translate('name', $locale);
    }

    /**
     * The delivery address on one line, ending with the country for orders sent abroad.
     */
    public function deliveryAddress(?string $locale = null): ?string
    {
        if (! $this->fulfilment?->isDelivery() || blank($this->address)) {
            return null;
        }

        return collect([
            $this->address,
            trim($this->postal_code.' '.$this->city),
            $this->region,
            $this->isInternational() ? $this->deliveryCountryName($locale) : null,
        ])->filter(fn (?string $part) => filled($part))->implode(', ');
    }

    /**
     * Whether the customer chose to pay online (Flooz / T-Money) rather than at pickup or on delivery.
     */
    public function paysOnline(): bool
    {
        return $this->payment_status !== null;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    /**
     * Gateways refuse a reference they have already seen, so every payment attempt gets a fresh one,
     * along with the gateway that will take it.
     */
    public function startPaymentAttempt(PaymentProvider $gateway): void
    {
        $this->update([
            'payment_status' => PaymentStatus::Pending,
            'payment_gateway' => $gateway,
            'payment_identifier' => $this->number.'-'.Str::upper(Str::random(6)),
            'payment_transaction_id' => null,
        ]);
    }

    /**
     * Secret that proves a payment link (return page, demo page, retry) was issued by us for this order.
     */
    public function paymentKey(): string
    {
        return substr(hash_hmac('sha256', 'payment:'.$this->number, (string) config('app.key')), 0, 32);
    }

    public function hasPaymentKey(string $key): bool
    {
        return hash_equals($this->paymentKey(), $key);
    }

    public function formatMoney(?int $amount): ?string
    {
        return $amount === null ? null : $this->market->format($amount);
    }

    protected function casts(): array
    {
        return [
            'market' => Market::class,
            'fulfilment' => FulfilmentMethod::class,
            'pickup_shop' => Shop::class,
            'status' => OrderStatus::class,
            'payment_gateway' => PaymentProvider::class,
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
        ];
    }
}
