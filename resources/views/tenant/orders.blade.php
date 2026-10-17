<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .btn-pay { padding: 7px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #fff; background: #10b981; border: none; cursor: pointer; font-family: 'Inter', sans-serif; }
        .btn-pay:hover { background: #059669; }
        .empty { text-align: center; padding: 40px; color: #64748b; font-size: 14px; }
        .note { font-size: 13px; color: #64748b; margin-top: 4px; }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.tenant-sidebar', ['active' => 'orders'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>My Orders</h1>
                <p class="note">Unpaid orders are cancelled automatically after {{ config('billing.order_auto_cancel_days') }} days and the stock is released.</p>
            </div>
            <a href="{{ route('tenant.products') }}" class="btn-sm btn-view">Browse products</a>
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
            @if($orders->count() > 0)
            <table class="data-table">
                <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Total</th><th>Placed</th><th>Status</th><th>Payment</th><th></th></tr></thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td style="font-weight:600;">{{ $order->id }}</td>
                        <td style="font-weight:500;color:#0f172a;">{{ $order->product->name ?? 'Product removed' }}</td>
                        <td>{{ $order->quantity }}</td>
                        <td style="font-weight:600;">₱{{ number_format($order->total_price, 2) }}</td>
                        <td style="color:#64748b;">{{ $order->created_at->format('M d, Y') }}</td>
                        <td>
                            <x-status-badge :status="$order->status" />
                            @if($order->delivered_at)<div class="detail-sub">{{ $order->delivered_at->format('M d, Y') }}</div>@endif
                            @if($order->latestActivity)<div class="detail-sub" title="{{ $order->latestActivity->description }}">Updated {{ $order->latestActivity->created_at->diffForHumans() }}</div>@endif
                        </td>
                        <td>@include('partials.billing.pay-actions', ['payable' => $order])</td>
                        <td>
                            @if($order->status->isCancellable())
                                <details><summary class="btn-sm btn-reject" style="cursor:pointer;list-style:none">Cancel…</summary>
                                    <form method="POST" action="{{ route('tenant.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order and release its reserved stock?')" style="margin-top:6px">
                                        @csrf @method('PATCH')
                                        <input name="reason" class="form-input" required maxlength="255" placeholder="Reason">
                                        <button type="submit" class="btn-sm btn-reject">Confirm</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="empty">You haven't ordered anything yet.</p>
            @endif
        </div>
    </div>
</body>
</html>
