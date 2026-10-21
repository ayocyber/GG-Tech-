<?php

namespace App\Models;

use Database\Factories\QuoteRequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quote_request_id', 'product_id', 'product_variant_id', 'description', 'quantity'])]
class QuoteRequestItem extends Model
{
    /** @use HasFactory<QuoteRequestItemFactory> */
    use HasFactory;

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
