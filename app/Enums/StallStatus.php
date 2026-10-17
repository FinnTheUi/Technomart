<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum StallStatus: string
{
    use EnumHelpers;

    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';

    public function badge(): string
    {
        return match ($this) {
            self::Available => 'badge-green',
            self::Occupied => 'badge-blue',
            self::Maintenance => 'badge-yellow',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => '#059669',
            self::Occupied => '#0284c7',
            self::Maintenance => '#d97706',
        };
    }
}
