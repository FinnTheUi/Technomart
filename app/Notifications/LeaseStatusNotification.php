<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Lease lifecycle events: renewal requested (to manager), renewed / terminated / expired (to tenant).
 */
class LeaseStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const RENEWAL_REQUESTED = 'renewal_requested';

    public const RENEWED = 'renewed';

    public const TERMINATED = 'terminated';

    public const EXPIRED = 'expired';

    public function __construct(public Contract $contract, public string $event) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message())
            ->action('View lease', $this->url());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lease_'.$this->event,
            'contract_id' => $this->contract->id,
            'url' => $this->url(),
            'message' => $this->message(),
        ];
    }

    private function subject(): string
    {
        return match ($this->event) {
            self::RENEWAL_REQUESTED => 'Lease renewal requested - Technomart',
            self::RENEWED => 'Your lease has been renewed - Technomart',
            self::TERMINATED => 'Your lease has been terminated - Technomart',
            self::EXPIRED => 'Your lease has ended - Technomart',
        };
    }

    private function message(): string
    {
        $stall = $this->contract->stall->name ?? 'your stall';

        return match ($this->event) {
            self::RENEWAL_REQUESTED => ($this->contract->tenant->name ?? 'A tenant').' requested to renew the lease for "'.$stall.'" by '.$this->contract->renewal_months_requested.' month(s).',
            self::RENEWED => 'Your lease for "'.$stall.'" was renewed until '.$this->contract->end_date->format('M d, Y').'.',
            self::TERMINATED => 'Your lease for "'.$stall.'" was terminated. Reason: '.$this->contract->termination_reason,
            self::EXPIRED => 'Your lease for "'.$stall.'" ended on '.$this->contract->end_date->format('M d, Y').'.',
        };
    }

    private function url(): string
    {
        return $this->event === self::RENEWAL_REQUESTED
            ? route('manager.contracts.index')
            : route('tenant.contracts.show', $this->contract);
    }
}
