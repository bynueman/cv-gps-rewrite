<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $fillable = [
        'path',
        'viewed_on',
        'browser',
        'device',
        'referrer_domain',
        'visitor_hash',
    ];

    protected $casts = [
        'viewed_on' => 'date',
    ];
}
