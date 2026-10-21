<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class LocaleSwitcher
{
    /**
     * The current page's URL in another language.
     */
    public static function urlFor(string $locale): string
    {
        $route = request()->route();

        if (! $route?->getName() || ! array_key_exists('locale', $route->originalParameters())) {
            return route('home', ['locale' => $locale]);
        }

        $parameters = [...$route->originalParameters(), 'locale' => $locale];

        if (in_array('signed', $route->gatherMiddleware(), true)) {
            return URL::signedRoute($route->getName(), $parameters);
        }

        $query = request()->query();

        return route($route->getName(), [...$parameters, ...$query]);
    }
}
