<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageViewDaily extends Model
{
    protected $table = 'page_view_daily';

    protected $fillable = [
        'date',
        'path',
        'hits',
        'uniques',
        'browser_json',
        'device_json',
    ];

    protected $casts = [
        'date' => 'date',
        'browser_json' => 'array',
        'device_json' => 'array',
    ];
}
