<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ShopeeUrl implements ValidationRule
{
    public static function isValid(mixed $value): bool
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        $url = parse_url($value);
        $host = strtolower($url['host'] ?? '');

        return ($url['scheme'] ?? '') === 'https'
            && ! isset($url['user']) && ! isset($url['pass'])
            && (! isset($url['port']) || $url['port'] === 443)
            && (in_array($host, ['shopee.co.id', 'shopee.com', 'shp.ee'], true)
                || str_ends_with($host, '.shopee.co.id'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid($value)) {
            $fail('Gunakan URL HTTPS Shopee yang valid.');
        }
    }
}
