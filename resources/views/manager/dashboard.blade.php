<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manager Dashboard — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
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
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Stall Availability</div><div class="panel-subtitle">Real-time stall status overview</div></div>
                    <a href="{{ route('manager.stalls') }}" style="font-size:13px;color:#4f46e5;font-weight:500;text-decoration:none;">View All →</a>
                </div>
                @if($stalls->count() > 0)
                <div class="stall-grid">
                    @foreach($stalls as $stall)
                    <div class="stall-tile {{ $stall->status }}">
                        <div class="stall-name">{{ $stall->name }}</div>
                        <div class="stall-price">₱{{ number_format($stall->price, 2) }}/mo</div>
                        <div class="stall-status" style="color: {{ $stall->status === 'available' ? '#059669' : ($stall->status === 'occupied' ? '#0284c7' : '#d97706') }}">
                            {{ ucfirst($stall->status) }}
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="empty">No stalls yet. <a href="{{ route('manager.stalls.create') }}" style="color:#4f46e5;">Add your first stall →</a></p>
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

        <!-- Predictive Analytics -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Predictive Analytics — Late Payment Risk</div>
                    <div class="panel-subtitle">Tenants flagged based on payment history patterns</div>
                </div>
            </div>
            @if($riskTenants->count() > 0)
            <table class="data-table">
                <thead><tr><th>Tenant</th><th>Overdue</th><th>Paid</th><th>Risk Score</th><th>Risk Level</th></tr></thead>
                <tbody>
                    @foreach($riskTenants as $rt)
                    <tr>
                        <td><div style="font-weight:600;color:#0f172a;">{{ $rt->name }}</div><div style="font-size:12px;color:#64748b;">{{ $rt->email }}</div></td>
                        <td><span class="badge badge-red">{{ $rt->overdue_count }}</span></td>
                        <td><span class="badge badge-green">{{ $rt->paid_count }}</span></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="risk-bar {{ $rt->risk_score >= 50 ? 'risk-high' : ($rt->risk_score >= 25 ? 'risk-medium' : 'risk-low') }}">
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
            <div class="panel" style="max-height: 400px; overflow-y: auto;">
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
                @else
                <p class="empty" style="padding:20px;">No recent activity.</p>
                @endif
            </div>
        </div>

        <div class="two-col">
            <!-- Recent Payments -->
            <div class="panel">
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
                            <td><span class="badge {{ $p->status === 'paid' ? 'badge-green' : ($p->status === 'pending' ? 'badge-yellow' : 'badge-red') }}">{{ ucfirst($p->status) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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
                            <span class="badge {{ $mr->status === 'pending' ? 'badge-yellow' : ($mr->status === 'resolved' ? 'badge-green' : 'badge-purple') }}">{{ ucfirst(str_replace('_', ' ', $mr->status)) }}</span>
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
