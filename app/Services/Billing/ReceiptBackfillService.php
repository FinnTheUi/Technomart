<?php

namespace App\Services\Billing;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\ElectricBill;
use App\Models\Order;
use App\Models\Payment;

class ReceiptBackfillService
{
    public function __construct(private readonly DocumentNumberGenerator $numbers) {}

    /** Issue receipt numbers for historical records that were paid before receipt tracking existed. */
    public function run(): int
    {
        $issued = 0;

        $queries = [
            Payment::where('status', PaymentStatus::Paid->value)->whereNull('receipt_number'),
            ElectricBill::where('status', PaymentStatus::Paid->value)->whereNull('receipt_number'),
            Order::whereIn('status', [OrderStatus::Paid->value, OrderStatus::Delivered->value])->whereNull('receipt_number'),
        ];

        foreach ($queries as $query) {
            $query->orderBy('id')->each(function ($payable) use (&$issued): void {
                $payable->forceFill(['receipt_number' => $this->numbers->nextReceiptNumber()])->save();
                $issued++;
            });
        }

        return $issued;
    }
}
