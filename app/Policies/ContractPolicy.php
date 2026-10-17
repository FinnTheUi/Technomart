<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function view(User $user, Contract $contract): bool
    {
        return $user->id === $contract->tenant_id
            || $user->isAdmin()
            || $this->manage($user, $contract);
    }

    public function sign(User $user, Contract $contract): bool
    {
        return $user->id === $contract->tenant_id;
    }

    public function requestRenewal(User $user, Contract $contract): bool
    {
        return $user->id === $contract->tenant_id;
    }

    /** Renew / terminate: the manager of the leased stall. */
    public function manage(User $user, Contract $contract): bool
    {
        return $user->isManager() && $contract->stall?->manager_id === $user->id;
    }
}
