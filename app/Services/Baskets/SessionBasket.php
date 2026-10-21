<?php

namespace App\Services\Baskets;

use App\Enums\Market;
use App\Enums\ProductStatus;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * A list of product variants and quantities kept in the visitor's session.
 */
abstract class SessionBasket
{
    public const MAX_QUANTITY = 999;

    abstract protected function sessionKey(): string;

    /**
     * @return array<int, int> Variant id => quantity.
     */
    public function quantities(): array
    {
        return session($this->sessionKey(), []);
    }

    public function add(ProductVariant $variant, int $quantity = 1): void
    {
        $quantities = $this->quantities();
        $quantities[$variant->id] = min(self::MAX_QUANTITY, ($quantities[$variant->id] ?? 0) + max(1, $quantity));

        session([$this->sessionKey() => $quantities]);
    }

    public function update(int $variantId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($variantId);

            return;
        }

        $quantities = $this->quantities();

        if (array_key_exists($variantId, $quantities)) {
            $quantities[$variantId] = min(self::MAX_QUANTITY, $quantity);
            session([$this->sessionKey() => $quantities]);
        }
    }

    public function remove(int $variantId): void
    {
        $quantities = $this->quantities();
        unset($quantities[$variantId]);

        session([$this->sessionKey() => $quantities]);
    }

    public function clear(): void
    {
        session()->forget($this->sessionKey());
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Lines for published products only, priced in the given market.
     *
     * @return Collection<int, BasketLine>
     */
    public function lines(Market $market): Collection
    {
        $quantities = $this->quantities();

        if ($quantities === []) {
            return collect();
        }

        return ProductVariant::query()
            ->with(['product.coverImage', 'product.brand'])
            ->whereKey(array_keys($quantities))
            ->get()
            ->filter(fn (ProductVariant $variant) => $variant->product?->status === ProductStatus::Published)
            ->sortBy(fn (ProductVariant $variant) => array_search($variant->id, array_keys($quantities)))
            ->map(fn (ProductVariant $variant) => new BasketLine(
                $variant,
                $quantities[$variant->id],
                $variant->priceFor($market),
            ))
            ->values();
    }
}
