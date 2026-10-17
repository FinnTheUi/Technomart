<?php

namespace App\Notifications;

use App\Models\Stall;
use App\Models\User;
use App\Models\Waitlist;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a waitlisted person that a stall is available. Waitlist entries usually belong
 * to guests (no account), so this is routed by email; users also get an in-app notice.
 */
class StallAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Stall $stall,
        public Waitlist $waitlistEntry
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof User ? ['mail', 'database'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Stall Available - Technomart')
            ->greeting('Good news, '.$this->waitlistEntry->name.'!')
            ->line('The stall "'.$this->stall->name.'" that you were waitlisted for is now available!')
            ->line('Location: '.($this->stall->location ?? 'N/A'))
            ->line('Price: PHP '.number_format($this->stall->price, 2).'/month')
            ->action('Apply Now', route('stalls.apply', $this->stall))
            ->line('Please apply soon - stalls are available on a first-come, first-served basis.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stall_available',
            'stall_id' => $this->stall->id,
            'stall_name' => $this->stall->name,
            'url' => route('stalls.apply', $this->stall),
            'message' => 'Stall "'.$this->stall->name.'" is now available!',
        ];
    }
}
