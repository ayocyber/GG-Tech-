<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the language from the {locale} URL prefix (French by default).
 *
 * Livewire update requests have no prefix, so they reuse the locale kept in the session.
 */
class SetLocale
{
    public const SUPPORTED = ['fr', 'en'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = in_array(session('locale'), self::SUPPORTED, true) ? session('locale') : config('app.locale');
        }

        app()->setLocale($locale);
        session(['locale' => $locale]);
        URL::defaults(['locale' => $locale]);

        $request->route()?->forgetParameter('locale');

        return $next($request);
    }
}
