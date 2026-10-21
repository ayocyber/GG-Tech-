<?php

namespace App\Models;

use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number', 'locale', 'market', 'currency', 'customer_name', 'email', 'phone', 'fulfilment', 'delivery_zone_id',
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

    public function formatMoney(?int $amount): ?string
    {
        return $amount === null ? null : $this->market->format($amount);
    }

    protected function casts(): array
    {
        return [
            'market' => Market::class,
            'fulfilment' => FulfilmentMethod::class,
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
        ];
    }
}
