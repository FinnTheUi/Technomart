<?php

namespace App\Services\Billing;

use App\Contracts\Payable;
use App\Models\ElectricBill;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps the short payable type used in URLs and gateway metadata to its model.
 */
class PayableResolver
{
    /** @var array<string, class-string<Model&Payable>> */
    public const TYPES = [
        'payment' => Payment::class,
        'electric_bill' => ElectricBill::class,
        'order' => Order::class,
    ];

    /**
     * @return (Model&Payable)|null
     */
    public function find(string $type, int|string $id): ?Payable
    {
        $class = self::TYPES[$type] ?? null;

        return $class ? $class::find($id) : null;
    }

    /**
     * @return Model&Payable
     */
    public function findOrFail(string $type, int|string $id): Payable
    {
        $payable = $this->find($type, $id);

        abort_if($payable === null, 404);

        return $payable;
    }

    /** Page where the payer sees this document. */
    public static function payerUrl(Payable $payable): string
    {
        return match ($payable::payableType()) {
            'payment' => route('tenant.bills'),
            'electric_bill' => route('tenant.electric-bills'),
            'order' => route('tenant.orders'),
        };
    }

    /** Page where staff verify payments for this document. */
    public static function reviewerUrl(Payable $payable): string
    {
        return match ($payable::payableType()) {
            'payment' => route('manager.payments'),
            'electric_bill' => route('manager.electric-bills'),
            'order' => route('manager.orders.index'),
        };
    }

    public static function receiptUrl(Payable $payable): string
    {
        return route('receipts.show', ['type' => $payable::payableType(), 'id' => $payable->getKey()]);
    }
}

