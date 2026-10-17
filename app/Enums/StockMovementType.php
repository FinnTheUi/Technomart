<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum StockMovementType: string
{
    use EnumHelpers;

    case Initial = 'initial';
    case Purchase = 'purchase';         // received from a supplier (purchase order)
    case Sale = 'sale';                 // reserved by a tenant order
    case Cancellation = 'cancellation'; // cancelled order returned to stock
    case Adjustment = 'adjustment';     // manual correction (count, damage, ...)

    public function badge(): string
    {
        return match ($this) {
            self::Initial => 'badge-indigo',
            self::Purchase, self::Cancellation => 'badge-green',
            self::Sale => 'badge-blue',
            self::Adjustment => 'badge-yellow',
        };
    }
}
