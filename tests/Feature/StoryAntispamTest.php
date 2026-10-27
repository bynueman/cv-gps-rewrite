<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use App\Services\StoryAntispam;
use App\Support\StoryAvailability;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StoryAntispamTest extends TestCase
{
    use RefreshDatabase;

    private string $sessionCookie = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
        config(['stories.enabled' => true, 'stories.policies_approved' => true, 'stories.policy_version' => 'approved-test-v1',
            'stories.antispam_provider' => 'local', 'stories.abuse_key' => str_repeat('test-only-', 5),
            'stories.turnstile_secret' => null, 'stories.turnstile_site_key' => null, 'cache.default' => 'file',
            'database.connections.stories' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        $this->artisan('migrate', ['--database' => 'stories', '--path' => 'database/migrations/stories', '--force' => true])->assertSuccessful();
    }

    private function challenge(): array
    {
        $response = $this->postJson(route('stories.challenge'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $cookie = $response->getCookie(config('session.cookie'), false);
        $this->sessionCookie = $cookie->getValue();
        $this->withUnencryptedCookie(config('session.cookie'), $this->sessionCookie);
        $data = $response->json();
        $this->assertSame(['token', 'question', 'expires_in', 'wait_seconds'], array_keys($data));
        preg_match('/^(\d+) \+ (\d+)$/', $data['question'], $parts);

        return $data + ['answer' => (string) ((int) $parts[1] + (int) $parts[2])];
    }

    private function payload(array $challenge, array $overrides = []): array
    {
        return array_merge(['policy_version' => config('stories.policy_version'), 'category' => 'kehidupan', 'body' => 'Cerita fiksi cukup panjang untuk pengujian verifikasi lokal.',
            'visibility' => 'public', 'terms_consent' => true, 'publication_consent' => true, 'social_consent' => false,
            'idempotency_key' => (string) Str::uuid(), 'contact_website' => '',
            'challenge_token' => $challenge['token'], 'challenge_answer' => $challenge['answer']], $overrides);
    }

    public function test_local_provider_accepts_anonymous_submission_without_cloudflare_and_starts_pending(): void
    {
        $challenge = $this->challenge();
        $this->travel(2)->seconds();
        $response = $this->postJson(route('stories.store'), $this->payload($challenge))->assertStatus(202);
        $story = Story::firstOrFail();
        $this->assertSame('pending', $story->status);
        $response->assertJsonMissingPath('deletion_token');
        $this->assertNull($story->deletion_token_hash);
        $this->getJson(route('stories.feed'))->assertJsonCount(0, 'stories');
        Http::assertNothingSent();
    }

    public function test_wrong_answers_are_bounded_and_never_query_or_write_story_storage(): void
    {
        $challenge = $this->challenge();
        $this->travel(2)->seconds();
        DB::connection('stories')->enableQueryLog();
        foreach (range(1, 3) as $attempt) {
            $this->postJson(route('stories.store'), $this->payload($challenge, ['challenge_answer' => '99']))->assertUnprocessable();
        }
        $this->postJson(route('stories.store'), $this->payload($challenge))->assertUnprocessable();
        $this->assertSame([], DB::connection('stories')->getQueryLog());
        DB::connection('stories')->disableQueryLog();
        $this->assertDatabaseCount('stories', 0, 'stories');
        $this->assertDatabaseCount('story_limits', 0, 'stories');
        Http::assertNothingSent();
    }

    public function test_expired_challenge_fails_and_a_new_challenge_works(): void
    {
        $expired = $this->challenge();
        $this->travel(301)->seconds();
        $this->postJson(route('stories.store'), $this->payload($expired))->assertUnprocessable()->assertJsonValidationErrors('challenge_token');
        $renewed = $this->challenge();
        $this->travel(2)->seconds();
        $this->postJson(route('stories.store'), $this->payload($renewed))->assertStatus(202);
    }

    public function test_challenge_allows_only_the_exact_idempotent_retry_and_rejects_replay(): void
    {
        $challenge = $this->challenge();
        $this->travel(2)->seconds();
        $payload = $this->payload($challenge);
        $this->postJson(route('stories.store'), $payload)->assertStatus(202)->assertJsonMissingPath('deletion_token');
        $this->postJson(route('stories.store'), $payload)->assertStatus(202)->assertJsonMissingPath('deletion_token');
        $this->postJson(route('stories.store'), $this->payload($challenge))->assertUnprocessable();
        $this->postJson(route('stories.store'), array_merge($payload, ['body' => 'Isi yang diubah tetap tidak boleh memakai UUID kiriman lama.']))->assertStatus(409);
        $this->assertDatabaseCount('stories', 1, 'stories');
    }

    public function test_signed_challenge_is_tamper_resistant_and_bound_to_the_issuing_session(): void
    {
        $challenge = $this->challenge();
        $originalCookie = $this->sessionCookie;
        $this->travel(2)->seconds();
        $this->withUnencryptedCookie(config('session.cookie'), 'invalid-cookie')
            ->postJson(route('stories.store'), $this->payload($challenge))->assertUnprocessable();
        $this->withUnencryptedCookie(config('session.cookie'), $originalCookie)
            ->postJson(route('stories.store'), $this->payload($challenge, ['challenge_token' => substr($challenge['token'], 0, -4).'aaaa']))->assertUnprocessable();
        $this->postJson(route('stories.store'), $this->payload($challenge))->assertStatus(202);
    }

    public function test_server_timing_rejects_fast_posts_without_consuming_a_correct_answer(): void
    {
        $challenge = $this->challenge();
        $payload = $this->payload($challenge);
        $this->postJson(route('stories.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('challenge_answer');
        $this->assertDatabaseCount('story_limits', 0, 'stories');
        $this->travel(2)->seconds();
        $this->postJson(route('stories.store'), $payload)->assertStatus(202);
    }

    public function test_honeypot_missing_challenge_and_malformed_answers_are_rejected_before_database_work(): void
    {
        $challenge = $this->challenge();
        $this->travel(2)->seconds();
        foreach ([['contact_website' => 'https://spam.invalid'], ['challenge_token' => null], ['challenge_answer' => null],
            ['challenge_answer' => '-1'], ['challenge_answer' => ['bad']]] as $invalid) {
            $this->postJson(route('stories.store'), $this->payload($challenge, $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('stories', 0, 'stories');
        $this->assertDatabaseCount('story_limits', 0, 'stories');
    }

    public function test_challenge_issuance_and_verification_have_independent_http_limits(): void
    {
        $challenge = $this->challenge();
        foreach (range(1, 9) as $attempt) {
            $this->postJson(route('stories.challenge'))->assertOk();
        }
        $this->postJson(route('stories.challenge'))->assertStatus(429);
        $this->travel(2)->seconds();
        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('stories.store'), $this->payload($challenge, ['challenge_token' => 'invalid']))->assertUnprocessable();
        }
        $this->postJson(route('stories.store'), $this->payload($challenge))->assertStatus(429);
        $this->assertDatabaseCount('stories', 0, 'stories');
    }

    public function test_quota_cooldown_and_duplicate_detection_still_apply_after_valid_math(): void
    {
        $first = $this->challenge();
        $this->travel(2)->seconds();
        $this->postJson(route('stories.store'), $this->payload($first))->assertStatus(202);
        $second = $this->challenge();
        $this->travel(2)->seconds();
        $this->postJson(route('stories.store'), $this->payload($second, ['body' => 'Cerita kedua yang berbeda tetap mengikuti cooldown kiriman.']))->assertStatus(429);
        $this->travel(61)->seconds();
        $duplicate = $this->challenge();
        $this->travel(2)->seconds();
        $this->postJson(route('stories.store'), $this->payload($duplicate))->assertStatus(429);
        config(['stories.daily_quota' => 1]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.55']);
        $quota = $this->challenge();
        $this->travel(2)->seconds();
        $this->postJson(route('stories.store'), $this->payload($quota, ['body' => 'Jaringan berbeda masih tunduk pada kuota harian global.']))->assertStatus(429);
        $this->assertDatabaseCount('stories', 1, 'stories');
    }

    public function test_private_moderation_and_retired_removal_remain_safe_in_local_mode(): void
    {
        $challenge = $this->challenge();
        $this->travel(2)->seconds();
        $receipt = $this->postJson(route('stories.store'), $this->payload($challenge,
            ['visibility' => 'private', 'publication_consent' => true, 'social_consent' => true]))->assertStatus(202);
        $story = Story::firstOrFail();
        $this->assertFalse($story->publication_consent);
        $this->assertFalse($story->social_consent);
        $this->actingAs(User::factory()->create(['role' => 'superadmin']))
            ->patch(route('admin.stories.update', $story->id), ['status' => 'approved', 'reason' => 'Review privat uji.'])->assertRedirect();
        $this->assertNull($story->fresh()->published_at);
        $this->getJson(route('stories.feed'))->assertJsonCount(0, 'stories');
        $this->postJson(route('stories.removal'), ['code' => $receipt->json('code')])->assertStatus(410);
        $this->assertSame($story->body, Story::firstOrFail()->body);
        $this->assertNull(Story::firstOrFail()->deletion_token_hash);
    }

    public function test_unknown_provider_and_stage_a_fail_closed(): void
    {
        $payload = $this->payload(['token' => 'invalid', 'answer' => '1']);
        config(['stories.antispam_provider' => 'none']);
        $this->postJson(route('stories.store'), $payload)->assertStatus(503);
        $this->postJson(route('stories.challenge'))->assertStatus(503);
        config(['stories.antispam_provider' => 'local', 'stories.policies_approved' => false]);
        $this->postJson(route('stories.challenge'))->assertStatus(503);
        $this->postJson(route('stories.store'), $payload)->assertStatus(503);
        $this->getJson(route('stories.feed'))->assertStatus(503);
        $this->get(route('stories.index'))->assertOk()->assertInertia(fn ($page) => $page->where('enabled', false)->where('antispamProvider', 'local')->where('siteKey', null));
        Http::assertNothingSent();
    }

    public function test_concurrent_verification_lock_does_not_allow_a_second_consumer(): void
    {
        $challenge = $this->challenge();
        $this->travel(2)->seconds();
        $nonce = json_decode(Crypt::decryptString($challenge['token']), true)['nonce'];
        $lock = Cache::lock('story-challenge:'.$nonce.':lock', 5);
        $this->assertTrue($lock->get());
        try {
            $this->postJson(route('stories.store'), $this->payload($challenge))->assertStatus(429);
        } finally {
            $lock->release();
        }
        $this->postJson(route('stories.store'), $this->payload($challenge))->assertStatus(202);
    }

    public function test_production_rejects_nonpersistent_cache_or_sessions_for_local_verification(): void
    {
        config(['session.driver' => 'array']);
        $request = Request::create('/kuicip/cerita/challenge', 'POST');
        $request->setLaravelSession(app('session')->driver());
        app()->detectEnvironment(fn () => 'production');
        try {
            app(StoryAntispam::class)->issue($request);
            $this->fail('Production must reject array sessions.');
        } catch (HttpException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_local_challenge_issuance_preserves_csrf_protection(): void
    {
        $middleware = \Mockery::mock(ValidateCsrfToken::class, [$this->app, $this->app['encrypter']])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $middleware->shouldReceive('runningUnitTests')->andReturn(false);
        $this->app->instance(ValidateCsrfToken::class, $middleware);
        $this->postJson(route('stories.challenge'))->assertStatus(419);
        $landing = $this->get(route('stories.index'))->assertOk();
        $this->withUnencryptedCookie(config('session.cookie'), $landing->getCookie(config('session.cookie'), false)->getValue());
        $this->withHeader('X-XSRF-TOKEN', $landing->getCookie('XSRF-TOKEN', false)->getValue())
            ->postJson(route('stories.challenge'))->assertOk();
    }

    public function test_clients_cannot_select_a_weaker_provider_in_the_submission(): void
    {
        $payload = $this->payload(['token' => '', 'answer' => '1'], ['antispam_provider' => 'turnstile', 'turnstile_token' => 'pretend-valid']);
        $this->postJson(route('stories.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('challenge_token');
        $this->assertDatabaseCount('stories', 0, 'stories');
        Http::assertNothingSent();
    }

    public function test_local_retry_after_policy_change_uses_a_fresh_challenge_without_rewriting_old_consent(): void
    {
        $first = $this->challenge();
        $this->travel(2)->seconds();
        $payload = $this->payload($first);
        $receipt = $this->postJson(route('stories.store'), $payload)->assertStatus(202)->assertJsonMissingPath('deletion_token');
        $before = Story::firstOrFail()->getRawOriginal();
        config(['stories.policy_version' => StoryAvailability::REQUIRED_POLICY_VERSION]);
        $this->postJson(route('stories.store'), array_replace($payload, ['policy_version' => config('stories.policy_version')]))->assertStatus(409);
        $renewed = $this->challenge();
        $this->travel(2)->seconds();
        $retry = $this->payload($renewed, ['idempotency_key' => $payload['idempotency_key']]);
        $this->postJson(route('stories.store'), $retry)->assertStatus(202)
            ->assertJsonPath('code', $receipt->json('code'))->assertJsonMissingPath('deletion_token');
        $this->assertSame($before, Story::firstOrFail()->getRawOriginal());
        $this->assertDatabaseCount('stories', 1, 'stories');
        Http::assertNothingSent();
    }
}
