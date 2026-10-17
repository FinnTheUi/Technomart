<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Contract extends Model
{
    protected $fillable = [
        'tenant_id', 'stall_id', 'start_date', 'end_date', 'terms', 'status',
        'signed_at', 'signed_ip', 'signed_user_agent', 'document_hash',
        'renewal_requested_at', 'renewal_months_requested', 'terminated_at', 'termination_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_at' => 'datetime',
            'renewal_requested_at' => 'datetime',
            'terminated_at' => 'datetime',
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

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === ContractStatus::Active;
    }

    public function hasPendingRenewalRequest(): bool
    {
        return $this->renewal_requested_at !== null;
    }

    public function latestActivity(): MorphOne
    {
        return $this->morphOne(ActivityLog::class, 'subject')->latestOfMany();
    }
}
