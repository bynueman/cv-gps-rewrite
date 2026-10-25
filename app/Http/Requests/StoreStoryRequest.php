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

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(Story::CATEGORIES)],
            'body' => ['required', 'string', 'min:20', 'max:1500', 'not_regex:/<[^>]+>/'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'terms_consent' => ['required', 'accepted'],
            'publication_consent' => ['required_if:visibility,public', 'boolean', 'accepted_if:visibility,public'],
            'social_consent' => ['sometimes', 'boolean'],
            'idempotency_key' => ['required', 'uuid'],
            'turnstile_token' => ['required', 'string', 'max:2048'],
        ];
    }
}
