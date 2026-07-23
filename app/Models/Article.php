<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'slug', 'date', 'category_slug',
        'title_id', 'title_en', 'excerpt_id', 'excerpt_en',
        'body_id', 'body_en', 'tags_id', 'tags_en',
        'image', 'image_thumb', 'image_og', 'featured', 'published',
    ];

    protected $casts = [
        'date' => 'date',
        'featured' => 'boolean',
        'published' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    /** @return string[] */
    public function tagsIdList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->tags_id ?? ''))));
    }

    /** @return string[] */
    public function tagsEnList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->tags_en ?? ''))));
    }
}
