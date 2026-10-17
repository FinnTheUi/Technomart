<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Writes entries to the audit log (activity_logs).
 */
class ActivityLogger
{
    public static function log(string $action, string $description, ?Model $subject = null, ?array $properties = null, ?User $actor = null): ActivityLog
    {
        $request = app()->bound('request') ? request() : null;
        $safeProperties = Arr::except($properties ?? [], [
            'password', 'password_confirmation', 'token', 'secret', 'proof', 'card',
        ]);

        return ActivityLog::create([
            'user_id' => $actor?->id ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $safeProperties ?: null,
            'request_id' => $request?->attributes->get('request_id'),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
        ]);
    }
}
