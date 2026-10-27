<?php

namespace App\Http\Requests;

use App\Models\Story;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $body = $this->input('body');
        if (is_string($body)) {
            $body = trim(str_replace(["\r\n", "\r"], "\n", $body));
            $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body);
            $this->merge(['body' => $body]);
        }
    }

    public function messages(): array
    {
        return [
            'policy_version.required' => 'Panduan berubah. Muat ulang halaman dan tinjau persetujuan sebelum mengirim.',
            'policy_version.in' => 'Panduan berubah. Muat ulang halaman dan tinjau persetujuan sebelum mengirim.',
            'contact_website.prohibited' => 'Verifikasi gagal. Silakan coba kembali.',
            'challenge_token.required' => 'Minta soal verifikasi terlebih dahulu.',
            'challenge_answer.required' => 'Isi jawaban verifikasi.',
            'challenge_answer.regex' => 'Jawaban verifikasi harus berupa angka.',
        ];
    }

    public function rules(): array
    {
        return [
            'policy_version' => ['required', 'string', 'max:80', Rule::in([config('stories.policy_version')])],
            'category' => ['required', Rule::in(Story::CATEGORIES)],
            'body' => ['required', 'string', 'min:20', 'max:1500', 'not_regex:/<[^>]+>/'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'terms_consent' => ['required', 'accepted'],
            'publication_consent' => ['required_if:visibility,public', 'boolean', 'accepted_if:visibility,public'],
            'social_consent' => ['sometimes', 'boolean'],
            'idempotency_key' => ['required', 'uuid'],
            'contact_website' => ['prohibited'],
            'challenge_token' => [Rule::requiredIf(config('stories.antispam_provider') === 'local'), 'nullable', 'string', 'max:2048'],
            'challenge_answer' => [Rule::requiredIf(config('stories.antispam_provider') === 'local'), 'nullable', 'string', 'regex:/^[0-9]{1,3}$/'],
            'turnstile_token' => [Rule::requiredIf(config('stories.antispam_provider') === 'turnstile'), 'nullable', 'string', 'max:2048'],
        ];
    }
}
