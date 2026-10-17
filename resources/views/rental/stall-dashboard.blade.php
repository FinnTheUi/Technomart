<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Available Stalls — Technomart</title>
    <meta name="description" content="Browse available stalls for rent. Find the perfect space for your business with Technomart.">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }

        /* Nav */
        .site-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; padding: 0 24px; background: rgba(255,255,255,0.9); backdrop-filter: blur(20px); border-bottom: 1px solid #e2e8f0; }
        .nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 72px; }
        .nav-brand { display: flex; align-items: center; gap: 12px; }
        .nav-brand svg { width: 36px; height: 36px; flex-shrink: 0; }
        .nav-brand span { font-weight: 800; font-size: 18px; letter-spacing: -0.02em; color: #0f172a; }
        .nav-links { display: flex; align-items: center; gap: 12px; }
        .nav-link { font-size: 14px; font-weight: 600; color: #475569; padding: 8px 16px; border-radius: 8px; transition: color 0.2s, background 0.2s; }
        .nav-link:hover { color: #0f172a; background: #f1f5f9; }
        .nav-cta { font-size: 14px; font-weight: 600; color: #fff; padding: 9px 20px; border-radius: 10px; background: #4f46e5; transition: 0.2s; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
        .nav-cta:hover { background: #4338ca; transform: translateY(-1px); }

        /* Page Content */
        .page-container { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 120px 24px 80px; }
        .page-header { text-align: center; margin-bottom: 56px; }
        .page-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px 6px 12px; border-radius: 999px; background: #d1fae5; border: 1px solid #a7f3d0; color: #059669; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
        .page-badge .dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; animation: pulse-dot 2s ease-in-out infinite; }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.7); } }
        .page-header h1 { font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; line-height: 1.1; letter-spacing: -0.03em; color: #0f172a; margin-bottom: 12px; }
        .page-header h1 .gradient-text { color: #4f46e5; }
        .page-header p { font-size: 16px; color: #475569; max-width: 500px; margin: 0 auto; line-height: 1.6; }

        /* Alert */
        .alert { max-width: 600px; margin: -20px auto 32px; padding: 14px 20px; border-radius: 12px; font-size: 14px; font-weight: 600; text-align: center; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }

        /* Stalls Grid */
        .stalls-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
        .stall-card { position: relative; overflow: hidden; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .stall-card:hover { transform: translateY(-4px); border-color: #cbd5e1; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        .stall-card-body { padding: 28px; }
        .stall-name { font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 16px; display: flex; align-items: center; gap: 12px; }
        .stall-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #eff6ff; border: 1px solid #bfdbfe; color: #2563eb; flex-shrink: 0; }
        .stall-icon svg { width: 22px; height: 22px; }
        .stall-details { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
        .stall-detail { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #475569; font-weight: 500; }
        .stall-detail svg { width: 18px; height: 18px; color: #94a3b8; flex-shrink: 0; }
        .stall-price-bar { display: flex; align-items: center; justify-content: space-between; padding: 20px 0 0; border-top: 1px solid #f1f5f9; }
        .stall-price { display: flex; flex-direction: column; }
        .stall-price .amount { font-size: 28px; font-weight: 800; letter-spacing: -0.03em; color: #059669; }
        .stall-price .period { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .btn-select { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; color: #fff; padding: 12px 24px; border-radius: 10px; background: #10b981; transition: 0.2s; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2); }
        .btn-select:hover { transform: translateY(-1px); background: #059669; box-shadow: 0 6px 12px -2px rgba(16, 185, 129, 0.3); }
        .btn-select svg { width: 18px; height: 18px; }

        .btn-waitlist { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; color: #fff; padding: 12px 24px; border-radius: 10px; background: #f59e0b; transition: 0.2s; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.2); }
        .btn-waitlist:hover { transform: translateY(-1px); background: #d97706; box-shadow: 0 6px 12px -2px rgba(245, 158, 11, 0.3); }
        .waitlist-count { background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 6px; font-size: 11px; }

        /* Modal */
        .modal { display: none; position: fixed; inset: 0; z-index: 200; background: rgba(15,23,42,0.6); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; width: 100%; max-width: 480px; position: relative; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-close { position: absolute; top: 16px; right: 16px; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 8px; border-radius: 50%; transition: 0.2s; }
        .modal-close:hover { background: #f1f5f9; color: #0f172a; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .form-input { width: 100%; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 16px; color: #0f172a; font-family: inherit; font-size: 14px; transition: border-color 0.2s; }
        .form-input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
        .btn-submit { width: 100%; padding: 14px; border-radius: 10px; background: #4f46e5; color: #fff; font-weight: 700; font-size: 15px; border: none; cursor: pointer; transition: 0.2s; margin-top: 8px; }
        .btn-submit:hover { background: #4338ca; }

        /* Empty State */
        .empty-state { text-align: center; padding: 80px 24px; background: #fff; border: 1px dashed #cbd5e1; border-radius: 20px; }
        .empty-icon { width: 64px; height: 64px; margin: 0 auto 20px; color: #94a3b8; }
        .empty-state h3 { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; color: #64748b; line-height: 1.6; }

        /* Footer */
        .site-footer { position: relative; z-index: 1; border-top: 1px solid #e2e8f0; padding: 32px 24px; background: #fff; }
        .footer-inner { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
        .footer-inner .copy { font-size: 13px; color: #64748b; font-weight: 500; }

        @media (max-width: 640px) {
            .stalls-grid { grid-template-columns: 1fr; }
            .nav-brand span { display: none; }
        }
        /* Live status on each card: bubble color matches the status */
        .stall-card { position: relative; }
        .card-status { position: absolute; top: 14px; right: 14px; display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; padding: 4px 10px 4px 6px; border-radius: 999px; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; z-index: 1; }
        .card-bubble { width: 8px; height: 8px; border-radius: 50%; }
        .stall-card.available .card-bubble { background: #10b981; animation: cardPulse 2.4s infinite; }
        .stall-card.occupied .card-bubble { background: #0ea5e9; }
        .stall-card.maintenance .card-bubble { background: #f59e0b; }
        .stall-card.available .card-status { color: #047857; border-color: #a7f3d0; background: #ecfdf5; }
        .stall-card.occupied .card-status { color: #0369a1; border-color: #bae6fd; background: #f0f9ff; }
        .stall-card.maintenance .card-status { color: #b45309; border-color: #fde68a; background: #fffbeb; }
        .stall-card.available .btn-waitlist, .stall-card:not(.available) .btn-select { display: none !important; }
        @keyframes cardPulse { 0%, 100% { opacity: 1; } 50% { opacity: .45; } }
        /* Search & filters */
        .stall-toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin: 40px 0 24px; scroll-margin-top: 96px; }
        .search-box { flex: 1; min-width: 240px; position: relative; }
        .search-box svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94a3b8; pointer-events: none; }
        .search-box input { width: 100%; height: 46px; padding: 0 14px 0 42px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; font-family: inherit; background: #fff; color: #0f172a; outline: none; transition: border-color .2s, box-shadow .2s; }
        .search-box input:focus, .stall-toolbar select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.12); }
        .stall-toolbar select { height: 46px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; font-family: inherit; background: #fff; color: #334155; cursor: pointer; outline: none; }
        .btn-search { height: 46px; padding: 0 22px; border: none; border-radius: 12px; background: #4f46e5; color: #fff; font-size: 14px; font-weight: 700; font-family: inherit; cursor: pointer; transition: background .2s; }
        .btn-search:hover { background: #4338ca; }
        .btn-clear { font-size: 14px; font-weight: 600; color: #64748b; padding: 0 6px; }
        .btn-clear:hover { color: #0f172a; }
        @media (max-width: 640px) { .stall-toolbar select, .btn-search { flex: 1; } }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    <!-- Navigation -->
    <header class="site-nav">
        <div class="nav-inner">
            <a href="/" class="nav-brand">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#6366F1"/><stop offset="100%" stop-color="#06B6D4"/></linearGradient></defs><rect width="100" height="100" rx="22" fill="url(#logoGrad)"/><path d="M 28 30 L 72 30" stroke="white" stroke-width="8" stroke-linecap="round"/><path d="M 50 30 L 50 74" stroke="white" stroke-width="8" stroke-linecap="round"/><circle cx="50" cy="26" r="4" fill="rgba(255,255,255,0.4)"/></svg>
                <span>Technomart</span>
            </a>
            <nav class="nav-links">
                <a href="/" class="nav-link">Home</a>
                @auth
                    <a href="{{ url('/dashboard') }}" class="nav-cta">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="nav-link">Sign In</a>
                    <a href="{{ route('stalls.index') }}" class="nav-cta">Browse Stalls</a>
                @endauth
            </nav>
        </div>
    </header>

    <div class="page-container">
        <!-- Header -->
        <div class="page-header">
          
            <h1>Find Your <span class="gradient-text">Perfect Stall</span></h1>
            <p>Browse available stalls below and select one to start your rental application.</p>
        </div>

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert" style="background:#d1fae5;border:1px solid #a7f3d0;color:#059669;">{{ session('success') }}</div>
        @endif

        <!-- Floor Plan Map -->
        <div style="margin-bottom: 48px;">
            <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin-bottom:6px;">Stall Map</h2>
            <p style="font-size:14px;color:#64748b;margin-bottom:20px;">See the layout of all stalls. Click any stall for details or to apply.</p>
            @include('partials.floor-plan-map', ['floor1Stalls' => $floor1Stalls, 'floor2Stalls' => $floor2Stalls, 'interactive' => false])
        </div>

        <!-- Search & filters -->
        <form method="GET" action="{{ route('stalls.index') }}#stall-list" class="stall-toolbar" id="stall-list" role="search">
            <div class="search-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search stall name, e.g. Stall 12 or Cart 3" maxlength="50" aria-label="Search by stall name" id="stall-search">
            </div>
            <select name="floor" aria-label="Floor" onchange="this.form.submit()">
                <option value="">All floors</option>
                @foreach(config('stalls.floors') as $number => $floor)
                    <option value="{{ $number }}" @selected((string) $filters['floor'] === (string) $number)>{{ $floor['label'] }}</option>
                @endforeach
            </select>
            <select name="status" aria-label="Status" onchange="this.form.submit()">
                <option value="">Any status</option>
                @foreach(\App\Enums\StallStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-search">Search</button>
            @if($filters['q'] !== '' || $filters['floor'] || $filters['status'])
                <a href="{{ route('stalls.index') }}#stall-list" class="btn-clear">Clear</a>
            @endif
        </form>

        <!-- Stalls Grid -->
        @if($stalls->count() > 0)
            <div class="stalls-grid">
                @foreach($stalls as $stall)
                    <div class="stall-card {{ $stall->status->value }}" id="stall-{{ $stall->id }}" data-stall-id="{{ $stall->id }}">
                        <span class="card-status"><span class="card-bubble"></span><span class="card-status-label">{{ $stall->status->label() }}</span></span>
                        <div class="stall-card-body">
                            <div class="stall-name">
                                <div class="stall-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                {{ $stall->name }}
                            </div>
                            <div class="stall-details">
                                @if($stall->location)
                                    <div class="stall-detail">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $stall->location }}
                                    </div>
                                @endif
                                @if($stall->size)
                                    <div class="stall-detail">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                        {{ $stall->size }}
                                    </div>
                                @endif
                            </div>
                            <div class="stall-price-bar">
                                <div class="stall-price">
                                    <span class="amount">₱{{ number_format($stall->price, 2) }}</span>
                                    <span class="period">per month</span>
                                </div>
                                {{-- Both actions are rendered; CSS shows the one matching the live status. --}}
                                <a href="{{ route('stalls.apply', $stall) }}" class="btn-select" id="select-stall-{{ $stall->id }}">
                                    Select Stall
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                                <button type="button" class="btn-waitlist" onclick="openWaitlistModal({{ $stall->id }}, '{{ addslashes($stall->name) }}')">
                                    Join Waitlist
                                    @if($stall->waitlists_count > 0)
                                        <span class="waitlist-count">{{ $stall->waitlists_count }} waiting</span>
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            {{ $stalls->links('partials.pagination') }}
        @else
            <div class="empty-state">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                @if($filters['q'] !== '' || $filters['floor'] || $filters['status'])
                    <h3>No stalls match your search</h3>
                    <p>Try a different name or number, or <a href="{{ route('stalls.index') }}#stall-list" style="color:#4f46e5;font-weight:600;">clear the filters</a>.</p>
                @else
                    <h3>No Stalls Yet</h3>
                    <p>Stalls will appear here once they are set up. Please check back later.</p>
                @endif
            </div>
        @endif
    </div>

    <footer class="site-footer">
        <div class="footer-inner">
            <span class="copy">&copy; {{ date('Y') }} Technomart. All rights reserved.</span>
        </div>
    </footer>

    <!-- Waitlist Modal -->
    <div class="modal" id="waitlistModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeWaitlistModal()"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            <h2 style="font-size:24px;font-weight:800;color:#0f172a;margin-bottom:8px;">Join Waitlist</h2>
            <p style="color:#64748b;font-size:14px;margin-bottom:24px;">Get notified when <strong id="waitlistStallName" style="color:#0f172a;"></strong> becomes available.</p>
            <form id="waitlistForm" method="POST" action="">
                @csrf
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number (Optional)</label>
                    <input type="text" name="phone" class="form-input">
                </div>
                <button type="submit" class="btn-submit">Join Waitlist</button>
            </form>
        </div>
    </div>

    <script>
        function openWaitlistModal(stallId, stallName) {
            document.getElementById('waitlistStallName').innerText = stallName;
            document.getElementById('waitlistForm').action = `/stalls/${stallId}/waitlist`;
            document.getElementById('waitlistModal').classList.add('active');
        }
        function closeWaitlistModal() {
            document.getElementById('waitlistModal').classList.remove('active');
        }
        window.onclick = function(event) {
            let modal = document.getElementById('waitlistModal');
            if (event.target == modal) {
                closeWaitlistModal();
            }
        }
    </script>
    <script>
        // Keep the stall cards in step with the live floor-plan feed.
        document.addEventListener('stalls:updated', function (e) {
            e.detail.stalls.forEach(function (stall) {
                const card = document.querySelector('.stall-card[data-stall-id="' + stall.id + '"]');
                if (!card) return;
                card.classList.remove('available', 'occupied', 'maintenance');
                card.classList.add(stall.status);
                card.querySelector('.card-status-label').textContent = stall.status_label;
                const amount = card.querySelector('.stall-price .amount');
                if (amount) amount.textContent = '₱' + Number(stall.price).toLocaleString(undefined, {minimumFractionDigits: 2});
            });
        });
    </script>
</body>
</html>
