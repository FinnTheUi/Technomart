<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum PurchaseOrderStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case Ordered = 'ordered';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'badge-yellow',
            self::Ordered => 'badge-blue',
            self::Received => 'badge-green',
            self::Cancelled => 'badge-red',
        };
    }
}
