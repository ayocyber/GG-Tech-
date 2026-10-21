<?php

namespace App\Models;

use App\Enums\Market;
use App\Enums\QuoteStatus;
use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'number', 'locale', 'market', 'company_name', 'contact_name', 'job_title', 'email', 'phone', 'tax_id', 'city',
    'needed_by', 'notes', 'status',
])]
class QuoteRequest extends Model
{
    /** @use HasFactory<QuoteRequestFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteRequestItem::class);
    }

    protected function casts(): array
    {
        return [
            'market' => Market::class,
            'status' => QuoteStatus::class,
            'needed_by' => 'date',
        ];
    }
}
