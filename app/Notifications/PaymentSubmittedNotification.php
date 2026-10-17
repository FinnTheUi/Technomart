<?php

namespace App\Notifications;

use App\Contracts\Payable;
use App\Services\Billing\PayableResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to staff when a tenant submits a payment reference that needs verification.
 */
class PaymentSubmittedNotification extends Notification implements ShouldQueue
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
            ->subject('Payment awaiting verification - Technomart')
            ->greeting('Hello '.$notifiable->name.',')
            ->line(($this->payable->payer()?->name ?? 'A tenant').' submitted a '.strtoupper((string) $this->payable->payment_method).' payment.')
            ->line($this->payable->paymentDescription())
            ->line('Amount: PHP '.number_format($this->payable->amountDue(), 2).' | Reference: '.$this->payable->reference_number)
            ->action('Review payment', PayableResolver::reviewerUrl($this->payable));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_submitted',
            'payable_type' => $this->payable::payableType(),
            'payable_id' => $this->payable->getKey(),
            'url' => PayableResolver::reviewerUrl($this->payable),
            'message' => ($this->payable->payer()?->name ?? 'A tenant').' submitted payment for '.$this->payable->paymentDescription().' (ref '.$this->payable->reference_number.').',
        ];
    }
}
