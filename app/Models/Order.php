<?php

namespace App\Models;

use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Enums\OrderStatus;
use App\Enums\Shop;
use App\Support\ShopLocation;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number', 'locale', 'market', 'currency', 'customer_name', 'email', 'phone', 'fulfilment', 'pickup_shop', 'delivery_zone_id',
    'address', 'city', 'landmark', 'notes', 'subtotal', 'delivery_fee', 'total', 'status', 'payment_method',
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
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
        ];
    }
}
