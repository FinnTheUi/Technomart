<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * A document a tenant pays: rent bill, electric bill or supply order.
 * Implemented through App\Models\Concerns\HasPaymentWorkflow.
 */
interface Payable
{
    /** Short, stable identifier used in gateway metadata and URLs (e.g. "payment"). */
    public static function payableType(): string;

    /** Amount the payer owes now, including any late fee. */
    public function amountDue(): float;

    /** Human-readable description, e.g. "Rent — Stall A (due Oct 01, 2026)". */
    public function paymentDescription(): string;

    /** The user who owes this document. */
    public function payer(): ?User;

    /**
     * Staff who verify submitted payments for this document.
     *
     * @return Collection<int, User>
     */
    public function reviewers(): Collection;

    public function canBePaid(): bool;

    public function isAwaitingVerification(): bool;

    public function isPaid(): bool;

    /** Move to "for verification" (the payer submitted a reference). */
    public function markAsSubmitted(): void;

    /** Move to the paid state. */
    public function markAsPaid(): void;

    /** Return to the unpaid state after a rejected submission. */
    public function markAsUnpaid(): void;
}
