<?php

use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('builds public watermarked copies when a photo is saved', function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::disk('local')->put('products/originals/1/photo.jpg', UploadedFile::fake()->image('photo.jpg', 900, 600)->getContent());

    $image = ProductImage::factory()->create(['original_path' => 'products/originals/1/photo.jpg']);

    expect($image->refresh()->processed_at)->not->toBeNull();

    foreach ([400, 800, 1200] as $size) {
        Storage::disk('public')->assertExists($image->pathFor($size));
    }

    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($image->pathFor(400)));
    expect([$width, $height])->toBe([400, 400]);
});

it('stores photos on the disks chosen for production', function () {
    config(['store.disks.images' => 'r2_public', 'store.disks.originals' => 'r2_private']);
    Storage::fake('r2_public');
    Storage::fake('r2_private');
    Storage::disk('r2_private')->put('products/originals/1/photo.jpg', UploadedFile::fake()->image('photo.jpg', 600, 600)->getContent());

    $image = ProductImage::factory()->create(['original_path' => 'products/originals/1/photo.jpg']);

    Storage::disk('r2_public')->assertExists($image->pathFor(800));
    expect($image->refresh()->url())->toStartWith(Storage::disk('r2_public')->url($image->pathFor(800)));
});

it('keeps the original out of the public disk', function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::disk('local')->put('products/originals/1/photo.jpg', UploadedFile::fake()->image('photo.jpg', 500, 500)->getContent());

    ProductImage::factory()->create(['original_path' => 'products/originals/1/photo.jpg']);

    Storage::disk('public')->assertMissing('products/originals/1/photo.jpg');
});

it('deletes the original and every copy when the photo is removed', function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::disk('local')->put('products/originals/1/photo.jpg', UploadedFile::fake()->image('photo.jpg', 500, 500)->getContent());
    $image = ProductImage::factory()->create(['original_path' => 'products/originals/1/photo.jpg']);

    $image->delete();

    Storage::disk('local')->assertMissing('products/originals/1/photo.jpg');
    Storage::disk('public')->assertMissing($image->pathFor(800));
});
