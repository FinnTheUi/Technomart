<?php

namespace App\Notifications;

use App\Contracts\Payable;
use App\Services\Billing\PayableResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the payer when a submitted payment reference could not be verified.
 */
class PaymentRejectedNotification extends Notification implements ShouldQueue
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
            ->subject('Payment could not be verified - Technomart')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We could not verify your payment for '.$this->payable->paymentDescription().'.')
            ->line('Reason: '.$this->payable->rejection_reason)
            ->action('Submit again', PayableResolver::payerUrl($this->payable))
            ->line('If you believe this is a mistake, please message your manager.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_rejected',
            'payable_type' => $this->payable::payableType(),
            'payable_id' => $this->payable->getKey(),
            'url' => PayableResolver::payerUrl($this->payable),
            'message' => 'Payment for '.$this->payable->paymentDescription().' was not verified: '.$this->payable->rejection_reason,
        ];
    }
}
