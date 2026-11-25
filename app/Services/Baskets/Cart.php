<?php

namespace App\Services\Baskets;

use Illuminate\Support\Collection;

class Cart extends SessionBasket
{
    protected function sessionKey(): string
    {
        return 'cart';
    }

    /**
     * @param  Collection<int, BasketLine>  $lines
     */
    public function subtotal(Collection $lines): int
    {
        return (int) $lines->sum(fn (BasketLine $line) => $line->lineTotal() ?? 0);
    }

    /**
     * Heavy items (copiers, racks, UPS) need a delivery fee confirmed by staff.
     *
     * @param  Collection<int, BasketLine>  $lines
     */
    public function needsDeliveryQuote(Collection $lines): bool
    {
        return $lines->contains(fn (BasketLine $line) => $line->product()->is_heavy);
    }

    /**
     * The lines that cannot leave Togo because their product is sold for Togo only.
     *
     * @param  Collection<int, BasketLine>  $lines
     * @return Collection<int, BasketLine>
     */
    public function linesSoldInTogoOnly(Collection $lines): Collection
    {
        return $lines->reject(fn (BasketLine $line) => $line->product()->shipsAbroad())->values();
    }
}
