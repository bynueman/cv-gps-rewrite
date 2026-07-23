<?php

namespace App\Http\Resources;

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
                'id' => \App\Support\ArticleCategory::label($this->category_slug, 'id'),
                'en' => \App\Support\ArticleCategory::label($this->category_slug, 'en'),
            ],
            'title' => ['id' => $this->title_id, 'en' => $this->title_en],
            'excerpt' => ['id' => $this->excerpt_id, 'en' => $this->excerpt_en],
            'body' => [['id' => $this->body_id, 'en' => $this->body_en]],
            'image' => $this->image,
            'imageThumb' => $this->image_thumb,
            'imageOg' => $this->image_og,
            'featured' => $this->featured,
            'tags' => array_values(array_filter(array_map('trim', explode(',', $this->tags_id ?? '')))),
        ];
    }
}
