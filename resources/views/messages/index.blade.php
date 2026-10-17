<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Messages — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .chat-layout { display: flex; flex: 1; height: calc(100vh); }
        
        /* Chat Sidebar (Contacts) */
        .chat-sidebar { width: 320px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; background: #f8fafc; }
        .chat-sidebar-header { padding: 20px; border-bottom: 1px solid #e2e8f0; background: #fff; }
        .chat-sidebar-header h2 { font-size: 20px; font-weight: 800; color: #0f172a; }
        .chat-sidebar-header p { font-size: 12px; color: #64748b; margin-top: 2px; }
        
        .contact-list { flex: 1; overflow-y: auto; padding: 12px 8px; }
        .contact-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; cursor: pointer; transition: 0.2s; margin-bottom: 4px; border: 1px solid transparent; }
        .contact-item:hover { background: #f1f5f9; }
        .contact-item.active { background: #e0e7ff; border-color: #c7d2fe; }
        .contact-avatar { width: 40px; height: 40px; border-radius: 10px; background: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: #fff; flex-shrink: 0; }
        .contact-meta { flex: 1; min-width: 0; }
        .contact-name { font-size: 14px; font-weight: 600; color: #0f172a; }
        .contact-preview { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .unread-badge { background: #ef4444; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 6px; flex-shrink: 0; }
        
        /* Chat Area */
        .chat-main { flex: 1; display: flex; flex-direction: column; background: #fff; }
        .chat-header { padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px; background: #fff; }
        .chat-header h3 { font-size: 16px; font-weight: 700; color: #0f172a; }
        .chat-header .role-badge { font-size: 11px; color: #64748b; background: #f1f5f9; padding: 3px 8px; border-radius: 6px; border: 1px solid #e2e8f0; }
        
        .chat-messages { flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 16px; background: #f8fafc; }
        .message-bubble { max-width: 65%; padding: 12px 16px; border-radius: 16px; font-size: 14px; line-height: 1.5; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .message-bubble.mine { align-self: flex-end; background: #4f46e5; color: #fff; border-bottom-right-radius: 4px; }
        .message-bubble.theirs { align-self: flex-start; background: #fff; color: #0f172a; border-bottom-left-radius: 4px; border: 1px solid #e2e8f0; }
        .message-time { font-size: 10px; margin-top: 6px; opacity: 0.7; }
        
        .chat-input { padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; background: #fff; }
        .chat-input input { flex: 1; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px 16px; font-size: 14px; color: #0f172a; outline: none; font-family: 'Inter', sans-serif; transition: 0.2s; }
        .chat-input input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
        .chat-input button { background: #4f46e5; color: #fff; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 600; font-size: 14px; cursor: pointer; font-family: 'Inter', sans-serif; transition: 0.2s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
        .chat-input button:hover { background: #4338ca; transform: translateY(-1px); }
        
        .chat-empty { flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 16px; color: #64748b; background: #f8fafc; }
        .chat-empty svg { width: 64px; height: 64px; opacity: 0.5; }
        .chat-empty p { font-size: 15px; font-weight: 500; }
        
        /* New Chat Section */
        .new-chat-section { padding: 16px; border-top: 1px solid #e2e8f0; background: #fff; }
        .new-chat-section select { width: 100%; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 13px; color: #0f172a; font-family: 'Inter', sans-serif; outline: none; transition: 0.2s; }
        .new-chat-section select:focus { border-color: #4f46e5; }
        .new-chat-btn { width: 100%; margin-top: 8px; background: #e0e7ff; border: 1px solid #c7d2fe; color: #4338ca; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; transition: 0.2s; }
        .new-chat-btn:hover { background: #c7d2fe; }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @if(auth()->user()->role === 'admin')
        @include('partials.admin-sidebar', ['active' => 'messages'])
    @elseif(auth()->user()->role === 'manager')
        @include('partials.manager-sidebar', ['active' => 'messages'])
    @elseif(auth()->user()->role === 'tenant')
        @include('partials.tenant-sidebar', ['active' => 'messages'])
    @endif

    <div class="main">
        <div class="chat-layout">
            <!-- Conversation List -->
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <h2>Messages</h2>
                    <p>{{ $conversations->count() }} conversation(s)</p>
                </div>
                <div class="contact-list">
                    @forelse($conversations as $contact)
                    <a href="{{ route('messages.conversation', $contact->id) }}" class="contact-item {{ isset($user) && $user->id === $contact->id ? 'active' : '' }}">
                        <div class="contact-avatar">{{ strtoupper(substr($contact->name,0,1)) }}</div>
                        <div class="contact-meta">
                            <div class="contact-name">{{ $contact->name }}</div>
                            <div class="contact-preview">{{ $contact->last_message?->message ?? 'No messages yet' }}</div>
                        </div>
                        @if($contact->unread_count > 0)
                        <span class="unread-badge">{{ $contact->unread_count }}</span>
                        @endif
                    </a>
                    @empty
                    <div style="text-align:center; padding: 40px 16px;">
                        <p style="color:#64748b;font-size:14px;margin-bottom:8px;">No conversations yet.</p>
                        <p style="color:#94a3b8;font-size:12px;">Start a new chat below!</p>
                    </div>
                    @endforelse
                </div>
                <div class="new-chat-section">
                    <form action="" method="GET" id="newChatForm">
                        <select name="user_id" id="newChatSelect" onchange="if(this.value) window.location.href='/messages/'+this.value">
                            <option value="">Start new conversation...</option>
                            @foreach($contactableUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ ucfirst($u->role) }})</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="chat-main">
                @if(isset($user) && isset($messages))
                <div class="chat-header">
                    <div class="contact-avatar" style="width:36px;height:36px;font-size:13px;">{{ strtoupper(substr($user->name,0,1)) }}</div>
                    <div>
                        <h3>{{ $user->name }}</h3>
                    </div>
                    <span class="role-badge">{{ ucfirst($user->role) }}</span>
                </div>
                <div class="chat-messages" id="chatMessages">
                    @forelse($messages as $msg)
                    <div class="message-bubble {{ $msg->sender_id === auth()->id() ? 'mine' : 'theirs' }}">
                        {{ $msg->message }}
                        <div class="message-time">{{ $msg->created_at->format('g:i A') }}</div>
                    </div>
                    @empty
                    <div style="text-align:center;color:#64748b;padding:60px 0;font-size:14px;background:#f8fafc;border-radius:12px;margin:20px;border:1px dashed #cbd5e1;">
                        No messages yet. Say hello! 
                    </div>
                    @endforelse
                </div>
                <form class="chat-input" method="POST" action="{{ route('messages.send') }}">
                    @csrf
                    <input type="hidden" name="receiver_id" value="{{ $user->id }}">
                    <input type="text" name="message" placeholder="Type your message..." autocomplete="off" required autofocus>
                    <button type="submit">Send</button>
                </form>
                @else
                <div class="chat-empty">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <p>Select a conversation or start a new one</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

        // Poll for new messages every 5 seconds
        @if(isset($user))
        setInterval(async () => {
            try {
                const res = await fetch('/api/messages/{{ $user->id }}');
                const data = await res.json();
                if (data.length !== document.querySelectorAll('.message-bubble').length) {
                    location.reload();
                }
            } catch(e) {}
        }, 5000);
        @endif
    </script>
</body>
</html>
