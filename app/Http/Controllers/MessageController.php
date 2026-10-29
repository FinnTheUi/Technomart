<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Notifications\SystemEventNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        return view('messages.index', $this->contacts(auth()->user()));
    }

    public function conversation(User $user)
    {
        abort_unless($this->canContact(auth()->user(), $user), 403);
        $messages = $this->thread(auth()->user(), $user)->latest('id')
            ->paginate(50, ['*'], 'messages_page')->withQueryString();
        $messages->setCollection($messages->getCollection()->reverse()->values());
        $this->markReceivedRead($user);

        return view('messages.index', [...$this->contacts(auth()->user()), 'user' => $user, 'messages' => $messages]);
    }

    public function send(Request $request)
    {
        $request->validate(['receiver_id' => 'required|exists:users,id', 'message' => 'required|string|max:2000']);
        $receiver = User::findOrFail($request->integer('receiver_id'));
        abort_unless($this->canContact($request->user(), $receiver), 403);
        Message::create(['sender_id' => $request->user()->id, 'receiver_id' => $receiver->id, 'message' => $request->message]);
        $receiver->notify(new SystemEventNotification('new_message', 'New message from '.$request->user()->name.'.', route('messages.conversation', $request->user())));

        return redirect()->route('messages.conversation', $receiver);
    }

    public function apiMessages(User $user, Request $request)
    {
        abort_unless($this->canContact($request->user(), $user), 403);
        $request->validate(['after_id' => 'nullable|integer|min:0']);
        $query = $this->thread($request->user(), $user)->with('sender');
        $messages = $request->filled('after_id')
            ? $query->where('id', '>', $request->integer('after_id'))->oldest('id')->limit(100)->get()
            : $query->latest('id')->limit(50)->get()->reverse()->values();
        $this->markReceivedRead($user);

        return response()->json($messages->map(fn ($msg) => [
            'id' => $msg->id, 'message' => $msg->message, 'is_mine' => $msg->sender_id === $request->user()->id,
            'sender_name' => $msg->sender?->name ?? 'Former user', 'created_at' => $msg->created_at->diffForHumans(),
            'time' => $msg->created_at->format('g:i A'),
        ])->values());
    }

    private function contacts(User $current): array
    {
        $allowed = User::query()->where('is_active', true)->where('role', $current->isManager() ? 'tenant' : 'manager');
        $lastMessage = Message::select('created_at')->where(function ($query) use ($current) {
            $query->where(fn ($q) => $q->whereColumn('sender_id', 'users.id')->where('receiver_id', $current->id))
                ->orWhere(fn ($q) => $q->whereColumn('receiver_id', 'users.id')->where('sender_id', $current->id));
        })->latest('id')->limit(1);
        $conversations = (clone $allowed)->select('users.*')->selectSub($lastMessage, 'last_message_at')
            ->whereExists(function ($query) use ($current) {
                $query->selectRaw('1')->from('messages')->where(function ($q) use ($current) {
                    $q->where(fn ($q) => $q->whereColumn('sender_id', 'users.id')->where('receiver_id', $current->id))
                        ->orWhere(fn ($q) => $q->whereColumn('receiver_id', 'users.id')->where('sender_id', $current->id));
                });
            })->orderByDesc('last_message_at')->orderBy('id')->paginate(15, ['*'], 'conversations_page')->withQueryString();
        $conversations->through(function ($contact) use ($current) {
            $contact->last_message = $this->thread($current, $contact)->latest('id')->first();
            $contact->unread_count = Message::where('sender_id', $contact->id)->where('receiver_id', $current->id)->whereNull('read_at')->count();

            return $contact;
        });

        return [
            'conversations' => $conversations,
            'contactableUsers' => $allowed->when(request()->filled('contact_search'), fn ($q) => $q->where('name', 'like', '%'.substr((string) request('contact_search'), 0, 100).'%'))
                ->orderBy('name')->orderBy('id')->paginate(15, ['*'], 'contacts_page')->withQueryString(),
        ];
    }

    private function thread(User $current, User $other): Builder
    {
        return Message::where(function ($query) use ($current, $other) {
            $query->where(fn ($q) => $q->where('sender_id', $current->id)->where('receiver_id', $other->id))
                ->orWhere(fn ($q) => $q->where('sender_id', $other->id)->where('receiver_id', $current->id));
        });
    }

    private function markReceivedRead(User $sender): void
    {
        Message::where('sender_id', $sender->id)->where('receiver_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
        auth()->user()->unreadNotifications()->where('data->type', 'new_message')
            ->where('data->url', route('messages.conversation', $sender))->update(['read_at' => now()]);
    }

    private function canContact(User $sender, User $receiver): bool
    {
        return ! $sender->is($receiver) && $receiver->is_active && match ($sender->role) {
            User::ROLE_MANAGER => $receiver->isTenant(),
            User::ROLE_TENANT => $receiver->isManager(),
            default => false,
        };
    }
}
