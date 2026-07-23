<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'brand_id', 'slug', 'group', 'packaging',
        'name_id', 'name_en', 'short_id', 'short_en',
        'personality_id', 'personality_en', 'serving_id', 'serving_en',
        'description_id', 'description_en', 'highlights', 'notes',
        'color', 'color_dark', 'image', 'image_thumb', 'image_og',
        'featured', 'placeholder', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'highlights' => 'array',
        'notes' => 'array',
        'featured' => 'boolean',
        'placeholder' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Same brand, exclude self, prefer same packaging (Putri Teko only —
     * Kuicip has no packaging dimension so this is simply "same brand,
     * exclude self"), limit 3. Mirrors the original app's
     * relatedProducts(product, count = 3) helper.
     */
    public function scopeRelatedTo(Builder $query, Product $product, int $count = 3): Builder
    {
        return $query
            ->where('brand_id', $product->brand_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->orderByRaw('packaging = ? DESC', [$product->packaging])
            ->limit($count);
    }
}
