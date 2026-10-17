<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasPaymentWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;

/**
 * A monthly rent bill.
 */
class Payment extends Model implements Payable
{
    use HasPaymentWorkflow;

    protected $fillable = [
        'tenant_id', 'manager_id', 'stall_id', 'amount', 'late_fee', 'due_date', 'paid_date', 'status', 'receipt_url',
        'payment_method', 'reference_number', 'proof_path', 'gateway_checkout_id',
        'submitted_at', 'verified_by', 'verified_at', 'rejection_reason', 'receipt_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'due_date' => 'date',
            'paid_date' => 'datetime',
            'amount' => 'decimal:2',
            'late_fee' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class);
    }

    // ── Payable ──

    public static function payableType(): string
    {
        return 'payment';
    }

    public function baseAmount(): float
    {
        return (float) $this->amount;
    }

    public function paymentDescription(): string
    {
        return 'Rent - '.($this->stall->name ?? 'Stall').' (due '.$this->due_date->format('M d, Y').')';
    }

    public function payer(): ?User
    {
        return $this->tenant;
    }

    public function reviewers(): Collection
    {
        return collect([$this->manager])->filter();
    }

    public function canBePaid(): bool
    {
        return $this->status->isPayable();
    }

    public function isAwaitingVerification(): bool
    {
        return $this->status === PaymentStatus::ForVerification;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function markAsSubmitted(): void
    {
        $this->status = PaymentStatus::ForVerification;
    }

    public function markAsPaid(): void
    {
        $this->status = PaymentStatus::Paid;
        $this->paid_date = now();
    }

    public function markAsUnpaid(): void
    {
        $this->status = $this->due_date->lt(today()) ? PaymentStatus::Overdue : PaymentStatus::Pending;
    }

    public function latestActivity(): MorphOne
    {
        return $this->morphOne(ActivityLog::class, 'subject')->latestOfMany();
    }
}
