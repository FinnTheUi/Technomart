<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Immediate in-app alerts do not depend on SMTP or a running queue worker. */
class SystemEventNotification extends Notification
{
    public function __construct(public string $type, public string $message, public string $url, public array $context = []) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [...$this->context, 'type' => $this->type, 'message' => $this->message, 'url' => $this->url];
    }
}
