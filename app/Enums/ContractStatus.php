<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ContractStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'badge-green',
            self::Pending => 'badge-yellow',
            self::Expired, self::Terminated => 'badge-red',
        };
    }
}
