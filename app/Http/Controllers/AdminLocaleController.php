<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;

class AdminLocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 404);

        session(['admin_locale' => $locale]);

        return back(fallback: '/admin');
    }
}
