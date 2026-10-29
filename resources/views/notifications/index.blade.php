<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notifications — Technomart</title>
    <meta name="description" content="View all your notifications and alerts from Technomart.">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    @vite('resources/js/app.js')
</head>
<body>
    @php $role = auth()->user()->role; @endphp
    @if($role === 'admin')
        @include('partials.admin-sidebar', ['active' => 'notifications'])
    @elseif($role === 'manager')
        @include('partials.manager-sidebar', ['active' => 'notifications'])
    @else
        @include('partials.tenant-sidebar', ['active' => 'notifications'])
    @endif

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Notifications</h1>
                <p>Stay up to date with your latest alerts and updates.</p>
            </div>
            @if($notifications->where('read_at', null)->count() > 0)
                <form method="POST" action="{{ route('notifications.markAllRead') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="font-size:13px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="panel" style="padding:0; overflow:hidden;">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                    $type = $data['type'] ?? 'info';
                @endphp
                <div class="notif-page-item {{ $isUnread ? 'unread' : '' }}" id="notif-{{ $notification->id }}">
                    {{-- Icon --}}
                    @switch($type)
                        @case('overdue_payment')
                            <div class="notif-item-icon icon-payment">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/></svg>
                            </div>
                            @break
                        @case('application_approved')
                            <div class="notif-item-icon icon-application">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            @break
                        @case('lease_expiring')
                            <div class="notif-item-icon icon-lease">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            @break
                        @case('stall_available')
                            <div class="notif-item-icon icon-stall">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                            </div>
                            @break
                        @default
                            <div class="notif-item-icon icon-default">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                    @endswitch

                    {{-- Content --}}
                    <div class="notif-item-content">
                        <div class="notif-msg">{{ $data['message'] ?? 'New notification' }}</div>
                        <div class="notif-time">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>

                    {{-- Actions --}}
                    <div class="notif-page-actions">
                        @if(!empty($data['url']) && str_starts_with($data['url'], url('/').'/'))
                            <a href="{{ $data['url'] }}" class="btn-sm btn-view">View</a>
                        @endif
                        @if($isUnread)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" style="margin:0;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-sm btn-view">Mark read</button>
                            </form>
                        @else
                            <span class="badge badge-green" style="font-size:11px;">Read</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty" style="padding:60px 24px;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:48px;height:48px;margin:0 auto 16px;display:block;opacity:.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <h3 style="font-size:16px;font-weight:700;color:#0f172a;margin-bottom:4px;">No notifications yet</h3>
                    <p>When you receive notifications, they'll appear here.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div style="display:flex;justify-content:center;margin-top:24px;gap:8px;">
                @if($notifications->onFirstPage())
                    <span class="btn btn-outline" style="opacity:.5;pointer-events:none;font-size:13px;">← Previous</span>
                @else
                    <a href="{{ $notifications->previousPageUrl() }}" class="btn btn-outline" style="font-size:13px;">← Previous</a>
                @endif
                <span style="display:flex;align-items:center;font-size:13px;color:#64748b;padding:0 12px;">Page {{ $notifications->currentPage() }} of {{ $notifications->lastPage() }}</span>
                @if($notifications->hasMorePages())
                    <a href="{{ $notifications->nextPageUrl() }}" class="btn btn-outline" style="font-size:13px;">Next →</a>
                @else
                    <span class="btn btn-outline" style="opacity:.5;pointer-events:none;font-size:13px;">Next →</span>
                @endif
            </div>
        @endif
    </div>
</body>
</html>
