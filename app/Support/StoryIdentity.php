<?php

namespace App\Support;

use Illuminate\Http\Request;

class StoryIdentity
{
    public static function hash(string $value): string
    {
        $key = config('stories.abuse_key');
        abort_unless(is_string($key) && strlen($key) >= 32, 503, 'Ruang Cerita belum siap menerima kiriman.');

        return hash_hmac('sha256', $value, $key);
    }

    // shortcut: daily network/browser deduplication, strengthen identity only if observed abuse warrants it.
    public static function fingerprint(Request $request): string
    {
        return self::hash($request->ip().'|'.$request->userAgent().'|'.now()->toDateString());
    }
}
