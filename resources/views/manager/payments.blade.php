<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payments — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .btn-paid{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;}
        .btn-paid:hover{background:#d1fae5;}
        .btn-overdue{background:#fef2f2;color:#dc2626;border:1px solid #fecdd3;}
        .btn-overdue:hover{background:#fee2e2;}
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'payments'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Payment Management</h1>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- Create Payment Bill -->
        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:20px;">Create Payment Bill</h3>
            <form method="POST" action="{{ route('manager.payments.store') }}">@csrf
                <div class="form-row-inline">
                    <div class="form-group">
                        <label class="form-label">Tenant</label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">Select tenant...</option>
                            @foreach($tenants as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stall</label>
                        <select name="stall_id" class="form-select" required>
                            <option value="">Select stall...</option>
                            @foreach($stalls as $s)<option value="{{ $s->id }}">{{ $s->name }} (₱{{ number_format($s->price,2) }})</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (₱)</label>
                        <input type="number" name="amount" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-input" required>
                    </div>
                    <button type="submit" class="btn-submit">Create Bill</button>
                </div>
            </form>
        </div>

        <!-- Payment Records -->
        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:20px;">Payment Records</h3>
            @if($payments->count() > 0)
            <table class="data-table">
                <thead><tr><th>#</th><th>Tenant</th><th>Stall</th><th>Amount Due</th><th>Due Date</th><th>Paid Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($payments as $p)
                    <tr>
                        <td style="font-weight:600;">{{ $p->id }}</td>
                        <td style="font-weight:600;color:#0f172a;">{{ $p->tenant->name ?? 'N/A' }}</td>
                        <td>{{ $p->stall->name ?? 'N/A' }}</td>
                        <td style="font-weight:600;">
                            ₱{{ number_format($p->amountDue(), 2) }}
                            @if($p->lateFee() > 0)<div class="detail-sub">incl. ₱{{ number_format($p->lateFee(), 2) }} late fee</div>@endif
                        </td>
                        <td>{{ $p->due_date->format('M d, Y') }}</td>
                        <td>{{ $p->paid_date?->format('M d, Y') ?? '—' }}</td>
                        <td><x-status-badge :status="$p->status" />@if($p->latestActivity)<div class="detail-sub" title="{{ $p->latestActivity->description }}">{{ $p->latestActivity->created_at->diffForHumans() }}</div>@endif</td>
                        <td>@include('partials.billing.review-actions', ['payable' => $p, 'overdueUrl' => route('manager.payments.markOverdue', $p)])</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="text-align:center;padding:40px;color:#64748b;">No payment records yet.</p>
            @endif
        </div>
    </div>
</body>
</html>
