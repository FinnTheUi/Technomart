<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasPaymentWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ElectricBill extends Model implements Payable
{
    use HasPaymentWorkflow;

    protected $fillable = [
        'tenant_id', 'stall_id', 'manager_id',
        'meter_number', 'period_from', 'period_to', 'billing_date',
        'reading_present', 'reading_previous', 'kwh_used', 'rate_per_kwh',
        'total_amount', 'late_fee', 'due_date',
        'prepared_by', 'noted_by',
        'status', 'paid_date',
        'payment_method', 'reference_number', 'proof_path', 'gateway_checkout_id',
        'submitted_at', 'verified_by', 'verified_at', 'rejection_reason', 'receipt_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'period_from' => 'date',
            'period_to' => 'date',
            'billing_date' => 'date',
            'due_date' => 'date',
            'paid_date' => 'datetime',
            'reading_present' => 'decimal:2',
            'reading_previous' => 'decimal:2',
            'kwh_used' => 'decimal:2',
            'rate_per_kwh' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'late_fee' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // ── Payable ──

    public static function payableType(): string
    {
        return 'electric_bill';
    }

    public function baseAmount(): float
    {
        return (float) $this->total_amount;
    }

    public function paymentDescription(): string
    {
        return 'Electricity - '.($this->stall->name ?? 'Stall')
            .' ('.$this->period_from->format('M d').' to '.$this->period_to->format('M d, Y').')';
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
}
