<?php

namespace App\Livewire;

use App\Enums\Market;
use App\Services\Baskets\BasketLine;
use App\Services\Baskets\Cart;
use App\Services\Baskets\QuoteBasket;
use Illuminate\View\View;
use Livewire\Component;

class CartPage extends Component
{
    public function updateQuantity(Cart $cart, int $variantId, int|string $quantity): void
    {
        $cart->update($variantId, (int) $quantity);
        $this->dispatch('basket-updated');
    }

    public function remove(Cart $cart, int $variantId): void
    {
        $cart->remove($variantId);
        $this->dispatch('basket-updated');
    }

    /**
     * Moves the whole cart into the quote basket, for buyers who want a business price.
     */
    public function convertToQuote(Cart $cart, QuoteBasket $quote): void
    {
        foreach ($cart->lines(Market::current()) as $line) {
            $quote->add($line->variant, $line->quantity);
        }

        $cart->clear();
        $this->dispatch('basket-updated');
        $this->redirectRoute('quote');
    }

    public function render(Cart $cart): View
    {
        $market = Market::current();
        $lines = $cart->lines($market);

        return view('livewire.cart-page', [
            'market' => $market,
            'lines' => $lines,
            'subtotal' => $cart->subtotal($lines),
            'needsDeliveryQuote' => $cart->needsDeliveryQuote($lines),
            'hasBulkLine' => $lines->contains(fn (BasketLine $line) => $line->quantity >= config('store.bulk_quantity')),
        ]);
    }
}
