<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\OrderStatus;
use App\Models\Concerns\HasPaymentWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;

/**
 * A tenant's supply order. Stock is reserved when the order is placed and
 * returned if the order is cancelled.
 */
class Order extends Model implements Payable
{
    use HasFactory, HasPaymentWorkflow;

    protected $fillable = [
        'user_id', 'product_id', 'quantity', 'total_price', 'status',
        'paid_at', 'delivered_at', 'cancelled_at',
        'payment_method', 'reference_number', 'proof_path', 'gateway_checkout_id',
        'submitted_at', 'verified_by', 'verified_at', 'rejection_reason', 'receipt_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_price' => 'decimal:2',
            'paid_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ── Payable ──

    public static function payableType(): string
    {
        return 'order';
    }

    public function baseAmount(): float
    {
        return (float) $this->total_price;
    }

    public function paymentDescription(): string
    {
        return 'Supply order #'.$this->id.' - '.$this->quantity.' x '.($this->product->name ?? 'item');
    }

    public function payer(): ?User
    {
        return $this->user;
    }

    public function reviewers(): Collection
    {
        return User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get();
    }

    public function canBePaid(): bool
    {
        return $this->status->isPayable();
    }

    public function isAwaitingVerification(): bool
    {
        return $this->status === OrderStatus::ForVerification;
    }

    public function isPaid(): bool
    {
        return in_array($this->status, OrderStatus::sold(), true);
    }

    public function markAsSubmitted(): void
    {
        $this->status = OrderStatus::ForVerification;
    }

    public function markAsPaid(): void
    {
        $this->status = OrderStatus::Paid;
        $this->paid_at = now();
    }

    public function markAsUnpaid(): void
    {
        $this->status = OrderStatus::Pending;
    }

    public function latestActivity(): MorphOne
    {
        return $this->morphOne(ActivityLog::class, 'subject')->latestOfMany();
    }
}
