<?php

namespace App\Http\Resources;

use App\Rules\ShopeeUrl;
use App\Support\BilingualLists;
use App\Support\LocalizedContent;
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
            'name' => LocalizedContent::pair($this->resource, 'name'),
            'short' => LocalizedContent::pair($this->resource, 'short'),
            'personality' => LocalizedContent::pair($this->resource, 'personality'),
            'serving' => $this->serving_id ? LocalizedContent::pair($this->resource, 'serving') : null,
            'description' => LocalizedContent::pair($this->resource, 'description'),
            'highlights' => array_map(fn ($item) => ['key' => $item['key'], ...LocalizedContent::pair($this->resource, 'highlights.'.$item['key'])], BilingualLists::keyed(is_array($this->highlights) ? $this->highlights : [])),
            'notes' => array_map(fn ($item) => ['key' => $item['key'], ...LocalizedContent::pair($this->resource, 'notes.'.$item['key'])], BilingualLists::keyed(is_array($this->notes) ? $this->notes : [])),
            'color' => $this->color,
            'colorDark' => $this->color_dark,
            'image' => $this->image,
            'imageThumb' => $this->image_thumb,
            'imageAlt' => LocalizedContent::pair($this->resource, 'image_alt'),
            'netWeight' => $this->net_weight,
            'shopeeUrl' => ShopeeUrl::isValid($this->shopee_url) ? $this->shopee_url
                : (ShopeeUrl::isValid(config('company.shopee_storefront')) ? config('company.shopee_storefront') : null),
            'featured' => $this->featured,
            'placeholder' => $this->placeholder,
        ];
    }
}
