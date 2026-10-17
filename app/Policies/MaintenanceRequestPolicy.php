<?php

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenanceRequestPolicy
{
    public function view(User $user, MaintenanceRequest $request): bool
    {
        return $user->id === $request->tenant_id || $this->update($user, $request);
    }

    /** Only the manager of the affected stall handles the request. */
    public function update(User $user, MaintenanceRequest $request): bool
    {
        return $user->isManager() && $request->stall?->manager_id === $user->id;
    }
}
