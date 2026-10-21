<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected const CACHE_KEY = 'store-settings';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Read a store setting, falling back to config/store.php.
     */
    public static function value(string $key, mixed $default = null): mixed
    {
        $stored = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('value', 'key')->all(),
        );

        return $stored[$key] ?? config("store.{$key}", $default);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function store(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
