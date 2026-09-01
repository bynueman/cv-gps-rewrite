<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    protected $fillable = [
        'name',
        'issuer',
        'category',
        'logo',
        'pdf_url',
        'valid_until',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'valid_until' => 'date:Y-m-d',
        'sort_order'  => 'integer',
    ];
}
