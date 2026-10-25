<?php

namespace App\Models;

use App\Support\StoryDatabase;
use Illuminate\Database\Eloquent\Model;

abstract class StoryRecord extends Model
{
    protected $connection = 'stories';

    public function getConnection()
    {
        StoryDatabase::ensureConfigured();

        return parent::getConnection();
    }
}
