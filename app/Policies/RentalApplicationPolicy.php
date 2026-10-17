<?php

namespace App\Policies;

use App\Models\RentalApplication;
use App\Models\User;

class RentalApplicationPolicy
{
    /** Approve / reject: the manager of the requested stall. */
    public function review(User $user, RentalApplication $application): bool
    {
        return $user->isManager() && $application->stall?->manager_id === $user->id;
    }
}
