{{-- Notification Bell Partial — @include('partials.notification-bell') --}}
@php
    $unreadNotifCount = auth()->user()->unreadNotifications()->count();
@endphp
<div class="notif-section" id="notifSection">
    <button type="button" class="notif-trigger {{ ($active ?? '') === 'notifications' ? 'active' : '' }}" id="notifTrigger" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        Notifications
        <span class="notif-badge {{ $unreadNotifCount === 0 ? 'hidden' : '' }}" id="notifBadge">{{ $unreadNotifCount }}</span>
    </button>

    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-dropdown-header">
            <h3>Notifications</h3>
            <button type="button" class="notif-mark-all" id="notifMarkAllBtn" onclick="markAllNotificationsRead()">Mark all read</button>
        </div>
        <div class="notif-list" id="notifList">
            <div class="notif-empty" id="notifLoading">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <p>Loading notifications…</p>
            </div>
        </div>
        <div class="notif-dropdown-footer">
            <a href="{{ route('notifications.index') }}">View all notifications →</a>
        </div>
    </div>
</div>

<script>
(function() {
    const trigger = document.getElementById('notifTrigger');
    const dropdown = document.getElementById('notifDropdown');
    const badge = document.getElementById('notifBadge');
    const list = document.getElementById('notifList');
    const csrfToken = '{{ csrf_token() }}';

    // Toggle dropdown
    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        const isOpen = dropdown.classList.contains('open');
        dropdown.classList.toggle('open');
        trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        if (!isOpen) fetchNotifications();
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!document.getElementById('notifSection').contains(e.target)) {
            dropdown.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });

    function getIconForType(type) {
        const icons = {
            'overdue_payment':     { cls: 'icon-payment',     svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/></svg>' },
            'application_approved':{ cls: 'icon-application', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
            'lease_expiring':      { cls: 'icon-lease',       svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
            'stall_available':     { cls: 'icon-stall',       svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>' },
        };
        return icons[type] || { cls: 'icon-default', svg: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' };
    }

    function renderNotifications(data) {
        // Update badge
        if (data.count > 0) {
            badge.textContent = data.count > 99 ? '99+' : data.count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        if (data.notifications.length === 0) {
            list.innerHTML = '<div class="notif-empty"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg><p>No notifications yet</p></div>';
            return;
        }

        list.innerHTML = data.notifications.map(function(n) {
            const icon = getIconForType(n.type);
            return '<div class="notif-item ' + (n.read ? '' : 'unread') + '" data-id="' + n.id + '" onclick="markNotificationRead(\'' + n.id + '\', this)">' +
                '<div class="notif-item-icon ' + icon.cls + '">' + icon.svg + '</div>' +
                '<div class="notif-item-content">' +
                    '<div class="notif-msg">' + escapeHtml(n.message) + '</div>' +
                    '<div class="notif-time">' + n.time + '</div>' +
                '</div>' +
                '<div class="notif-dot"></div>' +
            '</div>';
        }).join('');
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function fetchNotifications() {
        fetch('/api/notifications', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => renderNotifications(data))
        .catch(() => {
            list.innerHTML = '<div class="notif-empty"><p>Could not load notifications</p></div>';
        });
    }

    // Poll every 30s for badge count
    setInterval(function() {
        fetch('/api/notifications', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.count > 0) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        })
        .catch(() => {});
    }, 30000);

    // Initial fetch for badge
    fetchNotifications();

    // Expose global helpers
    window.markNotificationRead = function(id, el) {
        if (el) {
            el.classList.remove('unread');
            const dot = el.querySelector('.notif-dot');
            if (dot) dot.style.background = '#e2e8f0';
        }
        fetch('/notifications/' + id + '/read', {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(() => {
            let current = parseInt(badge.textContent) || 0;
            if (current > 1) {
                badge.textContent = current - 1;
            } else {
                badge.classList.add('hidden');
            }
        });
    };

    window.markAllNotificationsRead = function() {
        fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(() => {
            badge.classList.add('hidden');
            document.querySelectorAll('.notif-item.unread').forEach(el => {
                el.classList.remove('unread');
                const dot = el.querySelector('.notif-dot');
                if (dot) dot.style.background = '#e2e8f0';
            });
        });
    };
})();
</script>
