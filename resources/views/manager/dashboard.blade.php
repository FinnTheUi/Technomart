<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manager Dashboard — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    @vite('resources/js/app.js')
    <style>
        .chart-container{position:relative;height:220px}
        /* Risk meter */
        .risk-bar { width: 80px; height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
        .risk-bar-fill { height: 100%; border-radius: 3px; transition: width 0.5s; }
        .risk-high .risk-bar-fill { background: #ef4444; }
        .risk-medium .risk-bar-fill { background: #f59e0b; }
        .risk-low .risk-bar-fill { background: #10b981; }

        /* Stall grid */
        .stall-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
        .stall-tile { padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; transition: transform 0.2s; background: #fff; }
        .stall-tile:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stall-tile.available { background: #ecfdf5; border-color: #a7f3d0; }
        .stall-tile.occupied { background: #f0f9ff; border-color: #bae6fd; }
        .stall-tile.maintenance { background: #fffbeb; border-color: #fde68a; }
        .stall-name { font-weight: 700; font-size: 14px; color: #0f172a; margin-bottom: 4px; }
        .stall-price { font-size: 12px; color: #64748b; }
        .stall-status { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 8px; }

        .empty { text-align: center; padding: 40px; color: #64748b; font-size: 14px; }
        
        /* Dashboard specific layout */
        .alert-review { background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px 20px; margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between; }
        .alert-review-content { display: flex; align-items: center; gap: 12px; }
        .alert-review-text { color: #d97706; font-weight: 600; font-size: 15px; }
        .btn-review { background: #fef3c7; color: #d97706; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; transition: 0.2s; border: 1px solid #fde68a; text-decoration: none; }
        .btn-review:hover { background: #fde68a; }
        a.stall-tile { display: block; text-decoration: none; color: inherit; }
        #stall-availability, #risk-analytics, #activity-feed, #recent-payments { scroll-margin-top: 20px; }
        .dash-search { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .dash-search-box { flex: 1; min-width: 180px; position: relative; }
        .dash-search-box svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; pointer-events: none; }
        .dash-search-box input { width: 100%; height: 38px; padding: 0 10px 0 34px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; font-family: inherit; outline: none; }
        .dash-search select { height: 38px; padding: 0 8px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; font-family: inherit; background: #fff; }
        .dash-search-box input:focus, .dash-search select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.12); }
        .dash-search button { height: 38px; padding: 0 16px; border: none; border-radius: 10px; background: #4f46e5; color: #fff; font-weight: 600; font-size: 13px; cursor: pointer; font-family: inherit; }
        .dash-clear { align-self: center; font-size: 13px; color: #64748b; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'dashboard'])

    <div class="main">
        <div class="page-header">
            <h1>Manager Dashboard</h1>
        </div>

        @if(isset($pendingAppsCount) && $pendingAppsCount > 0)
        <div class="alert-review">
            <div class="alert-review-content">
                <svg fill="none" stroke="#d97706" viewBox="0 0 24 24" style="width:24px; height:24px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <div class="alert-review-text">You have {{ $pendingAppsCount }} pending rental application(s) awaiting approval.</div>
            </div>
            <a href="{{ route('manager.rental-applications') }}" class="btn-review">Review Now</a>
        </div>
        @endif

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-indigo"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg></div>
                <div class="stat-value">{{ $totalStalls }}</div>
                <div class="stat-label">Total Stalls</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-emerald"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div class="stat-value">{{ $availableStalls }}</div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-sky"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg></div>
                <div class="stat-value">{{ $occupiedStalls }}</div>
                <div class="stat-label">Occupied</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-emerald"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/></svg></div>
                <div class="stat-value">₱{{ number_format($totalCollected, 2) }}</div>
                <div class="stat-label">Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-amber"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div class="stat-value">₱{{ number_format($totalPending, 2) }}</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-rose"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
                <div class="stat-value">{{ $overdueCount }}</div>
                <div class="stat-label">Overdue</div>
            </div>
        </div>

        <div class="two-col">
            <!-- Stall Availability Map -->
            <div class="panel" id="stall-availability">
                <div class="panel-header">
                    <div><div class="panel-title">Stall Availability</div><div class="panel-subtitle">Real-time stall status overview</div></div>
                    <a href="{{ route('manager.stalls') }}" style="font-size:13px;color:#4f46e5;font-weight:500;text-decoration:none;">View All →</a>
                </div>
                @if($stalls->count() > 0)
                <form method="GET" action="{{ route('manager.dashboard') }}#stall-availability" class="dash-search" role="search">
                    <div class="dash-search-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                        <input type="search" name="stall_q" value="{{ $stallFilters['q'] }}" placeholder="Search stall name, e.g. Stall 12" maxlength="50" aria-label="Search stalls by name">
                    </div>
                    <select name="stall_status" aria-label="Status" onchange="this.form.submit()">
                        <option value="">Any status</option>
                        @foreach(\App\Enums\StallStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($stallFilters['status'] === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <button type="submit">Search</button>
                    @if($stallFilters['q'] !== '' || $stallFilters['status'])
                        <a href="{{ route('manager.dashboard') }}#stall-availability" class="dash-clear">Clear</a>
                    @endif
                </form>
                @if($stallPage->count() > 0)
                <div class="stall-grid">
                    @foreach($stallPage as $stall)
                    <a href="{{ route('manager.stalls.edit', $stall) }}" class="stall-tile {{ $stall->status->value }}">
                        <div class="stall-name">{{ $stall->name }}</div>
                        <div class="stall-price">₱{{ number_format($stall->price, 2) }}/mo</div>
                        <div class="stall-status" style="color: {{ $stall->status->color() }}">
                            {{ $stall->status->label() }}
                        </div>
                    </a>
                    @endforeach
                </div>
                {{ $stallPage->links('partials.pagination') }}
                @else
                <p class="empty">No stalls match your search.</p>
                @endif
                @else
                <p class="empty">No stalls assigned to you. Stalls are generated from the floor layout for the mall's manager.</p>
                @endif
            </div>

            <!-- Stall Distribution Chart -->
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Stall Distribution</div><div class="panel-subtitle">Occupancy breakdown</div></div>
                </div>
                <div class="chart-container">
                    <canvas id="stallChart"></canvas>
                </div>
            </div>
        </div>

        @if($awaitingVerification > 0 || $renewalRequests > 0)
        <div class="alert alert-success" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3;">
            @if($awaitingVerification > 0)
                <a href="{{ route('manager.payments') }}" style="font-weight:700;">{{ $awaitingVerification }} payment(s) awaiting verification</a>
            @endif
            @if($awaitingVerification > 0 && $renewalRequests > 0) &middot; @endif
            @if($renewalRequests > 0)
                <a href="{{ route('manager.contracts.index') }}" style="font-weight:700;">{{ $renewalRequests }} lease renewal request(s)</a>
            @endif
        </div>
        @endif
        <!-- Predictive Analytics -->
        <div class="panel" id="risk-analytics">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Predictive Analytics — Late Payment Risk</div>
                    <div class="panel-subtitle">Tenants flagged based on payment history patterns</div>
                </div>
            </div>
            @if($riskTenants->count() > 0)
            <table class="data-table">
                <thead><tr><th>Tenant</th><th>Why</th><th>Risk Score</th><th>Risk Level</th></tr></thead>
                <tbody>
                    @foreach($riskTenants as $rt)
                    <tr>
                        <td><div style="font-weight:600;color:#0f172a;">{{ $rt->name }}</div><div style="font-size:12px;color:#64748b;">{{ $rt->email }}</div></td>
                        <td>
                            <ul style="margin:0;padding-left:16px;font-size:13px;color:#475569;">
                                @foreach($rt->factors as $factor)<li>{{ $factor }}</li>@endforeach
                            </ul>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="risk-bar risk-{{ strtolower($rt->risk_level) }}">
                                    <div class="risk-bar-fill" style="width:{{ $rt->risk_score }}%"></div>
                                </div>
                                <span style="font-size:13px;font-weight:600;color:#0f172a;">{{ $rt->risk_score }}%</span>
                            </div>
                        </td>
                        <td><span class="badge {{ $rt->risk_level === 'High' ? 'badge-red' : ($rt->risk_level === 'Medium' ? 'badge-yellow' : 'badge-green') }}">{{ $rt->risk_level }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <p style="font-size:12px;color:#94a3b8;margin-top:10px;">
                {{ $riskTenants->first()->source === 'model' ? 'Scored by the logistic-regression model' : 'Scored from payment history (model unavailable)' }}
                &middot; updated {{ $riskTenants->first()->computed_at->diffForHumans() }}
            </p>
            @if($modelMetrics)
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:12px;padding:12px;background:#f8fafc;border-radius:10px;font-size:12px;color:#475569;">
                <strong>Model evaluation:</strong>
                <span>{{ $modelMetrics['rows'] }} training rows</span>
                <span>{{ $modelMetrics['late_rows'] }} late / {{ $modelMetrics['on_time_rows'] }} on-time</span>
                @foreach(['precision','recall','f1','roc_auc'] as $metric)
                    @isset($modelMetrics[$metric])<span>{{ strtoupper(str_replace('_',' ', $metric)) }}: {{ number_format($modelMetrics[$metric], 2) }}</span>@endisset
                @endforeach
                <span>Trained {{ \Carbon\Carbon::parse($modelMetrics['trained_at'])->diffForHumans() }}</span>
            </div>
            @endif
            {{ $riskTenants->links('partials.pagination') }}
            @else
            <p class="empty">No risk data yet. Payment history will be analyzed as transactions accumulate.</p>
            @endif
        </div>

        <div class="two-col">
            <!-- Revenue Chart -->
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Monthly Revenue</div><div class="panel-subtitle">Last 6 months</div></div>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="panel" id="activity-feed">
                <div class="panel-header">
                    <div><div class="panel-title">Activity Feed</div><div class="panel-subtitle">Recent system events</div></div>
                </div>
                @if(isset($activityFeed) && $activityFeed->count() > 0)
                    @foreach($activityFeed as $log)
                    <div style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
                        <div style="font-size:13px;color:#334155;">{{ $log->description }}</div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:4px;">{{ $log->created_at->diffForHumans() }}</div>
                    </div>
                    @endforeach
                    {{ $activityFeed->links('partials.pagination') }}
                @else
                <p class="empty" style="padding:20px;">No recent activity.</p>
                @endif
            </div>
        </div>

        <div class="two-col">
            <!-- Recent Payments -->
            <div class="panel" id="recent-payments">
                <div class="panel-header">
                    <div><div class="panel-title">Recent Payments</div><div class="panel-subtitle">Latest rent transactions</div></div>
                    <a href="{{ route('manager.payments') }}" style="font-size:13px;color:#4f46e5;font-weight:500;text-decoration:none;">Manage →</a>
                </div>
                @if($recentPayments->count() > 0)
                <table class="data-table">
                    <thead><tr><th>Tenant</th><th>Amount</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($recentPayments as $p)
                        <tr>
                            <td style="font-weight:500;color:#0f172a;">{{ $p->tenant->name ?? 'N/A' }}</td>
                            <td style="font-weight:500;">₱{{ number_format($p->amount, 2) }}</td>
                            <td style="color:#64748b;">{{ \Carbon\Carbon::parse($p->due_date)->format('M d, Y') }}</td>
                            <td><x-status-badge :status="$p->status" /></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $recentPayments->links('partials.pagination') }}
                @else
                <p class="empty">No payment records yet.</p>
                @endif
            </div>

            <!-- Maintenance Inbox Preview -->
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Maintenance Inbox</div><div class="panel-subtitle">{{ isset($unreadCount) ? $unreadCount : 0 }} pending request(s)</div></div>
                    <a href="{{ route('manager.inbox') }}" style="font-size:13px;color:#4f46e5;font-weight:500;text-decoration:none;">View All →</a>
                </div>
                @if($maintenanceRequests->count() > 0)
                    @foreach($maintenanceRequests->take(5) as $mr)
                    <div style="padding:14px 0;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-weight:600;font-size:14px;color:#0f172a;">{{ $mr->subject }}</div>
                            <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $mr->tenant->name ?? 'Unknown' }} · {{ $mr->stall->name ?? '' }} · {{ $mr->created_at->diffForHumans() }}</div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <span class="badge {{ $mr->priority === 'urgent' ? 'badge-red' : ($mr->priority === 'high' ? 'badge-yellow' : 'badge-blue') }}">{{ ucfirst($mr->priority) }}</span>
                            <x-status-badge :status="$mr->status" />
                        </div>
                    </div>
                    @endforeach
                @else
                <p class="empty">No maintenance requests yet.</p>
                @endif
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('stallChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'Occupied', 'Maintenance'],
                datasets: [{
                    data: [{{ $availableStalls }}, {{ $occupiedStalls }}, {{ $maintenanceStalls }}],
                    backgroundColor: ['#34d399', '#38bdf8', '#fbbf24'],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#64748b', font: { family: 'Inter', size: 12 }, padding: 16 } }
                }
            }
        });
        
        @if(isset($monthlyLabels) && isset($monthlyRevenue))
        const revCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($monthlyLabels) !!},
                datasets: [{
                    label: 'Revenue (₱)',
                    data: {!! json_encode($monthlyRevenue) !!},
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    borderColor: '#10b981',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', font: { family: 'Inter' } } },
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'Inter' } } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#0f172a', titleFont: { family: 'Inter' }, bodyFont: { family: 'Inter' }, padding: 10, cornerRadius: 8, displayColors: false }
                }
            }
        });
        @endif
    </script>
</body>
</html>
