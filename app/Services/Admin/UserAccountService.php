<?php

namespace App\Services\Admin;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\ElectricBill;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Rental\LeaseService;
use RuntimeException;

/**
 * Removing accounts without destroying financial history.
 *
 * Accounts with billing, lease or order records are deactivated (records are kept for
 * auditing); only accounts with no history are permanently deleted.
 */
class UserAccountService
{
    public const DELETED = 'deleted';

    public const DEACTIVATED = 'deactivated';

    public function __construct(private readonly LeaseService $leases) {}

    /**
     * @return string self::DELETED or self::DEACTIVATED
     */
    public function remove(User $user, User $admin): string
    {
        if ($user->is($admin)) {
            throw new RuntimeException('You cannot delete yourself.');
        }

        if ($this->hasHistory($user)) {
            $this->deactivate($user, $admin, 'Account removed by administrator');

            return self::DEACTIVATED;
        }

        RentalApplication::where('email', $user->email)->delete();

        ActivityLogger::log('deleted_user', $admin->name.' deleted user '.$user->name.' ('.$user->email.')', null,
            ['deleted_user_name' => $user->name, 'deleted_user_email' => $user->email], $admin);

        $user->delete();

        return self::DELETED;
    }

    /**
     * Block sign-in and end any active leases (freeing the stalls). History is kept.
     */
    public function deactivate(User $user, User $admin, string $reason = 'Account deactivated'): void
    {
        if ($user->is($admin)) {
            throw new RuntimeException('You cannot deactivate yourself.');
        }

        $user->update(['is_active' => false]);

        Contract::where('tenant_id', $user->id)
            ->where('status', ContractStatus::Active)
            ->get()
            ->each(fn (Contract $contract) => $this->leases->terminate($contract, $reason, $admin));

        ActivityLogger::log('deactivated_user', $admin->name.' deactivated '.$user->name.' ('.$user->email.')', $user, null, $admin);
    }

    public function activate(User $user, User $admin): void
    {
        $user->update(['is_active' => true]);

        ActivityLogger::log('activated_user', $admin->name.' reactivated '.$user->name.' ('.$user->email.')', $user, null, $admin);
    }

    public function hasHistory(User $user): bool
    {
        return Payment::where('tenant_id', $user->id)->orWhere('manager_id', $user->id)->exists()
            || ElectricBill::where('tenant_id', $user->id)->orWhere('manager_id', $user->id)->exists()
            || Contract::where('tenant_id', $user->id)->exists()
            || Order::where('user_id', $user->id)->exists()
            || Stall::where('manager_id', $user->id)->exists();
    }
}
