<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['tenant_id', 'manager_id', 'stall_id', 'amount', 'due_date', 'paid_date', 'status', 'receipt_url'];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }
}
