<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cached late-payment risk for a tenant, per manager (refreshed daily by `ml:score`).
 */
class TenantRiskScore extends Model
{
    protected $fillable = ['tenant_id', 'manager_id', 'score', 'level', 'source', 'factors', 'computed_at'];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'factors' => 'array',
            'computed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
