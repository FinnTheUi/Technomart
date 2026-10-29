<?php

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use App\Models\Message;
use App\Models\User;

class NavigationBadgeService
{
    /**
     * Return unread counts for notification-aware navigation items.
     *
     * @return array<string, int>
     */
    public function counts(User $user): array
    {
        $notifications = $user->unreadNotifications()
            ->get(['id', 'data'])
            ->pluck('data');

        $forPayable = static fn (string $type): int => $notifications
            ->filter(fn (array $data) => ($data['payable_type'] ?? null) === $type)
            ->count();

        $leaseCount = $notifications
            ->filter(fn (array $data) => str_starts_with((string) ($data['type'] ?? ''), 'lease_'))
            ->count();

        $counts = [
            'messages' => Message::where('receiver_id', $user->id)->whereNull('read_at')->count(),
            'bills' => $forPayable('payment'),
            'electric-bills' => $forPayable('electric_bill'),
            'orders' => $forPayable('order'),
            'contracts' => $leaseCount,
        ];

        if ($user->isManager()) {
            $counts['inbox'] = MaintenanceRequest::whereHas(
                'stall',
                fn ($query) => $query->where('manager_id', $user->id)
            )->where('status', MaintenanceStatus::Pending)->count();
        }

        return $counts;
    }

    public function markPayableRead(User $user, string $payableType): void
    {
        $user->unreadNotifications()
            ->get()
            ->filter(fn ($notification) => ($notification->data['payable_type'] ?? null) === $payableType)
            ->each->markAsRead();
    }

    public function markLeasesRead(User $user): void
    {
        $user->unreadNotifications()
            ->get()
            ->filter(fn ($notification) => str_starts_with((string) ($notification->data['type'] ?? ''), 'lease_'))
            ->each->markAsRead();
    }
}
