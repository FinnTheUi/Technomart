<?php

namespace App\Services\Billing;

use App\Contracts\Payable;
use App\Enums\PaymentStatus;
use App\Models\ElectricBill;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\OverduePaymentNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\PaymentSubmittedNotification;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

/**
 * The single place where payable documents change payment state:
 *
 *   unpaid ──submit reference──► for verification ──verify──► paid (receipt issued)
 *      ▲                                 │
 *      └──────────────reject─────────────┘
 *
 *   unpaid ──cash recorded by staff / gateway webhook──► paid
 */
class PaymentService
{
    public const METHOD_GCASH = 'gcash';

    public const METHOD_CASH = 'cash';

    public const METHOD_ONLINE = 'online';

    public function __construct(private readonly DocumentNumberGenerator $numbers) {}

    /**
     * The payer submits an e-wallet reference number (and optional screenshot) for staff to verify.
     *
     * @param  Model&Payable  $payable
     */
    public function submitReference(Payable $payable, User $payer, string $referenceNumber, ?UploadedFile $proof = null): void
    {
        DB::transaction(function () use ($payable, $referenceNumber, $proof) {
            $locked = $this->lock($payable);

            if (! $locked->canBePaid()) {
                throw new RuntimeException($locked->isAwaitingVerification()
                    ? 'A payment for this bill is already awaiting verification.'
                    : 'This bill is already paid.');
            }

            $locked->forceFill([
                'payment_method' => self::METHOD_GCASH,
                'reference_number' => $referenceNumber,
                'proof_path' => $proof?->store('payment-proofs', 'local'),
                'submitted_at' => now(),
                'rejection_reason' => null,
            ]);
            $locked->markAsSubmitted();
            $locked->save();

            $payable->setRawAttributes($locked->getAttributes(), true);
        });

        ActivityLogger::log('submitted_payment', $payer->name.' submitted GCash reference '.$referenceNumber.' for '.$payable->paymentDescription(), $payable, null, $payer);

        Notification::send($payable->reviewers(), new PaymentSubmittedNotification($payable));
    }

    /**
     * Confirm a submitted reference (staff verification).
     *
     * @param  Model&Payable  $payable
     */
    public function verify(Payable $payable, User $verifier): void
    {
        if (! $payable->isAwaitingVerification()) {
            throw new RuntimeException('There is no submitted payment to verify.');
        }

        $this->settle($payable, (string) $payable->payment_method, $payable->reference_number, $verifier);
    }

    /**
     * Reject a submitted reference; the document returns to unpaid and the payer is told why.
     *
     * @param  Model&Payable  $payable
     */
    public function reject(Payable $payable, User $verifier, string $reason): void
    {
        DB::transaction(function () use ($payable, $verifier, $reason) {
            $locked = $this->lock($payable);

            if (! $locked->isAwaitingVerification()) {
                throw new RuntimeException('There is no submitted payment to reject.');
            }

            $locked->forceFill([
                'rejection_reason' => $reason,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ]);
            $locked->markAsUnpaid();
            $locked->save();

            $payable->setRawAttributes($locked->getAttributes(), true);
        });

        ActivityLogger::log('rejected_payment', $verifier->name.' rejected payment reference '.$payable->reference_number.' for '.$payable->paymentDescription(), $payable, ['reason' => $reason], $verifier);

        $payable->payer()?->notify(new PaymentRejectedNotification($payable));
    }

    /**
     * Mark a document as paid and issue an official receipt number.
     * Used for verified references, cash recorded by staff, and gateway webhooks.
     * Idempotent: settling an already-paid document is a no-op.
     *
     * @param  Model&Payable  $payable
     */
    public function settle(Payable $payable, string $method, ?string $reference = null, ?User $verifier = null): bool
    {
        $settled = DB::transaction(function () use ($payable, $method, $reference, $verifier) {
            $locked = $this->lock($payable);

            if ($locked->isPaid()) {
                return false;
            }

            $locked->forceFill([
                'payment_method' => $method,
                'reference_number' => $reference ?? $locked->reference_number,
                'verified_by' => $verifier?->id,
                'verified_at' => now(),
                'rejection_reason' => null,
                'receipt_number' => $this->numbers->nextReceiptNumber(),
            ]);
            $locked->markAsPaid();
            $locked->save();

            $payable->setRawAttributes($locked->getAttributes(), true);

            return true;
        });

        if (! $settled) {
            return false;
        }

        ActivityLogger::log(
            'payment_settled',
            ($verifier?->name ?? 'Payment gateway').' recorded '.strtoupper($method).' payment of PHP '
                .number_format($payable->amountDue(), 2).' for '.$payable->paymentDescription()
                .' (receipt '.$payable->receipt_number.')',
            $payable,
            ['method' => $method, 'reference' => $reference],
            $verifier,
        );

        $payable->payer()?->notify(new PaymentReceivedNotification($payable));

        return true;
    }

    /**
     * Mark a rent/electric bill overdue, apply the late fee once, and notify the tenant.
     */
    public function markOverdue(Payment|ElectricBill $bill, ?User $actor = null): void
    {
        $changed = DB::transaction(function () use ($bill) {
            $locked = $this->lock($bill);
            if ($locked->isPaid() || $locked->isAwaitingVerification()) {
                return false;
            }

            if ($locked->lateFee() == 0) {
                $locked->late_fee = $this->lateFeeFor($locked->baseAmount());
            }

            $locked->status = PaymentStatus::Overdue;
            $locked->save();
            $bill->setRawAttributes($locked->getAttributes(), true);

            return true;
        });

        if (! $changed) {
            return;
        }

        if ($actor) {
            ActivityLogger::log('marked_overdue', $actor->name.' marked '.$bill->paymentDescription().' as overdue', $bill, null, $actor);
        }

        $bill->tenant?->notify(new OverduePaymentNotification($bill));
    }

    public function lateFeeFor(float $amount): float
    {
        $config = config('billing.late_fee');

        return round($amount * ((float) $config['percent']) / 100 + (float) $config['fixed'], 2);
    }

    /**
     * @param  Model&Payable  $payable
     * @return Model&Payable
     */
    private function lock(Payable $payable): Payable
    {
        return $payable::query()->lockForUpdate()->findOrFail($payable->getKey());
    }
}
