<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoryRequest;
use App\Models\Story;
use App\Models\StoryModerationEvent;
use App\Services\StorySubmission;
use App\Services\TurnstileVerifier;
use App\Support\Seo;
use App\Support\StoryIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Kuicip/Cerita', [
            'enabled' => (bool) (config('stories.enabled') && config('stories.policies_approved')),
            'siteKey' => config('stories.turnstile_site_key'),
            'policyVersion' => config('stories.policy_version'),
            'categories' => Story::CATEGORIES,
            'seo' => (new Seo(title: 'Kuicip — Ruang Cerita',
                description: 'Teman Ceritamu. Sebuah ruang berbagi cerita bersama Kuicip.', canonical: url('/kuicip/cerita')))->toArray(),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $data = $request->validate(['category' => ['nullable', Rule::in(Story::CATEGORIES)], 'before' => ['nullable', 'integer', 'min:1']]);
        $stories = Story::publicFeed()->when($data['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($data['before'] ?? null, fn ($q, $before) => $q->where('id', '<', $before))
            ->orderByDesc('id')->limit(7)->get();
        $batch = $stories->take(6);

        return response()->json(['stories' => $batch->map(fn (Story $story) => $story->publicData())->values(),
            'next' => $stories->count() > 6 ? $batch->last()->id : null])->header('Cache-Control', 'no-store');
    }

    public function store(StoreStoryRequest $request, StorySubmission $submissions, TurnstileVerifier $turnstile): JsonResponse
    {
        return response()->json($submissions->submit($request, $turnstile), 202)->header('Cache-Control', 'no-store');
    }

    public function react(Request $request, int $story): JsonResponse
    {
        $target = Story::publicFeed()->findOrFail($story);
        $connection = DB::connection('stories');
        $connection->transaction(function () use ($connection, $request, $target): void {
            $inserted = $connection->table('story_reactions')->insertOrIgnore([
                'story_id' => $target->id, 'fingerprint' => StoryIdentity::fingerprint($request), 'created_at' => now(),
            ]);
            if ($inserted) {
                Story::whereKey($target->id)->increment('supports_count');
            }
        });

        return response()->json(['supports' => $target->fresh()->supports_count])->header('Cache-Control', 'no-store');
    }

    public function report(Request $request, int $story): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', Rule::in(['privacy', 'harmful', 'spam', 'other'])],
            'details' => ['nullable', 'string', 'max:300']]);
        $target = Story::publicFeed()->findOrFail($story);
        DB::connection('stories')->table('story_reports')->insertOrIgnore([
            'story_id' => $target->id, 'fingerprint' => StoryIdentity::fingerprint($request),
            'reason' => $data['reason'], 'details' => $data['details'] ?? null,
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['received' => true], 202)->header('Cache-Control', 'no-store');
    }

    public function removal(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'regex:/^KC-[0-9]{4,20}$/'], 'token' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']]);
        DB::connection('stories')->transaction(function () use ($data): void {
            $story = Story::whereKey((int) substr($data['code'], 3))
                ->where('deletion_token_hash', hash('sha256', $data['token']))->lockForUpdate()->first();
            if ($story) {
                StoryModerationEvent::create(['story_id' => $story->id, 'action' => 'owner_removed',
                    'from_status' => $story->status, 'to_status' => 'hidden']);
                $story->update(['body' => '[removed]', 'status' => 'hidden', 'published_at' => null,
                    'deletion_token_hash' => null, 'social_consent' => false, 'publication_consent' => false]);
                $story->delete();
            }
        });

        return response()->json(['received' => true], 202)->header('Cache-Control', 'no-store');
    }
}
