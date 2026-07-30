<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Billing & Payments — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body>
    @if(auth()->user()->role === 'admin')
        @include('partials.admin-sidebar', ['active' => 'payments'])
    @elseif(auth()->user()->role === 'manager')
        @include('partials.manager-sidebar', ['active' => 'payments'])
    @elseif(auth()->user()->role === 'tenant')
        @include('partials.tenant-sidebar', ['active' => 'payments'])
    @endif

    <div class="main">
        <header class="header">
            <h2>Billing & Payments</h2>
            <div class="header-actions">
                <div class="user-badge">{{ auth()->user()->name }}</div>
            </div>
        </header>

        <div class="content">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment Records</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                <tr>
                                    <td class="font-medium">#{{ $payment->id }}</td>
                                    <td class="font-semibold text-emerald-600">${{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->due_date }}</td>
                                    <td>
                                        @if($payment->status === 'paid')
                                            <span class="badge badge-green">Paid</span>
                                        @else
                                            <span class="badge badge-red">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($payment->status == 'pending' && auth()->user()->role == 'tenant')
                                        <form action="{{ route('payments.pay', $payment->id) }}" method="POST" style="display:inline-block;">
                                            @csrf
                                            <button type="submit" class="btn-submit" style="padding: 6px 12px; font-size: 13px;">Pay Now</button>
                                        </form>
                                        @elseif($payment->status == 'paid')
                                        <a href="{{ route('payments.receipt', $payment->id) }}" class="btn-secondary" style="padding: 6px 12px; font-size: 13px;">View Receipt</a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="empty-state">No payments found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
