<?php

namespace App\Services\Baskets;

use App\Models\Product;
use App\Models\ProductVariant;

final readonly class BasketLine
{
    public function __construct(
        public ProductVariant $variant,
        public int $quantity,
        public ?int $unitPrice,
    ) {}

    public function product(): Product
    {
        return $this->variant->product;
    }

    public function lineTotal(): ?int
    {
        return $this->unitPrice === null ? null : $this->unitPrice * $this->quantity;
    }
}
