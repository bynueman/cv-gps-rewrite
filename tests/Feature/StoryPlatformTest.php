<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\StoryModerationEvent;
use App\Models\StoryReport;
use App\Models\User;
use App\Services\TurnstileVerifier;
use App\Support\StoryDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class StoryPlatformTest extends TestCase
{
    use RefreshDatabase;

    private bool $verificationSuccess = true;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'stories.enabled' => true, 'stories.policies_approved' => true, 'stories.policy_version' => 'reviewed-test-v1',
            'stories.abuse_key' => str_repeat('test-only-', 5), 'stories.turnstile_secret' => 'mock-server-secret',
            'stories.turnstile_hostname' => 'gpsfood.test', 'stories.daily_quota' => 200, 'stories.cooldown_seconds' => 60,
            'database.connections.stories' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
        ]);
        $this->artisan('migrate', ['--database' => 'stories', '--path' => 'database/migrations/stories', '--force' => true])->assertSuccessful();
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => fn () => Http::response([
            'success' => $this->verificationSuccess, 'hostname' => 'gpsfood.test', 'action' => 'story_submit',
        ])]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['category' => 'kehidupan', 'body' => 'Hari ini saya belajar menikmati hal-hal kecil dalam kehidupan.',
            'visibility' => 'public', 'terms_consent' => true, 'publication_consent' => true,
            'social_consent' => false, 'turnstile_token' => 'mock-token', 'idempotency_key' => (string) Str::uuid()], $overrides);
    }

    private function story(array $overrides = []): Story
    {
        return Story::create(array_merge(['category' => 'bahagia', 'body' => 'Cerita publik sintetis untuk pengujian, bukan kiriman pelanggan.',
            'visibility' => 'public', 'status' => 'approved', 'publication_consent' => true, 'social_consent' => false,
            'policy_version' => 'reviewed-test-v1', 'consented_at' => now(), 'idempotency_key' => (string) Str::uuid(),
            'request_hash' => str_repeat('a', 64), 'fingerprint' => str_repeat('b', 64),
            'duplicate_hash' => str_repeat('c', 64), 'published_at' => now()], $overrides));
    }

    public function test_public_feed_keeps_full_text_for_the_story_detail_modal(): void
    {
        $body = "Paragraf pertama cerita fiksi.\n\n".str_repeat('Cerita panjang untuk dibaca secara utuh. ', 40);
        $body = mb_substr($body, 0, 1500);
        $story = $this->story(['body' => $body]);
        $this->getJson(route('stories.feed'))->assertOk()
            ->assertJsonPath('stories.0.body', $body)
            ->assertJsonPath('stories.0.code', $story->displayCode());
    }

    public function test_pending_report_counter_excludes_deleted_stories(): void
    {
        $story = $this->story();
        $this->postJson(route('stories.report', $story->id), ['reason' => 'other'])->assertStatus(202);
        $this->actingAs(User::factory()->create(['role' => 'superadmin']))
            ->get(route('admin.stories.index'))->assertInertia(fn ($page) => $page->where('pendingReports', 1));
        $story->delete();
        $this->get(route('admin.stories.index'))->assertInertia(fn ($page) => $page->where('pendingReports', 0));
    }

    public function test_browsing_the_feed_does_not_consume_the_submission_rate_limit(): void
    {
        foreach (range(1, 6) as $request) {
            $this->getJson(route('stories.feed'))->assertOk();
        }
        $this->postJson(route('stories.store'), $this->payload())->assertStatus(202);
    }

    public function test_public_validation_never_flashes_story_text_into_the_website_session(): void
    {
        $this->post('/kuicip/cerita', ['body' => 'private invalid story text'])
            ->assertUnprocessable()->assertSessionMissing('_old_input.body');
        $this->assertDatabaseCount('stories', 0, 'stories');
    }

    public function test_story_submission_requires_csrf_and_accepts_the_cookie_header(): void
    {
        $middleware = Mockery::mock(ValidateCsrfToken::class, [$this->app, $this->app['encrypter']])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $middleware->shouldReceive('runningUnitTests')->andReturn(false);
        $this->app->instance(ValidateCsrfToken::class, $middleware);
        $this->postJson(route('stories.store'), $this->payload())->assertStatus(419);
        $cookie = $this->get(route('stories.index'))->assertOk()->getCookie('XSRF-TOKEN', false);
        $this->withHeader('X-XSRF-TOKEN', $cookie->getValue())->postJson(route('stories.store'), $this->payload())->assertStatus(202);
    }

    public function test_private_submission_cannot_grant_publication_or_social_reuse_accidentally(): void
    {
        $this->postJson(route('stories.store'), $this->payload(['visibility' => 'private', 'social_consent' => true]))->assertStatus(202);
        $story = Story::firstOrFail();
        $this->assertFalse($story->publication_consent);
        $this->assertFalse($story->social_consent);
        $response = $this->get(route('stories.index'))->assertOk();
        $this->assertStringNotContainsString($story->body, $response->getContent());
    }

    public function test_database_guard_rejects_mysql_mariadb_aliases_for_the_same_database(): void
    {
        $original = config('database');
        try {
            config(['database.default' => 'website',
                'database.connections.website' => ['driver' => 'mysql', 'host' => 'test.invalid', 'port' => '3306', 'database' => 'same_database'],
                'database.connections.stories' => ['driver' => 'mariadb', 'host' => 'test.invalid', 'port' => '3306', 'database' => 'same_database', 'username' => 'test']]);
            $this->expectException(\RuntimeException::class);
            StoryDatabase::ensureConfigured();
        } finally {
            config(['database' => $original]);
        }
    }

    public function test_retention_targets_only_the_story_database(): void
    {
        $user = User::factory()->create();
        $this->story(['created_at' => now()->subDays(91)]);
        $current = $this->story();
        $this->artisan('stories:prune')->assertSuccessful();
        $this->assertDatabaseCount('stories', 1, 'stories');
        $this->assertSame($current->id, Story::firstOrFail()->id);
        $this->assertNotNull($user->fresh());
    }

    public function test_guest_submission_is_pending_and_returns_an_owner_token_once(): void
    {
        $payload = $this->payload(['body' => "  Ini cerita sintetis yang cukup panjang.\r\nBaris kedua.  "]);
        $response = $this->postJson(route('stories.store'), $payload)->assertStatus(202);
        $story = Story::firstOrFail();
        $this->assertSame('pending', $story->status);
        $this->assertSame("Ini cerita sintetis yang cukup panjang.\nBaris kedua.", $story->body);
        $this->assertFalse($story->social_consent);
        $this->assertSame(hash('sha256', $response->json('deletion_token')), $story->deletion_token_hash);
        $this->assertSame('KC-0001', $response->json('code'));
        $this->postJson(route('stories.store'), $payload)->assertStatus(202)->assertJsonMissingPath('deletion_token');
        $this->assertDatabaseCount('stories', 1, 'stories');
        $this->getJson(route('stories.feed'))->assertJsonCount(0, 'stories');
        Http::assertSentCount(1);
    }

    public function test_reusing_a_retry_key_with_changed_content_is_rejected(): void
    {
        $payload = $this->payload();
        $this->postJson(route('stories.store'), $payload)->assertStatus(202);
        $payload['visibility'] = 'private';
        $this->postJson(route('stories.store'), $payload)->assertStatus(409);
        $this->assertDatabaseCount('stories', 1, 'stories');
    }

    public function test_consent_plain_text_and_length_are_validated_before_http(): void
    {
        foreach ([['publication_consent' => false], ['terms_consent' => false], ['body' => str_repeat('x', 19)],
            ['body' => str_repeat('x', 1501)], ['body' => '<script>alert("not plain text");</script>']] as $invalid) {
            $this->postJson(route('stories.store'), $this->payload($invalid))->assertStatus(422);
        }
        Http::assertNothingSent();
        $this->assertDatabaseCount('stories', 0, 'stories');
    }

    public function test_public_feed_is_bounded_and_never_includes_non_public_data(): void
    {
        foreach (range(1, 8) as $number) {
            $this->story(['body' => 'Cerita publik sintetis nomor '.$number]);
        }
        foreach ([['visibility' => 'private'], ['status' => 'pending'], ['status' => 'hidden'],
            ['status' => 'rejected'], ['publication_consent' => false], ['deleted_at' => now()]] as $attributes) {
            $this->story(array_merge(['body' => 'PRIVATE_SENTINEL'], $attributes));
        }
        $first = $this->getJson(route('stories.feed', ['visibility' => 'private', 'status' => 'pending']))
            ->assertOk()->assertJsonCount(6, 'stories');
        $this->assertStringNotContainsString('PRIVATE_SENTINEL', $first->getContent());
        $this->assertSame(['id', 'code', 'category', 'body', 'supports', 'published_at'], array_keys($first->json('stories.0')));
        $this->getJson(route('stories.feed', ['before' => $first->json('next')]))->assertOk()->assertJsonCount(2, 'stories');
        $this->getJson(route('stories.feed', ['category' => 'percintaan']))->assertOk()->assertJsonCount(0, 'stories');
        $first->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_guest_and_editor_cannot_access_private_moderation(): void
    {
        $story = $this->story(['visibility' => 'private']);
        $this->get(route('admin.stories.show', $story->id))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(['role' => 'editor']));
        $this->get(route('admin.stories.index'))->assertForbidden();
        $this->get(route('admin.stories.show', $story->id))->assertForbidden();
        $this->patch(route('admin.stories.update', $story->id), ['status' => 'approved', 'reason' => 'test'])->assertForbidden();
        $this->assertDatabaseCount('story_moderation_events', 0, 'stories');
    }

    public function test_superadmin_review_preserves_private_visibility_and_records_history(): void
    {
        $story = $this->story(['visibility' => 'private', 'publication_consent' => false, 'status' => 'pending', 'published_at' => null]);
        $admin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($admin)->get(route('admin.stories.show', $story->id))->assertOk();
        $this->patch(route('admin.stories.update', $story->id), ['status' => 'approved', 'reason' => 'Selesai ditinjau privat'])->assertRedirect();
        $this->assertSame('private', $story->fresh()->visibility);
        $this->assertNull($story->fresh()->published_at);
        $this->getJson(route('stories.feed'))->assertJsonCount(0, 'stories');
        $this->assertSame($admin->id, StoryModerationEvent::firstOrFail()->actor_id);
    }

    public function test_public_approval_and_immediate_hide_follow_consent(): void
    {
        $story = $this->story(['status' => 'pending', 'published_at' => null]);
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $this->patch(route('admin.stories.update', $story->id), ['status' => 'approved', 'reason' => 'Layak tampil'])->assertRedirect();
        $this->getJson(route('stories.feed'))->assertJsonCount(1, 'stories');
        $this->patch(route('admin.stories.update', $story->id), ['status' => 'hidden', 'reason' => 'Peninjauan ulang'])->assertRedirect();
        $this->getJson(route('stories.feed'))->assertJsonCount(0, 'stories');
        $this->assertDatabaseCount('story_moderation_events', 2, 'stories');
    }

    public function test_private_targets_cannot_be_reported_or_reacted_to(): void
    {
        $story = $this->story(['visibility' => 'private']);
        $this->postJson(route('stories.report', $story->id), ['reason' => 'privacy'])->assertNotFound();
        $this->postJson(route('stories.react', $story->id))->assertNotFound();
        $this->assertDatabaseCount('story_reports', 0, 'stories');
    }

    public function test_reactions_and_reports_are_deduplicated_without_automatic_hiding(): void
    {
        $story = $this->story();
        foreach (range(1, 2) as $attempt) {
            $this->postJson(route('stories.react', $story->id))->assertOk()->assertJsonPath('supports', 1);
            $this->postJson(route('stories.report', $story->id), ['reason' => 'privacy'])->assertStatus(202);
        }
        $this->assertDatabaseCount('story_reactions', 1, 'stories');
        $this->assertDatabaseCount('story_reports', 1, 'stories');
        $this->assertSame('approved', $story->fresh()->status);
        $this->actingAs(User::factory()->create(['role' => 'superadmin']))
            ->patch(route('admin.stories.reports.update', StoryReport::firstOrFail()->id), ['status' => 'resolved', 'reason' => 'Ditinjau staf'])->assertRedirect();
        $this->assertSame('resolved', StoryReport::firstOrFail()->status);
    }

    public function test_removal_requires_secret_token_and_redacts_the_story(): void
    {
        $response = $this->postJson(route('stories.store'), $this->payload())->assertStatus(202);
        $this->postJson(route('stories.removal'), ['code' => $response->json('code'), 'token' => str_repeat('0', 64)])->assertStatus(202);
        $this->assertDatabaseCount('stories', 1, 'stories');
        $this->assertNull(Story::firstOrFail()->deleted_at);
        $this->postJson(route('stories.removal'), ['code' => $response->json('code'), 'token' => $response->json('deletion_token')])->assertStatus(202);
        $this->assertNull(Story::first());
        $this->assertSame('[removed]', Story::withTrashed()->firstOrFail()->body);
    }

    public function test_disabled_feature_blocks_public_and_moderation_mutations(): void
    {
        config(['stories.enabled' => false]);
        $this->get(route('stories.index'))->assertOk()->assertInertia(fn ($page) => $page->where('enabled', false));
        $this->postJson(route('stories.store'), $this->payload())->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));
        $this->get(route('admin.stories.index'))->assertNotFound();
        $this->patch(route('admin.stories.update', 1), ['status' => 'approved', 'reason' => 'test'])->assertNotFound();
        $this->assertDatabaseCount('stories', 0, 'stories');
    }

    public function test_missing_story_connection_never_writes_to_the_website_database(): void
    {
        config(['database.connections.stories' => null]);
        $this->postJson(route('stories.store'), $this->payload())->assertStatus(503);
        $this->assertFalse(Schema::connection('sqlite')->hasTable('stories'));
        $this->assertSame(':memory:', DB::connection('sqlite')->getDatabaseName());
    }

    public function test_turnstile_failure_and_missing_policy_approval_block_submission(): void
    {
        $this->verificationSuccess = false;
        $this->postJson(route('stories.store'), $this->payload())->assertStatus(422);
        config(['stories.policies_approved' => false]);
        $this->postJson(route('stories.store'), $this->payload())->assertStatus(503);
        $this->assertDatabaseCount('stories', 0, 'stories');
    }

    public function test_production_turnstile_rejects_missing_secret_and_public_test_keys(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['stories.turnstile_secret' => null]);
        $this->assertFalse(app(TurnstileVerifier::class)->verify('token'));
        config(['stories.turnstile_secret' => '1x0000000000000000000000000000000AA']);
        $this->assertFalse(app(TurnstileVerifier::class)->verify('token'));
        config(['stories.turnstile_secret' => 'mock-production-secret']);
        $this->verificationSuccess = false;
        $this->assertFalse(app(TurnstileVerifier::class)->verify('token'));
    }

    public function test_cooldown_quota_and_route_rate_limits_are_server_enforced(): void
    {
        $this->postJson(route('stories.store'), $this->payload())->assertStatus(202);
        $this->postJson(route('stories.store'), $this->payload(['body' => 'Cerita kedua yang berbeda dan cukup panjang untuk pengujian.']))->assertStatus(429);
        config(['stories.daily_quota' => 1]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->postJson(route('stories.store'), $this->payload())->assertStatus(429);
        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('stories.store'), [])->assertStatus($attempt < 5 ? 422 : 429);
        }
        $this->assertDatabaseCount('stories', 1, 'stories');
    }

    public function test_siteverify_rejects_wrong_hostname_action_http_failure_and_timeout(): void
    {
        foreach ([
            [200, ['success' => true, 'hostname' => 'wrong.test', 'action' => 'story_submit']],
            [200, ['success' => true, 'hostname' => 'gpsfood.test', 'action' => 'wrong_action']],
            [200, ['success' => false, 'error-codes' => ['timeout-or-duplicate']]],
            [503, ['success' => true, 'hostname' => 'gpsfood.test', 'action' => 'story_submit']],
        ] as [$status, $body]) {
            Http::swap(new Factory);
            Http::preventStrayRequests();
            Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response($body, $status)]);
            $this->assertFalse(app(TurnstileVerifier::class)->verify('mock-token'));
        }
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(fn () => throw new ConnectionException('Simulated timeout'));
        $this->assertFalse(app(TurnstileVerifier::class)->verify('mock-token'));
    }

    public function test_replayed_cloudflare_token_cannot_create_a_second_story(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::sequence()
            ->push(['success' => true, 'hostname' => 'gpsfood.test', 'action' => 'story_submit'])
            ->push(['success' => false, 'error-codes' => ['timeout-or-duplicate']])]);
        $this->postJson(route('stories.store'), $this->payload())->assertStatus(202);
        $this->postJson(route('stories.store'), $this->payload(['body' => 'Cerita sintetis lain untuk mencoba token yang sudah dipakai.']))->assertStatus(422);
        $this->assertDatabaseCount('stories', 1, 'stories');
        Http::assertSentCount(2);
    }
}
