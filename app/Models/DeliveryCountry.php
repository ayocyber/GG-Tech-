<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Support\Countries;
use Database\Factories\DeliveryCountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A country outside Togo that GG-TECH delivers to, with its fixed delivery fee in FCFA.
 */
#[Fillable(['country_code', 'fee', 'delivery_time', 'conditions', 'position', 'is_active'])]
class DeliveryCountry extends Model
{
    /** @use HasFactory<DeliveryCountryFactory> */
    use HasFactory, HasTranslations;

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    public function name(?string $locale = null): string
    {
        return Countries::name($this->country_code, $locale);
    }

    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'delivery_time' => 'array',
            'conditions' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
