<?php

namespace App\Services\Rental;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use App\Mail\TenantCredentialsMail;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use App\Notifications\ApplicationApprovedNotification;
use App\Services\Billing\PaymentService;
use App\Support\SafeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Turns an approved rental application into a tenant account, a lease contract,
 * and the monthly rent schedule, atomically.
 *
 * Approval confirms the first month's payment: an online payment already cleared,
 * a GCash reference the manager checked, or cash paid in person at the office.
 */
class RentalApplicationApprover
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly LeaseService $leases,
    ) {}

    /**
     * @return bool whether the approval email reached the applicant's mail server
     *
     * @throws RuntimeException when the application or stall is not in an approvable state
     */
    public function approve(RentalApplication $application, Stall $stall, User $manager): bool
    {
        [$tenant, $contract, $firstMonth, $isNewUser] = DB::transaction(function () use ($application, $stall) {
            // Lock both rows so two managers (or a double-click) cannot approve concurrently.
            $application = RentalApplication::lockForUpdate()->findOrFail($application->id);
            $stall = Stall::lockForUpdate()->findOrFail($stall->id);

            if (! $application->isPending()) {
                throw new RuntimeException('This application has already been processed.');
            }

            if (! $stall->isAvailable()) {
                throw new RuntimeException('This stall is no longer available.');
            }

            [$tenant, $isNewUser] = $this->resolveTenant($application);
            [$contract, $firstMonth] = $this->leases->startLease($tenant, $stall, $application->rental_duration, $this->terms($application));

            $application->update([
                'status' => ApplicationStatus::Approved,
                'payment_status' => PaymentStatus::Paid,
                'generated_username' => $application->email,
                'generated_password' => null,
            ]);

            return [$tenant, $contract, $firstMonth, $isNewUser];
        });

        $application->refresh();

        // Record the first month as paid with an official receipt.
        $this->payments->settle(
            $firstMonth,
            $application->payment_method === 'in_person' ? PaymentService::METHOD_CASH : ($application->payment_method ?? PaymentService::METHOD_CASH),
            $application->gcash_reference,
            $manager,
        );

        // Side effects run only after the transaction has committed.
        if ($isNewUser) {
            SafeMail::attempt(fn () => event(new Registered($tenant)), ['user' => $tenant->id]); // verification link
        }
        $tenant->notify(new ApplicationApprovedNotification($application, $stall)); // in-app

        return $this->sendApprovalEmail($application, $contract, $firstMonth->fresh());
    }

    /**
     * The applicant has no other way to learn they were approved, so this email is sent
     * immediately rather than queued. A mail failure never undoes the approval.
     */
    private function sendApprovalEmail(RentalApplication $application, Contract $contract, Payment $firstMonth): bool
    {
        try {
            Mail::to($application->email)->sendNow(new TenantCredentialsMail($application, $contract, $firstMonth));

            return true;
        } catch (Throwable $e) {
            Log::error('Approval email could not be sent.', ['application' => $application->id, 'email' => $application->email, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @return array{0: User, 1: bool}
     */
    private function resolveTenant(RentalApplication $application): array
    {
        $user = User::where('email', $application->email)->first();

        if ($user && ! $user->isTenant()) {
            throw new RuntimeException('This email belongs to a staff account and cannot be used as a tenant.');
        }

        if ($user) {
            $user->update(['is_active' => true]);

            return [$user, false];
        }

        return [User::create([
            'name' => $application->name,
            'email' => $application->email,
            'phone_number' => $application->phone,
            'address' => $application->address,
            'password' => $application->password_hash, // already hashed; the 'hashed' cast leaves it as-is
            'role' => User::ROLE_TENANT,
            'is_active' => true,
        ]), true];
    }

    private function terms(RentalApplication $application): string
    {
        $paid = match ($application->payment_method) {
            'gcash' => 'First month paid via GCash.',
            'online' => 'First month paid online.',
            default => 'First month paid in person.',
        };

        return 'Rental duration: '.$application->rental_duration.' month(s). '.$paid;
    }
}
