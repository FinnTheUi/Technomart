<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tenant Dashboard — Technomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    @vite('resources/js/app.js')
    <style>
        .empty { text-align: center; padding: 40px; color: #64748b; font-size: 14px; }
    </style>
</head>
<body>
    @include('partials.tenant-sidebar', ['active' => 'dashboard'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Tenant Dashboard</h1>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-amber"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div class="stat-value">{{ $pendingBills }}</div>
                <div class="stat-label">Pending Bills</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-emerald"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div class="stat-value">₱{{ number_format($totalPaid, 2) }}</div>
                <div class="stat-label">Total Paid</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-sky"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg></div>
                <div class="stat-value">{{ $totalOrders }}</div>
                <div class="stat-label">Orders Placed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-violet"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35"/></svg></div>
                <div class="stat-value">{{ $maintenanceCount }}</div>
                <div class="stat-label">Maintenance Requests</div>
            </div>
        </div>

        <div class="two-col">
            <!-- Active Contracts -->
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Active Contracts</div><div class="panel-subtitle">Your current leases</div></div>
                    <a href="{{ route('tenant.contracts') }}" style="font-size:13px;color:#10b981;font-weight:500;text-decoration:none;">View All →</a>
                </div>
                @if(isset($activeContracts) && $activeContracts->count() > 0)
                <table class="data-table">
                    <thead><tr><th>Stall</th><th>Rent</th><th>End Date</th></tr></thead>
                    <tbody>
                    @foreach($activeContracts as $c)
                    <tr>
                        <td style="font-weight:600;color:#0f172a;">{{ $c->stall->name ?? 'N/A' }}</td>
                        <td style="font-weight:500;">₱{{ number_format($c->stall->price ?? 0,2) }}</td>
                        <td style="color:#64748b;">{{ $c->end_date->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                <p class="empty" style="padding:20px;">No active contracts.</p>
                @endif
            </div>

            <!-- Spending Chart -->
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Monthly Spending</div><div class="panel-subtitle">Last 6 months</div></div>
                </div>
                <div style="position:relative;height:200px;width:100%;"><canvas id="spendingChart"></canvas></div>
            </div>
        </div>

        <div class="two-col">
            <!-- Recent Bills -->
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Recent Bills</div><div class="panel-subtitle">Your latest rent bills</div></div>
                    <a href="{{ route('tenant.bills') }}" style="font-size:13px;color:#10b981;font-weight:500;text-decoration:none;">View All →</a>
                </div>
                @if($recentBills->count() > 0)
                <table class="data-table">
                    <thead><tr><th>Stall</th><th>Amount</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($recentBills as $b)
                    <tr>
                        <td style="font-weight:500;color:#0f172a;">{{ $b->stall->name ?? 'N/A' }}</td>
                        <td style="font-weight:600;">₱{{ number_format($b->amount,2) }}</td>
                        <td style="color:#64748b;">{{ \Carbon\Carbon::parse($b->due_date)->format('M d, Y') }}</td>
                        <td><x-status-badge :status="$b->status" /></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                <p class="empty">No bills yet.</p>
                @endif
            </div>

            <!-- Recent Orders -->
            <div class="panel">
                <div class="panel-header">
                    <div><div class="panel-title">Recent Orders</div><div class="panel-subtitle">Your product purchases</div></div>
                    <a href="{{ route('tenant.products') }}" style="font-size:13px;color:#10b981;font-weight:500;text-decoration:none;">Browse Products →</a>
                </div>
                @if($recentOrders->count() > 0)
                <table class="data-table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Total</th><th>Date</th></tr></thead>
                    <tbody>
                    @foreach($recentOrders as $o)
                    <tr>
                        <td style="font-weight:600;color:#0f172a;">{{ $o->product->name ?? 'N/A' }}</td>
                        <td>{{ $o->quantity }}</td>
                        <td style="font-weight:600;">₱{{ number_format($o->total_price,2) }}</td>
                        <td style="color:#64748b;">{{ $o->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                <p class="empty">No orders yet.</p>
                @endif
            </div>
        </div>

        <!-- Maintenance Requests -->
        <div class="panel">
            <div class="panel-header">
                <div><div class="panel-title">Maintenance Requests</div><div class="panel-subtitle">Your submitted issues</div></div>
                <a href="{{ route('tenant.maintenance') }}" style="font-size:13px;color:#10b981;font-weight:500;text-decoration:none;">Submit New →</a>
            </div>
            @if($recentMaintenance->count() > 0)
            <table class="data-table">
                <thead><tr><th>Subject</th><th>Stall</th><th>Priority</th><th>Status</th><th>Submitted</th></tr></thead>
                <tbody>
                @foreach($recentMaintenance as $m)
                <tr>
                    <td style="font-weight:600;color:#0f172a;"><a href="{{ route('tenant.maintenance.detail', $m) }}" style="text-decoration:none;color:#0f172a;">{{ $m->subject }}</a></td>
                    <td>{{ $m->stall->name ?? 'N/A' }}</td>
                    <td><span class="badge {{ $m->priority==='urgent'?'badge-red':($m->priority==='high'?'badge-yellow':'badge-blue') }}">{{ ucfirst($m->priority) }}</span></td>
                    <td><x-status-badge :status="$m->status" /></td>
                    <td style="color:#64748b;">{{ $m->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <p class="empty">No maintenance requests submitted yet.</p>
            @endif
        </div>
    </div>

    @if(isset($monthlyLabels) && isset($monthlySpending))
    <script>
        const ctx = document.getElementById('spendingChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($monthlyLabels) !!},
                datasets: [{
                    label: 'Spent (₱)',
                    data: {!! json_encode($monthlySpending) !!},
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    borderColor: '#10b981',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
    </script>
    @endif
</body>
</html>
