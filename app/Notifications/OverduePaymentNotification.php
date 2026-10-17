<?php

namespace App\Notifications;

use App\Contracts\Payable;
use App\Services\Billing\PayableResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the tenant when a rent or electric bill becomes overdue.
 */
class OverduePaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payable $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Overdue Payment Notice - Technomart')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->payment->paymentDescription().' was due on '.$this->payment->due_date->format('M d, Y').' and is now overdue.');

        if ($this->payment->lateFee() > 0) {
            $mail->line('A late fee of PHP '.number_format($this->payment->lateFee(), 2).' has been added.');
        }

        return $mail
            ->line('Amount due: PHP '.number_format($this->payment->amountDue(), 2))
            ->action('View My Bills', PayableResolver::payerUrl($this->payment))
            ->line('If you have already paid, please disregard this notice.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'overdue_payment',
            'payable_type' => $this->payment::payableType(),
            'payment_id' => $this->payment->getKey(),
            'amount' => $this->payment->amountDue(),
            'late_fee' => $this->payment->lateFee(),
            'due_date' => $this->payment->due_date->toDateString(),
            'url' => PayableResolver::payerUrl($this->payment),
            'message' => $this->payment->paymentDescription().' is overdue. Amount due: PHP '.number_format($this->payment->amountDue(), 2).'.',
        ];
    }
}
