<?php

namespace App\Support;

class StoryAvailability
{
    public static function publicEnabled(): bool
    {
        $version = trim((string) config('stories.policy_version'));

        return config('stories.enabled') && config('stories.policies_approved')
            && (! app()->environment('production')
                || ($version !== '' && strlen($version) <= 80 && ! str_starts_with(strtolower($version), 'draft')));
    }
}
