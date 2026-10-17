<?php

namespace App\Notifications;

use App\Contracts\Payable;
use App\Services\Billing\PayableResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the payer once a payment is confirmed, with the official receipt number.
 */
class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payable $payable) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment received - Official Receipt '.$this->payable->receipt_number)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We received your payment for '.$this->payable->paymentDescription().'.')
            ->line('Amount: PHP '.number_format($this->payable->amountDue(), 2))
            ->line('Official Receipt No.: '.$this->payable->receipt_number)
            ->action('View receipt', PayableResolver::receiptUrl($this->payable))
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'payable_type' => $this->payable::payableType(),
            'payable_id' => $this->payable->getKey(),
            'receipt_number' => $this->payable->receipt_number,
            'url' => PayableResolver::receiptUrl($this->payable),
            'message' => 'Payment received for '.$this->payable->paymentDescription().'. Receipt '.$this->payable->receipt_number.'.',
        ];
    }
}
