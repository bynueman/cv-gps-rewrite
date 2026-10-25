<?php

namespace App\Models;

class StoryReport extends StoryRecord
{
    protected $table = 'story_reports';

    protected $guarded = ['id'];

    protected $hidden = ['fingerprint'];
}
