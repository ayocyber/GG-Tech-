<?php

namespace App\Services;

use App\Models\ProductImage;
use App\Models\Setting;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Builds the public, watermarked WebP copies of a product photo.
 *
 * Originals stay on the private originals disk; only processed copies are public.
 */
class ProductImageProcessor
{
    public function __construct(private ImageManager $images) {}

    public static function originalsDisk(): Filesystem
    {
        return Storage::disk(config('store.disks.originals'));
    }

    public static function imagesDisk(): Filesystem
    {
        return Storage::disk(config('store.disks.images'));
    }

    public function process(ProductImage $image): void
    {
        $original = $this->images
            ->read(static::originalsDisk()->get($image->original_path))
            ->orient();

        $side = max($original->width(), $original->height());
        $square = $original->pad($side, $side, 'ffffff');

        foreach (config('store.image_sizes') as $size) {
            $copy = (clone $square)->scaleDown($size, $size);

            $this->applyWatermark($copy);

            static::imagesDisk()->put($image->pathFor($size), (string) $copy->toWebp(85), [
                'ContentType' => 'image/webp',
                'CacheControl' => 'public, max-age=31536000',
            ]);
        }

        $image->forceFill(['processed_at' => now()])->saveQuietly();
    }

    public function deleteCopies(ProductImage $image): void
    {
        static::imagesDisk()->delete(
            collect(config('store.image_sizes'))->map(fn (int $size) => $image->pathFor($size))->all(),
        );
    }

    private function applyWatermark(ImageInterface $image): void
    {
        $path = config('store.watermark.path');

        if (! is_file($path)) {
            return;
        }

        $widthPercent = (float) Setting::value('watermark.width_percent');
        $opacity = (int) Setting::value('watermark.opacity');
        $margin = (int) round($image->width() * config('store.watermark.margin_percent') / 100);

        $logo = $this->images->read($path)->scale(width: (int) round($image->width() * $widthPercent / 100));

        $image->place($logo, 'bottom-right', $margin, $margin, $opacity);
    }
}
