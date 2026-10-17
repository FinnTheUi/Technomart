<?php

namespace App\Policies;

use App\Models\Stall;
use App\Models\User;

class StallPolicy
{
    public function update(User $user, Stall $stall): bool
    {
        return $user->isManager() && $stall->manager_id === $user->id;
    }
}
