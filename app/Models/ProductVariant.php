<?php

namespace App\Models;

use App\Enums\Market;
use App\Models\Concerns\HasTranslations;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'label', 'sku', 'price_xof', 'in_stock', 'position'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, HasTranslations;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Price in the market's currency (FCFA).
     */
    public function priceFor(Market $market): ?int
    {
        return $this->price_xof;
    }

    public function displayLabel(): ?string
    {
        return $this->translate('label');
    }

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'in_stock' => 'boolean',
            'price_xof' => 'integer',
        ];
    }
}
