<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ArticleValidationRules;
use App\Models\Article;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
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

            /** @var Article $existing */
            $existing = $this->route('article');
            $slug = $this->resolvedSlug();

            if ($slug === '') {
                $validator->errors()->add('slug', 'Slug tidak valid.');
            } elseif ($slug !== $existing->slug) {
                $conflict = Article::where('slug', $slug)->where('id', '!=', $existing->id)->exists();

                if ($conflict) {
                    $validator->errors()->add('slug', 'Slug sudah digunakan artikel lain.');
                } elseif ($existing->published && ! $this->boolean('confirm_slug_change')) {
                    $validator->errors()->add(
                        'slug',
                        'Artikel ini sudah dipublikasikan — mengubah slug akan mengubah URL publik. Centang konfirmasi untuk melanjutkan.'
                    );
                }
            }

            $this->ensureFeaturedCap($validator, $existing->id);
        });
    }
}
