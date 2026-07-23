<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected static string $cachePrefix = 'setting:';

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever(self::$cachePrefix.$key, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::$cachePrefix.$key);
    }

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget(self::$cachePrefix.$setting->key));
        static::deleted(fn (Setting $setting) => Cache::forget(self::$cachePrefix.$setting->key));
    }
}
