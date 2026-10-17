<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum OrderStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';               // placed, awaiting payment
    case ForVerification = 'for_verification'; // payment reference submitted
    case Paid = 'paid';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'badge-yellow',
            self::ForVerification => 'badge-blue',
            self::Paid => 'badge-indigo',
            self::Delivered => 'badge-green',
            self::Cancelled => 'badge-red',
        };
    }

    /** Orders that count as a sale (revenue, top products). */
    public static function sold(): array
    {
        return [self::Paid, self::Delivered];
    }

    public function isPayable(): bool
    {
        return $this === self::Pending;
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::ForVerification], true);
    }
}
