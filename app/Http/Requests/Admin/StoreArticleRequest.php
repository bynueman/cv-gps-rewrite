<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ArticleValidationRules;
use App\Models\Article;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    use ArticleValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    public function messages(): array
    {
        return $this->articleMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->ensureBodyNotEmpty($validator);

            $slug = $this->resolvedSlug();
            if ($slug === '') {
                $validator->errors()->add('slug', 'Slug tidak valid.');
            } elseif (Article::where('slug', $slug)->exists()) {
                $validator->errors()->add('slug', 'Slug sudah digunakan artikel lain.');
            }

            $this->ensureFeaturedCap($validator, null);
        });
    }
}
