<?php

namespace App\Models;

use App\Models\Concerns\HasContentTranslation;
use App\Support\LocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    use HasContentTranslation;

    protected $with = ['translationState'];

    protected $fillable = [
        'key',
        'value',
        'value_en',
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

    public static function pair(string $key, string $defaultId = '', string $defaultEn = ''): array
    {
        return Cache::rememberForever('setting-pair:'.$key, function () use ($key, $defaultId, $defaultEn) {
            $record = static::where('key', $key)->first();

            return $record ? LocalizedContent::pair($record, 'value') : ['id' => $defaultId, 'en' => $defaultEn];
        });
    }

    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            Cache::forget(self::$cachePrefix.$setting->key);
            Cache::forget('setting-pair:'.$setting->key);
            DB::afterCommit(function () use ($setting) {
                Cache::forget(self::$cachePrefix.$setting->key);
                Cache::forget('setting-pair:'.$setting->key);
            });
        });
        static::deleted(function (Setting $setting) {
            Cache::forget(self::$cachePrefix.$setting->key);
            Cache::forget('setting-pair:'.$setting->key);
        });
    }
}
