{{-- Admin Sidebar Partial — @include('partials.admin-sidebar', ['active' => 'dashboard']) --}}
<aside class="sidebar">
    <a href="/" class="sidebar-brand"><svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="sGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#6366F1"/><stop offset="100%" stop-color="#06B6D4"/></linearGradient></defs><rect width="100" height="100" rx="22" fill="url(#sGrad)"/><path d="M 28 30 L 72 30" stroke="white" stroke-width="8" stroke-linecap="round"/><path d="M 50 30 L 50 74" stroke="white" stroke-width="8" stroke-linecap="round"/><circle cx="50" cy="26" r="4" fill="rgba(255,255,255,0.4)"/></svg><span>Technomart</span></a>
    <nav class="sidebar-nav">
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ ($active ?? '') === 'dashboard' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>Dashboard</a>
        <a href="{{ route('admin.users') }}" class="nav-item {{ ($active ?? '') === 'users' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Users</a>
        <a href="{{ route('admin.products') }}" class="nav-item {{ ($active ?? '') === 'products' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>Products</a>
        <a href="{{ route('admin.orders.index') }}" class="nav-item {{ ($active ?? '') === 'orders' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>Orders</a>
        <a href="{{ route('admin.locations') }}" class="nav-item {{ ($active ?? '') === 'locations' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Locations</a>
        <a href="{{ route('admin.audit-log') }}" class="nav-item {{ ($active ?? '') === 'audit-log' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Audit Log</a>
        <a href="{{ route('admin.reports.index') }}" class="nav-item {{ ($active ?? '') === 'reports' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Reports</a>
        <a href="{{ route('messages.index') }}" class="nav-item {{ ($active ?? '') === 'messages' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>Messages</a>
        <a href="{{ route('profile.edit') }}" class="nav-item {{ ($active ?? '') === 'profile' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21a8 8 0 10-16 0m12-13a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Profile</a>
    </nav>
    @include('partials.notification-bell')
    <div class="sidebar-footer">
        <a href="{{ route('profile.edit') }}" style="text-decoration:none; color:inherit; display:block;">
            <div class="user-info" style="cursor:pointer; transition:0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                <div class="user-meta">
                    <div class="uname">{{ auth()->user()->name }}</div>
                    <div class="urole">Administrator</div>
                </div>
            </div>
        </a>
        <div style="display:flex; flex-direction:column; gap:4px; margin-top:12px;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Log Out</button>
            </form>
        </div>
    </div>
</aside>
