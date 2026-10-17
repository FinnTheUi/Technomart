<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ApplicationStatus: string
{
    use EnumHelpers;

    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function badge(): string
    {
        return match ($this) {
            self::PendingApproval => 'badge-yellow',
            self::Approved => 'badge-green',
            self::Rejected => 'badge-red',
        };
    }
}
