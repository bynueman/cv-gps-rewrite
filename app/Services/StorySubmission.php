<?php

namespace App\Services;

use App\Http\Requests\StoreStoryRequest;
use App\Models\Story;
use App\Support\StoryIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorySubmission
{
    public function submit(StoreStoryRequest $request, StoryAntispam $antispam): array
    {
        $data = $request->validated();
        $publication = $data['visibility'] === 'public' && $request->boolean('publication_consent');
        $social = $data['visibility'] === 'public' && $request->boolean('social_consent');
        $hash = StoryIdentity::hash(json_encode([$data['category'], $data['body'], $data['visibility'],
            $publication, $social, config('stories.policy_version')], JSON_THROW_ON_ERROR));
        $provider = $antispam->provider();
        if ($provider === 'local') {
            $antispam->verify($request, $hash);
        }
        $existing = Story::withTrashed()->where('idempotency_key', $data['idempotency_key'])->first();
        if ($existing) {
            abort_unless(hash_equals($existing->request_hash, $hash), 409, 'Kode kiriman sudah digunakan.');

            return ['code' => $existing->displayCode(), 'accepted' => true];
        }
        if ($provider === 'turnstile') {
            $antispam->verify($request, $hash);
        }
        $fingerprint = StoryIdentity::fingerprint($request);
        $duplicate = StoryIdentity::hash(mb_strtolower(preg_replace('/\s+/u', ' ', $data['body'])));
        $connection = DB::connection('stories');

        return $connection->transaction(function () use ($connection, $data, $hash, $publication, $fingerprint, $duplicate, $social) {
            $quotaKey = 'quota:'.now()->toDateString();
            $connection->table('story_limits')->insertOrIgnore(['key' => $quotaKey, 'count' => 0, 'expires_at' => now()->endOfDay()]);
            $quota = $connection->table('story_limits')->where('key', $quotaKey)->lockForUpdate()->first();
            // The quota row serializes submissions, including concurrent retries of the same UUID.
            $existing = Story::withTrashed()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_hash, $hash), 409, 'Kode kiriman sudah digunakan.');

                return ['code' => $existing->displayCode(), 'accepted' => true];
            }
            $cooldownKey = 'cooldown:'.$fingerprint;
            $cooldown = $connection->table('story_limits')->where('key', $cooldownKey)->first();
            if ($quota->count >= max(1, (int) config('stories.daily_quota'))
                || ($cooldown && now()->lt($cooldown->expires_at))
                || Story::where('fingerprint', $fingerprint)->where('duplicate_hash', $duplicate)
                    ->where('created_at', '>=', now()->subDay())->exists()) {
                Log::notice('Story submission limited', ['reason' => 'quota_cooldown_or_duplicate']);
                abort(429, 'Kiriman belum dapat diterima. Silakan coba lagi nanti.');
            }
            $token = bin2hex(random_bytes(32));
            $story = Story::create([
                'category' => $data['category'], 'body' => $data['body'], 'visibility' => $data['visibility'],
                'status' => 'pending', 'publication_consent' => $publication,
                'social_consent' => $social,
                'policy_version' => config('stories.policy_version'), 'consented_at' => now(),
                'deletion_token_hash' => hash('sha256', $token), 'idempotency_key' => $data['idempotency_key'],
                'request_hash' => $hash, 'fingerprint' => $fingerprint, 'duplicate_hash' => $duplicate,
            ]);
            $connection->table('story_limits')->where('key', $quotaKey)->increment('count');
            $connection->table('story_limits')->updateOrInsert(['key' => $cooldownKey], [
                'count' => 1, 'expires_at' => now()->addSeconds(max(1, (int) config('stories.cooldown_seconds'))),
            ]);

            return ['code' => $story->displayCode(), 'accepted' => true, 'deletion_token' => $token];
        }, 3);
    }
}
