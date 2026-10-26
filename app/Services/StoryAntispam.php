<?php

namespace App\Services;

use App\Http\Requests\StoreStoryRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use JsonException;

class StoryAntispam
{
    private const LIFETIME = 300;

    private const MINIMUM_AGE = 2;

    private const MAX_ATTEMPTS = 3;

    public function __construct(private TurnstileVerifier $turnstile) {}

    public function provider(): string
    {
        $provider = config('stories.antispam_provider');
        abort_unless(in_array($provider, ['local', 'turnstile'], true), 503, 'Verifikasi belum tersedia.');

        return $provider;
    }

    public function issue(Request $request): array
    {
        abort_unless($this->provider() === 'local', 404);
        $this->ensureLocalStore($request);
        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $payload = ['purpose' => 'story_form', 'nonce' => bin2hex(random_bytes(16)),
            'issued_at' => now()->timestamp, 'session' => $this->sessionHash($request)];
        abort_unless(Cache::put('story-challenge:'.$payload['nonce'],
            ['answer' => $left + $right, 'attempts' => 0], self::LIFETIME), 503, 'Verifikasi belum tersedia.');

        return ['token' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'question' => $left.' + '.$right, 'expires_in' => self::LIFETIME, 'wait_seconds' => self::MINIMUM_AGE];
    }

    public function verify(StoreStoryRequest $request, string $requestHash): void
    {
        if ($this->provider() === 'turnstile') {
            abort_unless($this->turnstile->verify((string) $request->input('turnstile_token')), 422,
                'Verifikasi keamanan gagal. Silakan coba kembali.');

            return;
        }
        $this->ensureLocalStore($request);
        try {
            $payload = json_decode(Crypt::decryptString($request->input('challenge_token')), true, 8, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            $this->invalidChallenge();
        }
        if (! is_array($payload) || ($payload['purpose'] ?? null) !== 'story_form'
            || ! is_string($payload['nonce'] ?? null) || ! preg_match('/^[a-f0-9]{32}$/', $payload['nonce'])
            || ! is_int($payload['issued_at'] ?? null) || ! is_string($payload['session'] ?? null)
            || ! hash_equals($this->sessionHash($request), $payload['session'])) {
            $this->invalidChallenge();
        }
        $age = now()->timestamp - $payload['issued_at'];
        if ($age < 0 || $age >= self::LIFETIME) {
            $this->invalidChallenge();
        }
        if ($age < self::MINIMUM_AGE) {
            throw ValidationException::withMessages(['challenge_answer' => 'Tunggu sebentar sebelum mengirim jawaban.']);
        }
        $key = 'story-challenge:'.$payload['nonce'];
        $verified = Cache::lock($key.':lock', 5)->get(function () use ($key, $age, $request, $requestHash): bool {
            $record = Cache::get($key);
            if (! is_array($record)) {
                $this->invalidChallenge();
            }
            if (isset($record['request_key'])) {
                // A consumed challenge authorizes only an identical idempotent retry, never a new submission.
                if ($record['request_key'] !== $request->input('idempotency_key')) {
                    $this->invalidChallenge();
                }
                abort_unless(hash_equals($record['request_hash'], $requestHash), 409, 'Kode kiriman sudah digunakan.');

                return true;
            }
            if ($record['attempts'] >= self::MAX_ATTEMPTS) {
                $this->invalidChallenge();
            }
            $record['attempts']++;
            if ((int) $request->input('challenge_answer') === $record['answer']) {
                $record = ['request_key' => $request->input('idempotency_key'), 'request_hash' => $requestHash];
            } else {
                abort_unless(Cache::put($key, $record, self::LIFETIME - $age), 503, 'Verifikasi belum tersedia.');
                if ($record['attempts'] >= self::MAX_ATTEMPTS) {
                    $this->invalidChallenge();
                }
                throw ValidationException::withMessages(['challenge_answer' => 'Jawaban belum tepat. Coba kembali atau minta soal baru.']);
            }
            abort_unless(Cache::put($key, $record, self::LIFETIME - $age), 503, 'Verifikasi belum tersedia.');

            return true;
        });
        abort_unless($verified, 429, 'Verifikasi sedang diproses. Coba lagi sebentar.');
    }

    private function sessionHash(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }

    private function ensureLocalStore(Request $request): void
    {
        abort_unless($request->hasSession() && $request->session()->getId() !== '', 503, 'Sesi verifikasi belum tersedia.');
        if (app()->environment('production')) {
            abort_unless(in_array(config('session.driver'), ['file', 'database'], true)
                && in_array(config('cache.stores.'.config('cache.default').'.driver'), ['file', 'database'], true),
                503, 'Penyimpanan verifikasi belum siap.');
        }
    }

    private function invalidChallenge(): never
    {
        throw ValidationException::withMessages(['challenge_token' => 'Soal tidak berlaku atau sudah digunakan. Silakan minta soal baru.']);
    }
}
