<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverduePaymentNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Overdue Payment Notice — Tecnomart')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your payment of ₱' . number_format($this->payment->amount, 2) . ' for stall "' . ($this->payment->stall->name ?? 'N/A') . '" was due on ' . $this->payment->due_date->format('M d, Y') . ' and is now overdue.')
            ->line('Please settle your balance as soon as possible to avoid any service disruptions.')
            ->action('View My Bills', url('/tenant/bills'))
            ->line('If you have already paid, please disregard this notice.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'overdue_payment',
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'due_date' => $this->payment->due_date->toDateString(),
            'message' => 'Your payment of ₱' . number_format($this->payment->amount, 2) . ' is overdue.',
        ];
    }
}
