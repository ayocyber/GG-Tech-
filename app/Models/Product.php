<?php

namespace App\Models;

use App\Enums\Market;
use App\Enums\ProductStatus;
use App\Enums\TonerType;
use App\Models\Concerns\HasTranslations;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'category_id', 'brand_id', 'toner_type', 'slug', 'name', 'short_description', 'description', 'model', 'compatibility',
    'price_on_request', 'is_heavy', 'is_featured', 'needs_review', 'status', 'catalog_page',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasTranslations;

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $product->search_text = Str::lower(implode(' ', array_filter([
                ...array_values($product->name ?? []),
                $product->model,
                $product->compatibility,
                Brand::query()->whereKey($product->brand_id)->value('name'),
            ])));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->whereNotNull('processed_at')->oldest('position');
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ProductStatus::Published);
    }

    #[Scope]
    protected function withStorefrontData(Builder $query): void
    {
        $query->with(['brand', 'category', 'coverImage', 'variants']);
    }

    #[Scope]
    protected function search(Builder $query, string $terms): void
    {
        foreach (preg_split('/\s+/', Str::lower(trim($terms))) as $term) {
            $query->where('search_text', 'like', '%'.addcslashes($term, '%_\\').'%');
        }
    }

    public function isInStock(): bool
    {
        return $this->variants->contains('in_stock', true);
    }

    public function hasVariantChoice(): bool
    {
        return $this->variants->count() > 1;
    }

    /**
     * The cheapest variant price in the given market, used for "from" prices on cards.
     */
    public function startingPrice(Market $market): ?int
    {
        return $this->variants
            ->map(fn (ProductVariant $variant) => $variant->priceFor($market))
            ->filter()
            ->min();
    }

    /**
     * The same toner in the other type (original or premium compatible), when the shop sells both.
     */
    public function tonerCounterpart(): ?Product
    {
        if (! $this->toner_type || ! str_ends_with($this->slug, $this->toner_type->slugSuffix())) {
            return null;
        }

        return static::query()
            ->published()
            ->with('variants')
            ->where('slug', Str::beforeLast($this->slug, $this->toner_type->slugSuffix()).$this->toner_type->other()->slugSuffix())
            ->first();
    }

    public function canBeAddedToCart(): bool
    {
        return ! $this->price_on_request && $this->variants->contains(
            fn (ProductVariant $variant) => $variant->in_stock && $variant->price_xof !== null,
        );
    }

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'short_description' => 'array',
            'description' => 'array',
            'price_on_request' => 'boolean',
            'is_heavy' => 'boolean',
            'is_featured' => 'boolean',
            'needs_review' => 'boolean',
            'status' => ProductStatus::class,
            'toner_type' => TonerType::class,
        ];
    }
}
