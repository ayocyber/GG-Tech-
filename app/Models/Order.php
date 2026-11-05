<?php

namespace App\Models;

use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Shop;
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
    'address', 'city', 'landmark', 'notes', 'subtotal', 'delivery_fee', 'total', 'status', 'payment_method',
    'payment_status', 'payment_identifier', 'payment_reference', 'payment_provider_reference', 'payment_network', 'paid_at',
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
     * PayGate refuses an identifier it has already seen, so every payment attempt gets a fresh one.
     */
    public function startPaymentAttempt(): void
    {
        $this->update([
            'payment_status' => PaymentStatus::Pending,
            'payment_identifier' => $this->number.'-'.Str::upper(Str::random(6)),
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
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
        ];
    }
}
