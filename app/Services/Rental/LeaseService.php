<?php

namespace App\Services\Rental;

use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Enums\StallStatus;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
use App\Notifications\LeaseStatusNotification;
use App\Services\ActivityLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Lease lifecycle after approval: renewal requests, renewals, terminations and expiry.
 * Whenever a stall becomes free, its waitlist is notified.
 */
class LeaseService
{
    public function __construct(private readonly WaitlistService $waitlist) {}

    /**
     * Create an active lease starting today and bill every month of it. The stall becomes occupied.
     * Must be called inside a transaction that has locked the stall.
     *
     * @return array{0: Contract, 1: Payment} the contract and its first month's bill
     */
    public function startLease(User $tenant, Stall $stall, int $months, string $terms): array
    {
        if ($stall->hasActiveLease()) {
            throw new RuntimeException('This stall already has an active lease.');
        }

        $start = today();

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'stall_id' => $stall->id,
            'start_date' => $start,
            'end_date' => $start->copy()->addMonths($months),
            'terms' => $terms,
            'status' => ContractStatus::Active,
        ]);

        $first = null;
        for ($month = 0; $month < $months; $month++) {
            $bill = $this->billMonth($contract->setRelation('stall', $stall), $start->copy()->addMonths($month));
            $first ??= $bill;
        }

        $stall->update(['status' => StallStatus::Occupied]);

        return [$contract, $first];
    }

    /**
     * Manager places an existing tenant (walk-in / offline agreement) into an available stall.
     */
    public function assignTenant(Stall $stall, User $tenant, int $months, User $manager): Contract
    {
        if (! $tenant->isTenant() || ! $tenant->is_active) {
            throw new RuntimeException('Only active tenant accounts can be assigned to a stall.');
        }

        $contract = DB::transaction(function () use ($stall, $tenant, $months) {
            $stall = Stall::lockForUpdate()->findOrFail($stall->id);

            if ($stall->status === StallStatus::Maintenance) {
                throw new RuntimeException('This stall is under maintenance. Put it back in service first.');
            }

            [$contract] = $this->startLease($tenant, $stall, $months, "Rental duration: {$months} month(s). Assigned by the stall manager.");

            return $contract;
        });

        ActivityLogger::log('assigned_tenant', $manager->name.' assigned '.$tenant->name.' to "'.$stall->name.'" for '.$months.' month(s)', $contract, null, $manager);

        return $contract;
    }

    public function requestRenewal(Contract $contract, int $months, User $tenant): void
    {
        if (! $contract->isActive()) {
            throw new RuntimeException('Only active leases can be renewed.');
        }

        $contract->update([
            'renewal_requested_at' => now(),
            'renewal_months_requested' => $months,
        ]);

        ActivityLogger::log('requested_renewal', $tenant->name.' requested a '.$months.'-month renewal for contract #'.$contract->id, $contract, null, $tenant);

        $contract->loadMissing('stall.manager', 'tenant');
        $contract->stall->manager?->notify(new LeaseStatusNotification($contract, LeaseStatusNotification::RENEWAL_REQUESTED));
    }

    /**
     * Extend the lease and bill the added months.
     */
    public function renew(Contract $contract, int $months, User $manager): void
    {
        DB::transaction(function () use ($contract, $months) {
            $contract = Contract::with('stall')->lockForUpdate()->findOrFail($contract->id);

            if (! $contract->isActive()) {
                throw new RuntimeException('Only active leases can be renewed.');
            }

            $oldEnd = $contract->end_date->copy();

            for ($month = 0; $month < $months; $month++) {
                $this->billMonth($contract, $oldEnd->copy()->addMonths($month));
            }

            $contract->update([
                'end_date' => $oldEnd->copy()->addMonths($months),
                'renewal_requested_at' => null,
                'renewal_months_requested' => null,
            ]);
        });

        $contract->refresh()->load('stall', 'tenant');

        ActivityLogger::log('renewed_contract', $manager->name.' renewed contract #'.$contract->id.' by '.$months.' month(s) until '.$contract->end_date->format('M d, Y'), $contract, ['months' => $months], $manager);

        $contract->tenant?->notify(new LeaseStatusNotification($contract, LeaseStatusNotification::RENEWED));
    }

    /**
     * End a lease early. Unpaid rent for months after today is cancelled; the stall is freed.
     */
    public function terminate(Contract $contract, string $reason, ?User $actor = null): void
    {
        DB::transaction(function () use ($contract, $reason) {
            $contract = Contract::lockForUpdate()->findOrFail($contract->id);

            if (! $contract->isActive()) {
                throw new RuntimeException('This lease is not active.');
            }

            $contract->update([
                'status' => ContractStatus::Terminated,
                'terminated_at' => now(),
                'termination_reason' => $reason,
                'renewal_requested_at' => null,
                'renewal_months_requested' => null,
            ]);

            Payment::where('tenant_id', $contract->tenant_id)
                ->where('stall_id', $contract->stall_id)
                ->where('status', PaymentStatus::Pending)
                ->whereDate('due_date', '>', today())
                ->delete();
        });

        $contract->refresh()->load('stall', 'tenant');

        ActivityLogger::log('terminated_contract', ($actor?->name ?? 'System').' terminated contract #'.$contract->id.': '.$reason, $contract, null, $actor);

        $contract->tenant?->notify(new LeaseStatusNotification($contract, LeaseStatusNotification::TERMINATED));

        $this->releaseStall($contract);
    }

    /**
     * Expire every active lease whose end date has passed. Returns the number expired.
     */
    public function expireEndedLeases(?CarbonInterface $today = null): int
    {
        $today ??= today();

        $ended = Contract::with('stall', 'tenant')
            ->where('status', ContractStatus::Active)
            ->whereDate('end_date', '<', $today)
            ->get();

        foreach ($ended as $contract) {
            $contract->update(['status' => ContractStatus::Expired]);

            ActivityLogger::log('expired_contract', 'Contract #'.$contract->id.' for "'.($contract->stall->name ?? 'stall').'" expired', $contract);

            $contract->tenant?->notify(new LeaseStatusNotification($contract, LeaseStatusNotification::EXPIRED));

            $this->releaseStall($contract);
        }

        return $ended->count();
    }

    private function billMonth(Contract $contract, CarbonInterface $dueDate): Payment
    {
        return Payment::create([
            'tenant_id' => $contract->tenant_id,
            'manager_id' => $contract->stall->manager_id,
            'stall_id' => $contract->stall_id,
            'amount' => $contract->stall->price,
            'due_date' => $dueDate,
            'status' => PaymentStatus::Pending,
        ]);
    }

    private function releaseStall(Contract $contract): void
    {
        $stall = $contract->stall;

        if (! $stall || $stall->hasActiveLease()) {
            return;
        }

        $stall->update(['status' => StallStatus::Available]);
        $this->waitlist->notifyStallAvailable($stall);
    }
}
