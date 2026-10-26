<?php

namespace App\Http\Resources;

use App\Support\ArticleCategory;
use App\Support\LocalizedContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reconstructs the nested { id, en } Bilingual shape matching the
 * original content.ts `Article` type. `body` holds a single-element
 * array (sanitized rich-text HTML per language), matching the Next.js
 * app's articleFromDb() contract. Exposes all three image variants —
 * callers pick image vs imageThumb depending on context (detail vs
 * card grid), mirroring articleFromDb() vs articleCardFromDb().
 */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'date' => $this->date->toIso8601String(),
            'category' => [
                'id' => ArticleCategory::label($this->category_slug, 'id'),
                'en' => ArticleCategory::label($this->category_slug, 'en'),
            ],
            'title' => LocalizedContent::pair($this->resource, 'title'),
            'excerpt' => LocalizedContent::pair($this->resource, 'excerpt'),
            'body' => [LocalizedContent::pair($this->resource, 'body')],
            'image' => $this->image,
            'imageThumb' => $this->image_thumb,
            'imageOg' => $this->image_og,
            'featured' => $this->featured,
            'tagsLocalized' => array_map(fn ($tag, $index) => ['id' => $tag, 'en' => $this->tagsEnList()[$index] ?? '', 'en_stale' => LocalizedContent::pair($this->resource, 'tags')['en_stale']], $this->tagsIdList(), array_keys($this->tagsIdList())),
            'tags' => array_values(array_filter(array_map('trim', explode(',', $this->tags_id ?? '')))),
        ];
    }
}
