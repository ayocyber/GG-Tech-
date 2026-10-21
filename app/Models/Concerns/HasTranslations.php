<?php

namespace App\Models\Concerns;

/**
 * Reads French/English values stored as {"fr": "...", "en": "..."} JSON columns.
 */
trait HasTranslations
{
    public function translate(string $attribute, ?string $locale = null): ?string
    {
        $values = $this->getAttribute($attribute);

        if (! is_array($values)) {
            return $values;
        }

        $locale ??= app()->getLocale();

        return collect([$locale, config('app.fallback_locale'), config('app.locale')])
            ->map(fn (?string $candidate) => $values[$candidate ?? ''] ?? null)
            ->first(fn (?string $value) => filled($value))
            ?? collect($values)->first(fn (?string $value) => filled($value));
    }
}
