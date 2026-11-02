<?php

namespace App\Livewire;

use App\Enums\Market;
use App\Services\Baskets\Cart;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartSidebar extends Component
{
    #[On('basket-updated')]
    public function refresh(): void
    {
        // Re-rendering reads the latest lines from the session.
    }

    /**
     * Each tap sends its own $tappedAt because Livewire merges identical calls made while a
     * request is running, which would otherwise lose fast taps. The same applies to decrement().
     */
    public function increment(Cart $cart, int $variantId, ?float $tappedAt = null): void
    {
        $cart->update($variantId, ($cart->quantities()[$variantId] ?? 0) + 1);
        $this->dispatch('basket-updated');
    }

    public function decrement(Cart $cart, int $variantId, ?float $tappedAt = null): void
    {
        $cart->update($variantId, max(1, ($cart->quantities()[$variantId] ?? 0) - 1));
        $this->dispatch('basket-updated');
    }

    public function remove(Cart $cart, int $variantId): void
    {
        $cart->remove($variantId);
        $this->dispatch('basket-updated');
    }

    public function render(Cart $cart): View
    {
        $market = Market::current();
        $lines = $cart->lines($market);

        return view('livewire.cart-sidebar', [
            'market' => $market,
            'lines' => $lines,
            'subtotal' => $cart->subtotal($lines),
        ]);
    }
}
