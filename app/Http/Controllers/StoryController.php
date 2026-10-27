<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoryRequest;
use App\Models\Story;
use App\Services\StoryAntispam;
use App\Services\StorySubmission;
use App\Support\Seo;
use App\Support\StoryAvailability;
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
            'enabled' => StoryAvailability::publicEnabled(),
            'siteKey' => config('stories.antispam_provider') === 'turnstile' ? config('stories.turnstile_site_key') : null,
            'antispamProvider' => config('stories.antispam_provider'),
            'policyVersion' => app()->environment('production') ? StoryAvailability::REQUIRED_POLICY_VERSION : config('stories.policy_version'),
            'retentionDays' => max(1, (int) config('stories.retention_days')),
            'categories' => Story::CATEGORIES,
            'seo' => (new Seo(title: 'Kuicip — Ruang Cerita',
                description: 'Teman Ceritamu. Sebuah ruang berbagi cerita bersama Kuicip.', canonical: url('/kuicip/cerita')))->toArray(),
        ]);
    }

    public function challenge(Request $request, StoryAntispam $antispam): JsonResponse
    {
        return response()->json($antispam->issue($request))->header('Cache-Control', 'no-store');
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

    public function store(StoreStoryRequest $request, StorySubmission $submissions, StoryAntispam $antispam): JsonResponse
    {
        return response()->json($submissions->submit($request, $antispam), 202)->header('Cache-Control', 'no-store');
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

    public function removal(): JsonResponse
    {
        return response()->json(['retired' => true,
            'message' => 'Penghapusan mandiri telah dihentikan. Hubungi GPS FOOD melalui kontak resmi untuk peninjauan permintaan.'], 410)
            ->header('Cache-Control', 'no-store');
    }
}
