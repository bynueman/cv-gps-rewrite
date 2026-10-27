<?php

namespace App\Policies;

use App\Models\Story;
use App\Models\User;

class StoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user, Story $story): bool
    {
        return $user->isSuperadmin();
    }

    public function redact(User $user, Story $story): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Story $story): bool
    {
        return $user->isSuperadmin();
    }
}
