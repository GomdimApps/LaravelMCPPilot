<?php

namespace App\Policies;

use App\Models\Thing;
use App\Models\User;

class ThingPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Thing $thing): bool
    {
        return $thing->user_id === $user->id;
    }
}
