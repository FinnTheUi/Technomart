<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes entries to the audit log (activity_logs).
 */
class ActivityLogger
{
    public static function log(string $action, string $description, ?Model $subject = null, ?array $properties = null, ?User $actor = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $actor?->id ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
        ]);
    }
}
