<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    @vite('resources/js/app.js')
    <style>
        .chart-container{position:relative;height:300px}
        .activity-feed{max-height:420px;overflow-y:auto}
        .activity-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #f1f5f9}
        .activity-item:last-child{border-bottom:none}
        .activity-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px}
        .activity-text{flex:1}
        .activity-text .activity-desc{font-size:13px;color:#475569;line-height:1.4}
        .activity-text .activity-time{font-size:11px;color:#94a3b8;margin-top:2px}
        .rank-badge{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;font-size:12px;font-weight:700}
        .rank-1{background:#fffbeb;color:#d97706;border:1px solid #fde68a}
        .rank-2{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0}
        .rank-3{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
        .rank-default{background:#f8fafc;color:#94a3b8;border:1px solid #e2e8f0}
    </style>
</head>
<body>

    @include('partials.admin-sidebar', ['active' => 'dashboard'])

    <!-- Main Content -->
    <div class="main">
        <div class="page-header">
            <h1>Dashboard Overview</h1>
        </div>

        @if($lowStockCount > 0 || $ordersAwaitingAction > 0)
        <div class="alert alert-success" style="background:#eef2ff;border-color:#c7d2fe;color:#3730a3;">
            @if($ordersAwaitingAction > 0)<a href="{{ route('admin.orders.index') }}" style="font-weight:700;">{{ $ordersAwaitingAction }} order(s) to verify or deliver</a>@endif
            @if($ordersAwaitingAction > 0 && $lowStockCount > 0) &middot; @endif
            @if($lowStockCount > 0)<a href="{{ route('admin.products') }}" style="font-weight:700;">{{ $lowStockCount }} product(s) have low stock</a>@endif
        </div>
        @endif

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-indigo">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="stat-value">{{ $totalTenants }}</div>
                <div class="stat-label">Total Tenants</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-emerald">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="stat-value">{{ $totalManagers }}</div>
                <div class="stat-label">Stall Managers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-sky">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div class="stat-value">{{ $totalProducts }}</div>
                <div class="stat-label">Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-amber">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div class="stat-value">{{ $totalOrders }}</div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-rose">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="stat-value">₱{{ number_format($totalRevenue, 2) }}</div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-violet">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div class="stat-value">{{ $totalStalls }}</div>
                <div class="stat-label">Total Stalls</div>
            </div>
        </div>

        <!-- Chart + Top Tenants -->
        <div class="two-col">
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Most Ordered Products</div><div class="panel-subtitle">Top products by total quantity sold</div></div>
                </div>
                <div class="chart-container"><canvas id="productChart"></canvas></div>
            </div>
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Top Buying Tenants</div><div class="panel-subtitle">Ranked by total items purchased</div></div>
                </div>
                @if($topTenants->count() > 0)
                <table class="data-table">
                    <thead><tr><th>Rank</th><th>Tenant</th><th>Items</th><th>Spent</th></tr></thead>
                    <tbody>
                        @foreach($topTenants as $i => $t)
                        <tr>
                            <td><span class="rank-badge {{ $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : 'rank-default')) }}">{{ $i + 1 }}</span></td>
                            <td><div style="font-weight:600;color:#0f172a;">{{ $t->name }}</div><div style="font-size:12px;color:#94a3b8;">{{ $t->email }}</div></td>
                            <td>{{ $t->total_bought }}</td>
                            <td>₱{{ number_format($t->total_spent, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <p style="color:#94a3b8;font-size:14px;text-align:center;padding:40px 0;">No order data yet.</p>
                @endif
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="panel">
            <div class="panel-header">
                <div><div class="panel-title">Recent Orders</div><div class="panel-subtitle">Latest tenant purchase activity</div></div>
            </div>
            @if($recentOrders->count() > 0)
            <table class="data-table">
                <thead><tr><th>Order #</th><th>Tenant</th><th>Product</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach($recentOrders as $order)
                    <tr>
                        <td style="font-weight:600;">#{{ $order->id }}</td>
                        <td>{{ $order->user->name ?? 'N/A' }}</td>
                        <td>{{ $order->product->name ?? 'N/A' }}</td>
                        <td>{{ $order->quantity }}</td>
                        <td>₱{{ number_format($order->total_price, 2) }}</td>
                        <td><x-status-badge :status="$order->status" /></td>
                        <td style="color:#94a3b8;">{{ $order->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
                <p style="color:#94a3b8;font-size:14px;text-align:center;padding:40px 0;">No orders yet.</p>
            @endif
        </div>

        <!-- Monthly Revenue + Stall Occupancy + Activity Feed -->
        <div class="three-col">
            <div class="panel">
                <div class="panel-header"><div><div class="panel-title">Monthly Rental Revenue</div><div class="panel-subtitle">Last 12 months of paid rent</div></div></div>
                <div class="chart-container"><canvas id="revenueChart"></canvas></div>
            </div>
            <div class="panel">
                <div class="panel-header"><div><div class="panel-title">Stall Occupancy</div><div class="panel-subtitle">Current status breakdown</div></div></div>
                <div class="chart-container"><canvas id="occupancyChart"></canvas></div>
            </div>
            <div class="panel">
                <div class="panel-header"><div><div class="panel-title">Activity Feed</div><div class="panel-subtitle">Recent system events</div></div></div>
                <div class="activity-feed">
                    @forelse($activityFeed as $activity)
                    <div class="activity-item">
                        <div class="activity-icon" style="background:#eef2ff;color:#4f46e5;">⚡</div>
                        <div class="activity-text">
                            <div class="activity-desc">{{ $activity->description }}</div>
                            <div class="activity-time">{{ $activity->created_at->diffForHumans() }}{{ $activity->user ? ' by ' . $activity->user->name : '' }}</div>
                        </div>
                    </div>
                    @empty
                    <p style="color:#94a3b8;font-size:13px;text-align:center;padding:40px 0;">No activity recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        // Product sales chart
        const ctx = document.getElementById('productChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Units Sold',
                    data: @json($chartData),
                    backgroundColor: 'rgba(79, 70, 229, 0.15)',
                    borderColor: '#4f46e5',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', font: { family: 'Inter' } } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { family: 'Inter', size: 12 } } }
                }
            }
        });

        // Monthly revenue chart
        new Chart(document.getElementById('revenueChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($monthlyLabels),
                datasets: [{
                    label: 'Revenue (₱)',
                    data: @json($monthlyRevenue),
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5,150,105,0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#059669',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', font: { family: 'Inter' }, callback: v => '₱' + v.toLocaleString() } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } } }
                }
            }
        });

        // Stall occupancy doughnut
        new Chart(document.getElementById('occupancyChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Available', 'Occupied', 'Maintenance'],
                datasets: [{
                    data: [{{ $stallOccupancy['available'] }}, {{ $stallOccupancy['occupied'] }}, {{ $stallOccupancy['maintenance'] }}],
                    backgroundColor: ['#10b981', '#4f46e5', '#f59e0b'],
                    borderColor: '#fff',
                    borderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#64748b', font: { family: 'Inter', size: 12 }, padding: 16 } }
                }
            }
        });
    </script>
</body>
</html>
