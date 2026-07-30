<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'stall_id', 'subject', 'description', 'priority', 'status', 'image_path'];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }
}
