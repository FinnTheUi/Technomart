<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply Orders — Technomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .filters a { font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 8px; border: 1px solid #e2e8f0; color: #475569; text-decoration: none; }
        .filters a.active { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        .empty { text-align: center; padding: 40px; color: #64748b; }
    </style>
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'orders'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Supply Orders</h1>
                <p>Verify tenant payments, deliver paid orders, or cancel (stock is returned automatically).</p>
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
            <div class="filters">
                <a href="{{ route('admin.orders.index') }}" class="{{ request('status') ? '' : 'active' }}">All</a>
                @foreach($statuses as $status)
                    <a href="{{ route('admin.orders.index', ['status' => $status->value]) }}" class="{{ request('status') === $status->value ? 'active' : '' }}">{{ $status->label() }}</a>
                @endforeach
            </div>

            @if($orders->count() > 0)
            <table class="data-table">
                <thead><tr><th>#</th><th>Tenant</th><th>Product</th><th>Qty</th><th>Total</th><th>Placed</th><th>Status</th><th>Payment</th><th>Fulfilment</th></tr></thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td style="font-weight:600;">{{ $order->id }}</td>
                        <td>{{ $order->user->name ?? 'N/A' }}</td>
                        <td>{{ $order->product->name ?? 'N/A' }}</td>
                        <td>{{ $order->quantity }}</td>
                        <td style="font-weight:600;">₱{{ number_format($order->total_price, 2) }}</td>
                        <td style="color:#64748b;">{{ $order->created_at->format('M d, Y') }}</td>
                        <td><x-status-badge :status="$order->status" /></td>
                        <td>@include('partials.billing.review-actions', ['payable' => $order])</td>
                        <td>
                            <div class="action-cell">
                                @if($order->status === \App\Enums\OrderStatus::Paid)
                                    <form method="POST" action="{{ route('admin.orders.deliver', $order) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-sm btn-approve">Mark delivered</button>
                                    </form>
                                @endif
                                @if($order->status->isCancellable())
                                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Cancel order #{{ $order->id }} and return stock?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-sm btn-reject">Cancel</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:16px;">{{ $orders->links() }}</div>
            @else
            <p class="empty">No orders found.</p>
            @endif
        </div>
    </div>
</body>
</html>
