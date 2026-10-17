<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Bills — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .btn-pay { padding: 7px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #fff; background: #10b981; border: none; cursor: pointer; font-family: 'Inter', sans-serif; transition: 0.2s; text-decoration: none; }
        .btn-pay:hover { background: #059669; }
        .late-fee { font-size: 12px; color: #b91c1c; }
        .empty { text-align: center; padding: 40px; color: #64748b; font-size: 14px; }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.tenant-sidebar', ['active' => 'bills'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>My Bills</h1>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="panel">
            @if($bills->count() > 0)
            <table class="data-table">
                <thead><tr><th>#</th><th>Stall</th><th>Amount Due</th><th>Due Date</th><th>Paid</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($bills as $bill)
                    <tr>
                        <td style="font-weight:600;">{{ $bill->id }}</td>
                        <td style="font-weight:500;color:#0f172a;">{{ $bill->stall->name ?? 'N/A' }}</td>
                        <td style="font-weight:600;">
                            ₱{{ number_format($bill->amountDue(), 2) }}
                            @if($bill->lateFee() > 0)
                                <div class="late-fee">incl. ₱{{ number_format($bill->lateFee(), 2) }} late fee</div>
                            @endif
                        </td>
                        <td style="color:#64748b;">{{ $bill->due_date->format('M d, Y') }}</td>
                        <td style="color:#64748b;">{{ $bill->paid_date?->format('M d, Y') ?? '—' }}</td>
                        <td><x-status-badge :status="$bill->status" /></td>
                        <td>@include('partials.billing.pay-actions', ['payable' => $bill])</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="empty">No bills yet. Your manager will create bills when rent is due.</p>
            @endif
        </div>
    </div>
</body>
</html>
