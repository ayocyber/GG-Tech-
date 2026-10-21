<?php

namespace App\Models;

use App\Services\ProductImageProcessor;
use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'original_path', 'position', 'processed_at'])]
class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    /**
     * Photos uploaded from the admin panel are watermarked as soon as they are saved.
     */
    protected static function booted(): void
    {
        static::saved(function (ProductImage $image) {
            if ($image->wasChanged('original_path') || $image->wasRecentlyCreated && $image->processed_at === null) {
                $previous = $image->getOriginal('original_path');

                if ($image->wasChanged('original_path') && $previous && $previous !== $image->original_path) {
                    ProductImageProcessor::originalsDisk()->delete($previous);
                }

                app(ProductImageProcessor::class)->process($image);
            }
        });

        static::deleted(function (ProductImage $image) {
            app(ProductImageProcessor::class)->deleteCopies($image);
            ProductImageProcessor::originalsDisk()->delete($image->original_path);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Public path of the watermarked copy at the given width.
     */
    public function pathFor(int $size): string
    {
        return "products/{$this->product_id}/{$this->id}-{$size}.webp";
    }

    public function url(int $size = 800): string
    {
        return ProductImageProcessor::imagesDisk()->url($this->pathFor($size)).'?v='.$this->processed_at?->timestamp;
    }

    public function srcset(): string
    {
        return collect(config('store.image_sizes'))
            ->map(fn (int $size) => $this->url($size)." {$size}w")
            ->implode(', ');
    }

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
