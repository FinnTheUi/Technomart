<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Bills — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .btn-pay { padding: 7px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #fff; background: #10b981; border: none; cursor: pointer; font-family: 'Inter', sans-serif; transition: 0.2s; text-decoration: none; }
        .btn-pay:hover { background: #059669; }
        .settled { font-size: 13px; color: #059669; font-weight: 500; }
        .empty { text-align: center; padding: 40px; color: #64748b; font-size: 14px; }
    </style>
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

        <div class="panel">
            @if($bills->count() > 0)
            <table class="data-table">
                <thead><tr><th>#</th><th>Stall</th><th>Amount</th><th>Due Date</th><th>Paid Date</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($bills as $bill)
                    <tr>
                        <td style="font-weight:600;">{{ $bill->id }}</td>
                        <td style="font-weight:500;color:#0f172a;">{{ $bill->stall->name ?? 'N/A' }}</td>
                        <td style="font-weight:600;">₱{{ number_format($bill->amount, 2) }}</td>
                        <td style="color:#64748b;">{{ \Carbon\Carbon::parse($bill->due_date)->format('M d, Y') }}</td>
                        <td style="color:#64748b;">{{ $bill->paid_date ? \Carbon\Carbon::parse($bill->paid_date)->format('M d, Y') : '—' }}</td>
                        <td><span class="badge {{ $bill->status==='paid'?'badge-green':($bill->status==='pending'?'badge-yellow':'badge-red') }}">{{ ucfirst($bill->status) }}</span></td>
                        <td>
                            @if($bill->status !== 'paid')
                            <form method="POST" action="{{ route('tenant.bills.pay', $bill) }}">@csrf @method('PATCH')
                                <button type="submit" class="btn-pay" onclick="return confirm('Confirm payment of ₱{{ number_format($bill->amount,2) }}?')">Pay Now</button>
                            </form>
                            @else
                            <span class="settled">✓ Settled</span>
                            @endif
                        </td>
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
