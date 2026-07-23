<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = [
        'key', 'name', 'tag_id', 'tag_en', 'weight', 'story_id', 'story_en', 'href',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
