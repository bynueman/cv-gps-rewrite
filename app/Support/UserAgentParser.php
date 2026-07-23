<?php

namespace App\Support;

/**
 * Hand-rolled instead of pulling in a UA-parsing package — the dashboard
 * only needs a coarse browser label and mobile/desktop/tablet bucket,
 * plus a bot filter so crawlers don't inflate the visitor charts.
 */
class UserAgentParser
{
    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'facebookexternalhit',
        'whatsapp', 'telegrambot', 'curl', 'wget', 'python-requests', 'postmanruntime',
        'headlesschrome', 'phantomjs', 'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot',
    ];

    public static function isBot(?string $userAgent): bool
    {
        if (! $userAgent) {
            return true;
        }

        $ua = strtolower($userAgent);
        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function browser(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Lainnya';
        }

        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') && ! str_contains($userAgent, 'Chromium') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && str_contains($userAgent, 'Version/') => 'Safari',
            default => 'Lainnya',
        };
    }

    public static function device(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'desktop';
        }

        // Order matters: "Mobile" in the UA string is the reliable signal
        // for phones even on Android (which also appears on Android
        // tablets, so checking Android before Mobile would misclassify
        // them). iPad/Tablet without "Mobile" catches the rest.
        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Mobi')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'iPad') || str_contains($userAgent, 'Tablet') || str_contains($userAgent, 'Android')) {
            return 'tablet';
        }

        return 'desktop';
    }
}
