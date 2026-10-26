<?php

return [
    'enabled' => (bool) env('STORIES_ENABLED', false),
    'policies_approved' => (bool) env('STORIES_POLICIES_APPROVED', false),
    'policy_version' => env('STORIES_POLICY_VERSION', 'draft-v1'),
    'abuse_key' => env('STORIES_ABUSE_KEY'),
    'antispam_provider' => env('STORIES_ANTISPAM_PROVIDER', 'turnstile'),
    'daily_quota' => (int) env('STORIES_DAILY_QUOTA', 200),
    'cooldown_seconds' => (int) env('STORIES_COOLDOWN_SECONDS', 60),
    'retention_days' => (int) env('STORIES_RETENTION_DAYS', 90),
    'turnstile_site_key' => env('TURNSTILE_SITE_KEY'),
    'turnstile_secret' => env('TURNSTILE_SECRET_KEY'),
    'turnstile_hostname' => env('TURNSTILE_HOSTNAME'),
];
