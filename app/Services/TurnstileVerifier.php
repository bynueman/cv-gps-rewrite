<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    public function verify(string $token): bool
    {
        $secret = config('stories.turnstile_secret');
        $hostname = config('stories.turnstile_hostname');
        if (! $secret || ! $hostname || ! $token
            || (app()->environment('production') && preg_match('/^[123]x0+/', $secret))) {
            return false;
        }
        try {
            $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret, 'response' => $token,
            ]);

            return $response->successful() && $response->json('success') === true
                && $response->json('hostname') === $hostname
                && $response->json('action') === 'story_submit';
        } catch (\Throwable) {
            return false;
        }
    }
}
