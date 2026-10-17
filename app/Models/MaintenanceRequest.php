<?php

namespace App\Models;

use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'stall_id', 'subject', 'description', 'priority', 'status', 'image_path'];

    protected function casts(): array
    {
        return [
            'status' => MaintenanceStatus::class,
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

    public function latestActivity(): MorphOne
    {
        return $this->morphOne(ActivityLog::class, 'subject')->latestOfMany();
    }
}
