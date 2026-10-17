<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum MaintenanceStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'badge-yellow',
            self::InProgress => 'badge-purple',
            self::Resolved => 'badge-green',
            self::Closed => 'badge-red',
        };
    }
}
