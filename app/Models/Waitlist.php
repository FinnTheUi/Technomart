<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    protected $fillable = ['stall_id', 'email', 'name', 'phone', 'status', 'notified_at'];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }
}
