<?php

namespace App\Support;

use Locale;

/**
 * ISO 3166 country names in the visitor's language, read from PHP's intl data.
 */
class Countries
{
    /**
     * Region codes intl knows that are not countries a parcel can be sent to.
     */
    private const NotCountries = ['AC', 'CP', 'CQ', 'DG', 'EA', 'EU', 'EZ', 'IC', 'QO', 'TA', 'UN', 'XA', 'XB', 'ZZ'];

    /**
     * Lists already built during this request, by locale.
     *
     * @var array<string, array<string, string>>
     */
    private static array $built = [];

    public static function name(string $code, ?string $locale = null): string
    {
        return Locale::getDisplayRegion('-'.strtoupper($code), $locale ?? app()->getLocale());
    }

    public static function exists(string $code): bool
    {
        return array_key_exists(strtoupper($code), self::all('en'));
    }

    /**
     * Every country GG-TECH could ship to from Togo, keyed by code and sorted by name.
     *
     * @return array<string, string>
     */
    public static function all(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return self::$built[$locale] ??= self::build($locale);
    }

    /**
     * @return array<string, string>
     */
    private static function build(string $locale): array
    {
        $countries = [];

        foreach (range('A', 'Z') as $first) {
            foreach (range('A', 'Z') as $second) {
                $code = $first.$second;
                $name = Locale::getDisplayRegion('-'.$code, $locale);

                if ($name !== $code && $code !== 'TG' && ! in_array($code, self::NotCountries, true)) {
                    $countries[$code] = $name;
                }
            }
        }

        asort($countries, SORT_LOCALE_STRING);

        return $countries;
    }
}
