<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared columns and helpers for payable documents (see App\Contracts\Payable).
 * State changes are orchestrated by App\Services\Billing\PaymentService.
 */
trait HasPaymentWorkflow
{
    public function initializeHasPaymentWorkflow(): void
    {
        $this->mergeCasts([
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ]);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function lateFee(): float
    {
        return (float) ($this->late_fee ?? 0);
    }

    public function amountDue(): float
    {
        return round($this->baseAmount() + $this->lateFee(), 2);
    }

    /** Amount before late fees. */
    abstract public function baseAmount(): float;
}
