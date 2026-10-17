<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $po->po_number }} — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .po-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        @media print { .sidebar, .po-actions, .back-link { display: none !important; } .main { margin: 0 !important; } }
    </style>
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'purchase-orders'])

    <div class="main">
        <a href="{{ route('admin.purchase-orders.index') }}" class="back-link">← Purchase orders</a>
        <div class="page-header">
            <div>
                <h1>Purchase Order {{ $po->po_number }}</h1>
                <p>{{ $po->supplier->name }} · created {{ $po->created_at->format('M d, Y') }} by {{ $po->creator->name ?? 'system' }}</p>
            </div>
            <x-status-badge :status="$po->status" />
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="panel">
            <div class="info-grid" style="margin-bottom:20px;">
                <div class="info-item"><label>Supplier contact</label><div class="info-value">{{ $po->supplier->contact_person ?? '—' }} {{ $po->supplier->phone }}</div></div>
                <div class="info-item"><label>Ordered</label><div class="info-value">{{ $po->ordered_at?->format('M d, Y') ?? '—' }}</div></div>
                <div class="info-item"><label>Received</label><div class="info-value">{{ $po->received_at?->format('M d, Y') ?? '—' }}</div></div>
            </div>

            <table class="data-table">
                <thead><tr><th>Product</th><th>Quantity</th><th>Unit cost</th><th>Line total</th></tr></thead>
                <tbody>
                    @foreach($po->items as $item)
                    <tr>
                        <td style="font-weight:600;color:#0f172a;">{{ $item->product->name ?? '—' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>₱{{ number_format($item->unit_cost, 2) }}</td>
                        <td>₱{{ number_format($item->lineTotal(), 2) }}</td>
                    </tr>
                    @endforeach
                    <tr><td colspan="3" style="text-align:right;font-weight:700;">Total</td><td style="font-weight:800;">₱{{ number_format($po->total_cost, 2) }}</td></tr>
                </tbody>
            </table>

            @if($po->notes)<p class="detail-sub" style="margin-top:12px;">Notes: {{ $po->notes }}</p>@endif

            <div class="po-actions" style="margin-top:20px;">
                @if($po->status === \App\Enums\PurchaseOrderStatus::Draft)
                    <form method="POST" action="{{ route('admin.purchase-orders.ordered', $po) }}">@csrf @method('PATCH')
                        <button type="submit" class="btn btn-outline">Mark as sent to supplier</button>
                    </form>
                @endif
                @if($po->isOpen())
                    <form method="POST" action="{{ route('admin.purchase-orders.receive', $po) }}" onsubmit="return confirm('Receive all items and add them to stock?')">@csrf @method('PATCH')
                        <button type="submit" class="btn btn-primary">Receive goods</button>
                    </form>
                    <form method="POST" action="{{ route('admin.purchase-orders.cancel', $po) }}" onsubmit="return confirm('Cancel this purchase order?')">@csrf @method('PATCH')
                        <button type="submit" class="btn-sm btn-reject">Cancel PO</button>
                    </form>
                @endif
                <button type="button" class="btn btn-outline" onclick="window.print()">Print</button>
            </div>
        </div>
    </div>
</body>
</html>
