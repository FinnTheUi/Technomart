<?php

namespace App\Services\Rental;

use App\Models\Stall;
use App\Models\User;
use App\Models\Waitlist;
use App\Notifications\StallAvailableNotification;
use Illuminate\Support\Facades\Notification;

class WaitlistService
{
    /**
     * Notify everyone waiting for this stall. Returns the number of people notified.
     */
    public function notifyStallAvailable(Stall $stall): int
    {
        $entries = Waitlist::where('stall_id', $stall->id)->where('status', 'waiting')->get();

        foreach ($entries as $entry) {
            $notification = new StallAvailableNotification($stall, $entry);
            $user = User::where('email', $entry->email)->first();

            // Most waitlisters are guests without an account: email them directly.
            $user
                ? $user->notify($notification)
                : Notification::route('mail', [$entry->email => $entry->name])->notify($notification);

            $entry->update(['status' => 'notified', 'notified_at' => now()]);
        }

        return $entries->count();
    }
}
