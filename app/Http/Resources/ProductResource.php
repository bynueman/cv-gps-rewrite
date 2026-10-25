<?php

namespace App\Http\Resources;

use App\Rules\ShopeeUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reconstructs the nested { id, en } Bilingual shape from the flat
 * _id/_en DB columns, matching the original content.ts `Product` type
 * exactly — lets ProductDetail/ProductCard/ProductPackshot port with
 * no data-access logic changes.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'brand' => $this->brand->key,
            'group' => $this->group,
            'packaging' => $this->packaging,
            'name' => ['id' => $this->name_id, 'en' => $this->name_en],
            'short' => ['id' => $this->short_id, 'en' => $this->short_en],
            'personality' => ['id' => $this->personality_id, 'en' => $this->personality_en],
            'serving' => $this->serving_id ? ['id' => $this->serving_id, 'en' => $this->serving_en] : null,
            'description' => ['id' => $this->description_id, 'en' => $this->description_en],
            'highlights' => $this->highlights,
            'notes' => $this->notes,
            'color' => $this->color,
            'colorDark' => $this->color_dark,
            'image' => $this->image,
            'imageThumb' => $this->image_thumb,
            'imageAlt' => ['id' => $this->image_alt_id, 'en' => $this->image_alt_en],
            'netWeight' => $this->net_weight,
            'shopeeUrl' => ShopeeUrl::isValid($this->shopee_url) ? $this->shopee_url
                : (ShopeeUrl::isValid(config('company.shopee_storefront')) ? config('company.shopee_storefront') : null),
            'featured' => $this->featured,
            'placeholder' => $this->placeholder,
        ];
    }
}
