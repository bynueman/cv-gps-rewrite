<?php

namespace App\Support;

class StoryAvailability
{
    public const REQUIRED_POLICY_VERSION = 'kuicip-v3-support-removal-2026-10-27';

    public static function publicEnabled(): bool
    {
        $version = (string) config('stories.policy_version');

        return config('stories.enabled') && config('stories.policies_approved')
            && (! app()->environment('production')
                || $version === self::REQUIRED_POLICY_VERSION);
    }
}
