<?php

namespace App\Livewire;

use App\Services\Baskets\Cart;
use App\Services\Baskets\QuoteBasket;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class BasketIndicator extends Component
{
    #[On('basket-updated')]
    public function refresh(): void
    {
        // Re-rendering reads the latest counts from the session.
    }

    public function render(Cart $cart, QuoteBasket $quote): View
    {
        return view('livewire.basket-indicator', [
            'cartCount' => $cart->count(),
            'quoteCount' => $quote->count(),
        ]);
    }
}
