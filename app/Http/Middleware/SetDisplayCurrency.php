<?php

namespace App\Http\Middleware;

use App\Services\Currency\DisplayCurrency;
use App\Services\Currency\ExchangeRates;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restores the display currency a returning visitor chose (kept in a cookie) and
 * refreshes stale exchange rates after the response.
 */
class SetDisplayCurrency
{
    public function __construct(private DisplayCurrency $currency, private ExchangeRates $rates) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $remembered = $request->cookie(DisplayCurrency::CookieName);

        if (! $this->currency->hasChosen() && is_string($remembered) && $this->currency->isAvailable($remembered)) {
            $this->currency->choose($remembered);
        }

        $this->rates->refreshIfStale();

        return $next($request);
    }
}
