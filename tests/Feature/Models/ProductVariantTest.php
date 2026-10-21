<?php

use App\Enums\Market;
use App\Models\ProductVariant;

it('charges the FCFA price', function () {
    $variant = ProductVariant::factory()->make(['price_xof' => 45000]);

    expect($variant->priceFor(Market::Togo))->toBe(45000);
});

it('has no price when the FCFA price is missing', function () {
    $variant = ProductVariant::factory()->make(['price_xof' => null]);

    expect($variant->priceFor(Market::Togo))->toBeNull();
});

it('formats prices with French thousand separators', function () {
    expect(Market::Togo->format(1250000))->toBe("1\u{202F}250\u{202F}000 FCFA");
});
