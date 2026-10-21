<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the admin panel language chosen from the user menu (English by default).
 */
class SetAdminLocale
{
    public const DEFAULT = 'en';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('admin_locale');

        app()->setLocale(in_array($locale, SetLocale::SUPPORTED, true) ? $locale : self::DEFAULT);

        return $next($request);
    }
}
