<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Full notifications page (paginated).
     */
    public function index()
    {
        $notifications = Auth::user()
            ->notifications()
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $id)
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * API endpoint for dropdown polling — returns unread count + recent items.
     */
    public function apiUnread()
    {
        $user = Auth::user();

        $unreadCount = $user->unreadNotifications()->count();

        $recent = $user->notifications()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? 'New notification',
                'type' => $n->data['type'] ?? 'info',
                'read' => $n->read_at !== null,
                'time' => $n->created_at->diffForHumans(),
                'url' => $n->data['url'] ?? null,
            ]);

        return response()->json([
            'count' => $unreadCount,
            'notifications' => $recent,
        ]);
    }
}
