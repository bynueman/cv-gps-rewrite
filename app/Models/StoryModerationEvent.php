<?php

namespace App\Models;

class StoryModerationEvent extends StoryRecord
{
    protected $table = 'story_moderation_events';

    protected $guarded = ['id'];

    protected $hidden = ['fingerprint'];
}
