<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'tenant_id', 'stall_id', 'start_date', 'end_date', 'terms', 'status',
        'signed_at', 'signed_ip', 'signed_user_agent', 'document_hash',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_at' => 'datetime',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }
}
