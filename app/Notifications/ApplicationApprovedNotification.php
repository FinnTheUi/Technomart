<?php

namespace App\Notifications;

use App\Models\RentalApplication;
use App\Models\Stall;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public RentalApplication $application,
        public Stall $stall
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Rental Application Approved — Tecnomart')
            ->greeting('Congratulations, ' . $notifiable->name . '!')
            ->line('Your rental application for stall "' . $this->stall->name . '" has been approved.')
            ->line('You can now log in to your tenant dashboard to view your contract, bills, and more.')
            ->action('Go to Dashboard', url('/tenant/dashboard'))
            ->line('Welcome to Tecnomart!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'application_approved',
            'application_id' => $this->application->id,
            'stall_name' => $this->stall->name,
            'message' => 'Your rental application for "' . $this->stall->name . '" has been approved!',
        ];
    }
}
