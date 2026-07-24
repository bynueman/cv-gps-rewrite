<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'category',
        'logo',
        'sort_order',
        'is_active',
        'featured',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'featured' => 'boolean',
    ];
}
