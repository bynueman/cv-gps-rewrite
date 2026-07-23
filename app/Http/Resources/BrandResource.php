<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reconstructs the nested { id, en } Bilingual shape the original
 * Next.js app's content.ts used, from the flat _id/_en DB columns —
 * lets the ported React components (Header, Footer, ProductFamilies,
 * etc.) read brand.tag[lang] exactly as before.
 */
class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'tag' => ['id' => $this->tag_id, 'en' => $this->tag_en],
            'weight' => $this->weight,
            'story' => ['id' => $this->story_id, 'en' => $this->story_en],
            'href' => $this->href,
        ];
    }
}
