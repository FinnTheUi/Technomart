<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Show inbox with conversation list.
     */
    public function index()
    {
        $userId = auth()->id();

        // Get unique conversation partners
        $conversationPartnerIds = Message::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->get()
            ->map(function ($msg) use ($userId) {
                return $msg->sender_id == $userId ? $msg->receiver_id : $msg->sender_id;
            })
            ->unique()
            ->values();

        $conversations = User::whereIn('id', $conversationPartnerIds)->get()->map(function ($user) use ($userId) {
            $lastMessage = Message::where(function ($outer) use ($userId, $user) {
                $outer->where(function ($q) use ($userId, $user) {
                    $q->where('sender_id', $userId)->where('receiver_id', $user->id);
                })->orWhere(function ($q) use ($userId, $user) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $userId);
                });
            })->latest()->first();

            $unreadCount = Message::where('sender_id', $user->id)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count();

            $user->last_message = $lastMessage;
            $user->unread_count = $unreadCount;

            return $user;
        })->sortByDesc(function ($u) {
            return $u->last_message?->created_at;
        });

        // Get all users that can be messaged (based on role relationships)
        $currentUser = auth()->user();
        if ($currentUser->role === 'tenant') {
            // Tenants can message managers
            $contactableUsers = User::where('role', 'manager')->where('is_active', true)->get();
        } elseif ($currentUser->role === 'manager') {
            // Administrators have dashboard-only access and cannot receive messages.
            $contactableUsers = User::where('role', 'tenant')->where('is_active', true)->get();
        } else {
            // Admin can message everyone
            $contactableUsers = User::where('id', '!=', $userId)->get();
        }

        return view('messages.index', compact('conversations', 'contactableUsers'));
    }

    /**
     * Show conversation with a specific user.
     */
    public function conversation(User $user)
    {
        abort_unless($this->canContact(auth()->user(), $user), 403);

        $userId = auth()->id();

        $messages = Message::where(function ($outer) use ($userId, $user) {
            $outer->where(function ($q) use ($userId, $user) {
                $q->where('sender_id', $userId)->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($userId, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $userId);
            });
        })->orderBy('created_at', 'asc')->get();

        // Mark as read
        Message::where('sender_id', $user->id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Get all users that can be messaged
        $currentUser = auth()->user();
        if ($currentUser->role === 'tenant') {
            $contactableUsers = User::where('role', 'manager')->where('is_active', true)->get();
        } elseif ($currentUser->role === 'manager') {
            $contactableUsers = User::where('role', 'tenant')->where('is_active', true)->get();
        } else {
            $contactableUsers = User::where('id', '!=', $userId)->get();
        }

        // Get conversation list
        $conversationPartnerIds = Message::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->get()
            ->map(function ($msg) use ($userId) {
                return $msg->sender_id == $userId ? $msg->receiver_id : $msg->sender_id;
            })
            ->unique()
            ->values();

        $conversations = User::whereIn('id', $conversationPartnerIds)->get()->map(function ($u) use ($userId) {
            $lastMessage = Message::where(function ($outer) use ($userId, $u) {
                $outer->where(function ($q) use ($userId, $u) {
                    $q->where('sender_id', $userId)->where('receiver_id', $u->id);
                })->orWhere(function ($q) use ($userId, $u) {
                    $q->where('sender_id', $u->id)->where('receiver_id', $userId);
                });
            })->latest()->first();

            $unreadCount = Message::where('sender_id', $u->id)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count();

            $u->last_message = $lastMessage;
            $u->unread_count = $unreadCount;

            return $u;
        })->sortByDesc(function ($u) {
            return $u->last_message?->created_at;
        });

        return view('messages.index', compact('conversations', 'contactableUsers', 'messages', 'user'));
    }

    /**
     * Send a message.
     */
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:2000',
        ]);

        $receiver = User::findOrFail($request->integer('receiver_id'));
        abort_unless($this->canContact($request->user(), $receiver), 403);

        Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $receiver->id,
            'message' => $request->message,
        ]);

        return redirect()->route('messages.conversation', $request->receiver_id);
    }

    /**
     * API endpoint for polling new messages (used by Alpine.js).
     */
    public function apiMessages(User $user)
    {
        abort_unless($this->canContact(auth()->user(), $user), 403);

        $userId = auth()->id();

        $messages = Message::where(function ($outer) use ($userId, $user) {
            $outer->where(function ($q) use ($userId, $user) {
                $q->where('sender_id', $userId)->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($userId, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $userId);
            });
        })->orderBy('created_at', 'asc')->get()->map(function ($msg) use ($userId) {
            return [
                'id' => $msg->id,
                'message' => $msg->message,
                'is_mine' => $msg->sender_id == $userId,
                'sender_name' => $msg->sender->name,
                'created_at' => $msg->created_at->diffForHumans(),
                'time' => $msg->created_at->format('g:i A'),
            ];
        });

        // Mark received messages as read
        Message::where('sender_id', $user->id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    private function canContact(User $sender, User $receiver): bool
    {
        if ($sender->is($receiver) || ! $receiver->is_active) {
            return false;
        }

        return match ($sender->role) {
            User::ROLE_MANAGER => $receiver->isTenant(),
            User::ROLE_TENANT => $receiver->isManager(),
            default => false,
        };
    }
}
