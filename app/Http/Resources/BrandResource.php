<?php

namespace App\Http\Resources;

use App\Support\LocalizedContent;
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
            'tag' => LocalizedContent::pair($this->resource, 'tag'),
            'weight' => $this->weight,
            'story' => LocalizedContent::pair($this->resource, 'story'),
            'href' => $this->href,
        ];
    }
}
