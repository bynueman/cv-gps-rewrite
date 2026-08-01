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
            $setting = static::where('key', $key)->first();

            // A row that exists but holds NULL means an admin explicitly
            // cleared it — that must win over the config default, or a
            // cleared field is stuck permanently reverting to the default
            // (#/reported: clearing "email sekunder" kept showing the old
            // address). Only fall back when the key was never saved at all.
            return $setting ? $setting->value : $default;
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
