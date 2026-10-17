<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Status of any payable document: rent bills, electric bills, supply orders and rental checkouts.
 */
enum PaymentStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case ForVerification = 'for_verification';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'badge-yellow',
            self::ForVerification => 'badge-blue',
            self::Paid => 'badge-green',
            self::Overdue => 'badge-red',
        };
    }

    /** The tenant can still pay (or re-submit a payment). */
    public function isPayable(): bool
    {
        return in_array($this, [self::Pending, self::Overdue], true);
    }
}
