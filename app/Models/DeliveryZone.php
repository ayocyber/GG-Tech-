<?php

namespace App\Models;

use App\Enums\Market;
use App\Models\Concerns\HasTranslations;
use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['market', 'name', 'areas', 'fee', 'delivery_time', 'position', 'is_active'])]
class DeliveryZone extends Model
{
    /** @use HasFactory<DeliveryZoneFactory> */
    use HasFactory, HasTranslations;

    #[Scope]
    protected function activeFor(Builder $query, Market $market): void
    {
        $query->where('market', $market)->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    /**
     * Zones without a fixed fee are quoted by staff after the order.
     */
    public function isQuoted(): bool
    {
        return $this->fee === null;
    }

    protected function casts(): array
    {
        return [
            'market' => Market::class,
            'name' => 'array',
            'areas' => 'array',
            'delivery_time' => 'array',
            'fee' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
