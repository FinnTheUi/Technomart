{{-- Tenant Sidebar Partial — @include('partials.tenant-sidebar', ['active' => 'dashboard']) --}}
<aside class="sidebar">
    <a href="/" class="sidebar-brand"><svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="sGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#6366F1"/><stop offset="100%" stop-color="#06B6D4"/></linearGradient></defs><rect width="100" height="100" rx="22" fill="url(#sGrad)"/><path d="M 28 30 L 72 30" stroke="white" stroke-width="8" stroke-linecap="round"/><path d="M 50 30 L 50 74" stroke="white" stroke-width="8" stroke-linecap="round"/><circle cx="50" cy="26" r="4" fill="rgba(255,255,255,0.4)"/></svg><span>Technomart</span></a>
    <nav class="sidebar-nav">
        <a href="{{ route('tenant.dashboard') }}" class="nav-item {{ ($active ?? '') === 'dashboard' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>Dashboard</a>
        <a href="{{ route('tenant.products') }}" class="nav-item {{ ($active ?? '') === 'products' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>Order Products</a>
        <a href="{{ route('tenant.orders') }}" class="nav-item {{ ($active ?? '') === 'orders' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>My Orders</a>
        <a href="{{ route('tenant.bills') }}" class="nav-item {{ ($active ?? '') === 'bills' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>My Bills</a>
        <a href="{{ route('tenant.electric-bills') }}" class="nav-item {{ ($active ?? '') === 'electric-bills' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Electric Bills</a>
        <a href="{{ route('tenant.maintenance') }}" class="nav-item {{ ($active ?? '') === 'maintenance' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Maintenance</a>
        @if(Route::has('tenant.contracts'))
        <a href="{{ route('tenant.contracts') }}" class="nav-item {{ ($active ?? '') === 'contracts' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Contracts</a>
        @endif
        <a href="{{ route('messages.index') }}" class="nav-item {{ ($active ?? '') === 'messages' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>Messages</a>
        <a href="{{ route('profile.edit') }}" class="nav-item {{ ($active ?? '') === 'profile' ? 'active' : '' }}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21a8 8 0 10-16 0m12-13a4 4 0 11-8 0 4 4 0 018 0z"/></svg>Profile</a>
    </nav>
    @include('partials.notification-bell')
    <div class="sidebar-footer">
        <a href="{{ route('profile.edit') }}" style="text-decoration:none; color:inherit; display:block;">
            <div class="user-info" style="cursor:pointer; transition:0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                <div class="user-avatar">@if(auth()->user()->profilePhotoUrl())<img src="{{ auth()->user()->profilePhotoUrl() }}" alt="">@else{{ strtoupper(substr(auth()->user()->name,0,1)) }}@endif</div>
                <div class="user-meta">
                    <div class="uname">{{ auth()->user()->name }}</div>
                    <div class="urole">Tenant</div>
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
