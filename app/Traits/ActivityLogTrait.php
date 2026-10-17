<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

trait ActivityLogTrait
{
    /**
     * Log an activity to the audit log as the current user.
     *
     * @param  string  $action  Short action verb (e.g. 'created', 'approved')
     * @param  string  $description  Human-readable description
     * @param  Model|null  $subject  The model being acted upon
     * @param  array|null  $properties  Optional extra context
     */
    protected function logActivity(string $action, string $description, $subject = null, ?array $properties = null): ActivityLog
    {
        return ActivityLogger::log($action, $description, $subject, $properties);
    }
}
