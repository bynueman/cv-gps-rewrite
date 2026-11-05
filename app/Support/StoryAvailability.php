<?php

namespace App\Support;

class StoryAvailability
{
    public const REQUIRED_POLICY_VERSION = 'kuicip-v4-single-consent-2026-11-05';

    public static function publicEnabled(): bool
    {
        $version = (string) config('stories.policy_version');

        return config('stories.enabled') && config('stories.policies_approved')
            && (! app()->environment('production')
                || $version === self::REQUIRED_POLICY_VERSION);
    }
}
