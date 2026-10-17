<?php

namespace App\Policies;

use App\Models\ElectricBill;
use App\Models\Payment;
use App\Models\User;

/**
 * Shared rules for tenant bills (rent and electricity).
 */
abstract class BillPolicy
{
    public function view(User $user, Payment|ElectricBill $bill): bool
    {
        return $user->id === $bill->tenant_id
            || $user->id === $bill->manager_id
            || $user->isAdmin();
    }

    /** The tenant who owes the bill may pay it. */
    public function pay(User $user, Payment|ElectricBill $bill): bool
    {
        return $user->id === $bill->tenant_id;
    }

    /** The bill's manager records cash, verifies/rejects references, and marks overdue. */
    public function review(User $user, Payment|ElectricBill $bill): bool
    {
        return $user->isManager() && $user->id === $bill->manager_id;
    }
}
