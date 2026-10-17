<?php

namespace App\Notifications;

use App\Models\RentalApplication;
use App\Models\Stall;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RentalApplication $application,
        public Stall $stall
    ) {}

    /** In-app only: the applicant is emailed by the approver (TenantCredentialsMail). */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Rental Application Approved — Technomart')
            ->greeting('Congratulations, '.$notifiable->name.'!')
            ->line('Your rental application for stall "'.$this->stall->name.'" has been approved.')
            ->line('You can now log in to your tenant dashboard to view your contract, bills, and more.')
            ->action('Go to Dashboard', url('/tenant/dashboard'))
            ->line('Welcome to Technomart!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'application_approved',
            'application_id' => $this->application->id,
            'stall_name' => $this->stall->name,
            'message' => 'Your rental application for "'.$this->stall->name.'" has been approved!',
        ];
    }
}
