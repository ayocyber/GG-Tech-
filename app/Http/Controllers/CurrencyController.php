<?php

namespace App\Http\Controllers;

use App\Services\Currency\DisplayCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

/**
 * Switches the currency prices are displayed in. The cart is kept: it holds FCFA prices only.
 */
class CurrencyController extends Controller
{
    public function __invoke(Request $request, DisplayCurrency $currency): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', Rule::in($currency->codes())],
        ]);

        $currency->choose($validated['currency']);

        Cookie::queue(DisplayCurrency::CookieName, $validated['currency'], 60 * 24 * 365);

        return back(fallback: route('home', ['locale' => app()->getLocale()]));
    }
}
