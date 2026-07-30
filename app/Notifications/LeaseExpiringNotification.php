<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaseExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public Contract $contract)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysLeft = now()->diffInDays($this->contract->end_date, false);

        return (new MailMessage)
            ->subject('📋 Lease Expiring Soon — Tecnomart')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your lease for stall "' . ($this->contract->stall->name ?? 'N/A') . '" will expire on ' . $this->contract->end_date->format('M d, Y') . '.')
            ->line('That\'s approximately ' . max(0, (int)$daysLeft) . ' days from now.')
            ->line('If you wish to renew your lease, please contact your stall manager.')
            ->action('View My Dashboard', url('/tenant/dashboard'))
            ->line('Thank you for being a valued tenant!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lease_expiring',
            'contract_id' => $this->contract->id,
            'end_date' => $this->contract->end_date->toDateString(),
            'stall_name' => $this->contract->stall->name ?? 'N/A',
            'message' => 'Your lease for "' . ($this->contract->stall->name ?? 'N/A') . '" expires on ' . $this->contract->end_date->format('M d, Y') . '.',
        ];
    }
}
