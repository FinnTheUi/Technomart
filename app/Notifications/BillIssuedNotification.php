<?php

namespace App\Notifications;

use App\Contracts\Payable;
use App\Services\Billing\PayableResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BillIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payable $bill) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bill_issued',
            'payable_type' => $this->bill::payableType(),
            'payable_id' => $this->bill->getKey(),
            'amount' => $this->bill->amountDue(),
            'due_date' => $this->bill->due_date->toDateString(),
            'url' => PayableResolver::payerUrl($this->bill),
            'message' => 'A new '.$this->bill->paymentDescription().' bill was issued. Amount due: PHP '.number_format($this->bill->amountDue(), 2).'.',
        ];
    }
}
