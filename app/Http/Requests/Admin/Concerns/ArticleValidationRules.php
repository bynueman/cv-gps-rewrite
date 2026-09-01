<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Article;
use App\Support\ArticleCategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Shared rules/messages/business-logic checks for Store/UpdateArticleRequest —
 * ports the original Next.js app's validateArticleInput/assertFeaturedCap
 * (src/lib/articleAdmin.ts) plus the slug-change-on-published confirmation
 * from the old PATCH /api/admin/articles/[id] route.
 */
trait ArticleValidationRules
{
    protected function prepareForValidation(): void
    {
        $isEmptyHtml = fn ($html) => trim(strip_tags((string) $html)) === '';

        $titleId = $this->input('title.id');
        $titleEn = $this->input('title.en');
        $excerptId = $this->input('excerpt.id');
        $excerptEn = $this->input('excerpt.en');
        $bodyId = $this->input('body.id');
        $bodyEn = $this->input('body.en');
        $tagsId = $this->input('tags.id');
        $tagsEn = $this->input('tags.en');

        $this->merge([
            'title' => [
                'id' => $titleId ?: $titleEn,
                'en' => $titleEn ?: $titleId,
            ],
            'excerpt' => [
                'id' => $excerptId ?: $excerptEn,
                'en' => $excerptEn ?: $excerptId,
            ],
            'body' => [
                'id' => $isEmptyHtml($bodyId) ? $bodyEn : $bodyId,
                'en' => $isEmptyHtml($bodyEn) ? $bodyId : $bodyEn,
            ],
            'tags' => [
                'id' => $tagsId ?: $tagsEn,
                'en' => $tagsEn ?: $tagsId,
            ],
        ]);
    }

    protected function baseRules(): array
    {
        return [
            'title.id' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],
            'category_slug' => ['required', Rule::in(ArticleCategory::slugs())],
            'excerpt.id' => ['required', 'string'],
            'excerpt.en' => ['required', 'string'],
            'body.id' => ['required', 'string'],
            'body.en' => ['required', 'string'],
            'date' => ['required', 'date'],
            'slug' => ['nullable', 'string', 'max:255'],
            'tags.id' => ['nullable', 'string'],
            'tags.en' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'image_thumb' => ['nullable', 'string'],
            'image_og' => ['nullable', 'string'],
            'featured' => ['boolean'],
            'published' => ['boolean'],
            'confirm_slug_change' => ['boolean'],
        ];
    }

    protected function articleMessages(): array
    {
        return [
            'title.id.required' => 'Judul (ID) wajib diisi.',
            'title.en.required' => 'Judul (EN) wajib diisi.',
            'category_slug.required' => 'Pilih kategori yang valid.',
            'category_slug.in' => 'Pilih kategori yang valid.',
            'excerpt.id.required' => 'Ringkasan (ID) wajib diisi.',
            'excerpt.en.required' => 'Ringkasan (EN) wajib diisi.',
            'date.required' => 'Tanggal tidak valid.',
            'date.date' => 'Tanggal tidak valid.',
        ];
    }

    protected function resolvedSlug(): string
    {
        $raw = (string) ($this->input('slug') ?: $this->input('title.id', ''));

        return Str::slug($raw);
    }

    protected function ensureBodyNotEmpty(Validator $validator): void
    {
        foreach (['id' => 'ID', 'en' => 'EN'] as $lang => $label) {
            $stripped = trim(strip_tags((string) $this->input("body.{$lang}")));
            if ($stripped === '') {
                $validator->errors()->add("body.{$lang}", "Isi artikel ({$label}) wajib diisi.");
            }
        }
    }

    protected function ensureFeaturedCap(Validator $validator, ?int $excludeId): void
    {
        if (! ($this->boolean('featured') && $this->boolean('published'))) {
            return;
        }

        $count = Article::query()
            ->where('featured', true)
            ->where('published', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->count();

        if ($count >= 3) {
            $validator->errors()->add('featured', 'Maksimal 3 artikel featured yang aktif. Nonaktifkan salah satu dulu.');
        }
    }
}
