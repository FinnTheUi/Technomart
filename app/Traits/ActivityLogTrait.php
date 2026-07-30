<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait ActivityLogTrait
{
    /**
     * Log an activity to the activity_logs table.
     *
     * @param string $action  Short action verb (e.g. 'created', 'approved')
     * @param string $description  Human-readable description
     * @param \Illuminate\Database\Eloquent\Model|null $subject  The model being acted upon
     * @param array|null $properties  Optional extra context
     */
    protected function logActivity(string $action, string $description, $subject = null, ?array $properties = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'properties' => $properties,
        ]);
    }
}
