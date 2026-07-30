<?php

namespace App\Notifications;

use App\Models\Stall;
use App\Models\Waitlist;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StallAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Stall $stall,
        public Waitlist $waitlistEntry
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🏪 Stall Available — Tecnomart')
            ->greeting('Good news, ' . $notifiable->name . '!')
            ->line('The stall "' . $this->stall->name . '" that you were waitlisted for is now available!')
            ->line('Location: ' . ($this->stall->location ?? 'N/A'))
            ->line('Price: ₱' . number_format($this->stall->price, 2) . '/month')
            ->action('Apply Now', url('/stalls/' . $this->stall->id . '/apply'))
            ->line('Please apply soon — stalls are available on a first-come, first-served basis.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stall_available',
            'stall_id' => $this->stall->id,
            'stall_name' => $this->stall->name,
            'message' => 'Stall "' . $this->stall->name . '" is now available!',
        ];
    }
}
