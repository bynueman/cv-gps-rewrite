<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\StoryModerationEvent;
use App\Models\StoryReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StoryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Story::class);
        $data = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'hidden'])],
            'visibility' => ['nullable', Rule::in(['public', 'private'])], 'reports' => ['nullable', 'boolean'],
            'code' => ['nullable', 'string', 'regex:/^KC-[0-9]{4,20}$/']]);
        $query = Story::withTrashed()->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['visibility'] ?? null, fn ($q, $visibility) => $q->where('visibility', $visibility))
            ->when($data['code'] ?? null, fn ($q, $code) => $q->whereKey(ltrim(substr($code, 3), '0') ?: '0'));
        if ($request->boolean('reports')) {
            $query->whereNull('deleted_at')->whereIn('id', StoryReport::where('status', 'pending')->select('story_id'));
        }

        return Inertia::render('Admin/Stories/Index', ['stories' => $query->orderByDesc('id')->paginate(20)->withQueryString()
            ->through(fn (Story $story) => ['id' => $story->id, 'code' => $story->displayCode(),
                'excerpt' => mb_substr($story->body, 0, 180), 'redacted' => $story->trashed(), 'visibility' => $story->visibility, 'status' => $story->status]),
            'filters' => $data,
            'pendingReports' => StoryReport::where('status', 'pending')->whereIn('story_id', Story::select('id'))->count()]);
    }

    public function show(int $story): Response
    {
        Gate::authorize('viewAny', Story::class);
        $target = Story::withTrashed()->findOrFail($story);
        Gate::authorize('view', $target);

        return Inertia::render('Admin/Stories/Show', [
            'story' => $target->only(['id', 'body', 'category', 'visibility', 'status', 'publication_consent', 'social_consent', 'policy_version', 'created_at']),
            'code' => $target->displayCode(),
            'redacted' => $target->trashed(),
            'history' => StoryModerationEvent::where('story_id', $target->id)->orderByDesc('id')->limit(50)
                ->get(['id', 'actor_id', 'action', 'from_status', 'to_status', 'reason', 'created_at']),
            'reports' => StoryReport::where('story_id', $target->id)->orderByDesc('id')->paginate(20, ['id', 'reason', 'details', 'status', 'created_at']),
        ]);
    }

    public function update(Request $request, int $story): RedirectResponse
    {
        Gate::authorize('viewAny', Story::class);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'hidden'])], 'reason' => ['required', 'string', 'max:300']]);
        DB::connection('stories')->transaction(function () use ($request, $story, $data): void {
            $target = Story::lockForUpdate()->findOrFail($story);
            Gate::authorize('update', $target);
            StoryModerationEvent::create(['story_id' => $target->id, 'actor_id' => $request->user()->id,
                'action' => 'moderated', 'from_status' => $target->status, 'to_status' => $data['status'], 'reason' => $data['reason']]);
            $target->update(['status' => $data['status'],
                'published_at' => $data['status'] === 'approved' && $target->visibility === 'public' && $target->publication_consent
                    ? ($target->published_at ?? now()) : null]);
        });

        return back()->with('status', 'Keputusan moderasi disimpan. Visibility dan consent tidak diubah.');
    }

    public function redact(Request $request, int $story): RedirectResponse
    {
        Gate::authorize('viewAny', Story::class);
        $data = $request->validate([
            'review_confirmed' => ['required', 'accepted'],
            'confirmation_code' => ['required', 'string', 'regex:/^KC-[0-9]{4,20}$/'],
            'request_reference' => ['required', 'string', 'regex:/^PR-[A-Z0-9-]{6,30}$/'],
        ]);
        DB::connection('stories')->transaction(function () use ($request, $story, $data): void {
            $target = Story::withTrashed()->lockForUpdate()->findOrFail($story);
            Gate::authorize('redact', $target);
            abort_unless(hash_equals($target->displayCode(), $data['confirmation_code']), 422, 'Konfirmasi kode tidak sesuai.');
            if ($target->trashed()) {
                return;
            }
            // Remove free-text copies; keep the event/report identities and decision history.
            StoryModerationEvent::where('story_id', $target->id)->update(['reason' => null]);
            StoryReport::where('story_id', $target->id)->update(['details' => null]);
            StoryModerationEvent::create(['story_id' => $target->id, 'actor_id' => $request->user()->id,
                'action' => 'privacy_redacted', 'from_status' => $target->status, 'to_status' => 'hidden',
                'reason' => 'Operator reviewed privacy request '.$data['request_reference']]);
            // Historical policy, consent and legacy hashes remain intact; the tombstone cannot be republished.
            $target->update(['body' => '[removed]', 'status' => 'hidden', 'published_at' => null]);
            $target->delete();
        });

        return redirect()->route('admin.stories.show', $story)->with('status', 'Isi cerita diredaksi dan disembunyikan. Riwayat keputusan tetap tersedia.');
    }

    public function reviewReport(Request $request, int $report): RedirectResponse
    {
        Gate::authorize('viewAny', Story::class);
        $data = $request->validate(['status' => ['required', Rule::in(['resolved', 'dismissed'])], 'reason' => ['required', 'string', 'max:300']]);
        DB::connection('stories')->transaction(function () use ($request, $report, $data): void {
            $target = StoryReport::lockForUpdate()->findOrFail($report);
            $story = Story::findOrFail($target->story_id);
            $target->update(['status' => $data['status']]);
            StoryModerationEvent::create(['story_id' => $story->id, 'actor_id' => $request->user()->id,
                'action' => 'report_'.$data['status'], 'from_status' => $story->status, 'to_status' => $story->status, 'reason' => $data['reason']]);
        });

        return back()->with('status', 'Laporan telah ditinjau.');
    }
}
