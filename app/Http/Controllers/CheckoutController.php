<?php

namespace App\Http\Controllers;

use App\Services\Baskets\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __invoke(Cart $cart): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()->route('cart');
        }

        return view('checkout');
    }
}
