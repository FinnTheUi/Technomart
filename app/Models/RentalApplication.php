<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalApplication extends Model
{
    protected $fillable = [
        'stall_id', 'name', 'email', 'phone', 'address',
        'rental_duration', 'payment_method', 'payment_status',
        'status', 'gcash_reference', 'generated_username', 'generated_password',
    ];

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }
}
