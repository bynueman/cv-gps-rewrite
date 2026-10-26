<?php

namespace App\Models;

use App\Models\Concerns\HasContentTranslation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasContentTranslation;

    protected $with = ['translationState'];

    protected $fillable = [
        'key', 'name', 'tag_id', 'tag_en', 'weight', 'story_id', 'story_en', 'href',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
