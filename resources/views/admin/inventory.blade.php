<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock Ledger — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .form-grid { display: grid; grid-template-columns: 2fr 1fr 2fr auto; gap: 12px; align-items: end; }
        .btn-add { background: #4f46e5; color: #fff; border: none; border-radius: 10px; padding: 11px 20px; font-weight: 600; font-size: 14px; cursor: pointer; font-family: 'Inter', sans-serif; }
        .qty-in { color: #059669; font-weight: 700; }
        .qty-out { color: #dc2626; font-weight: 700; }
        .empty { text-align: center; padding: 40px; color: #64748b; }
        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'inventory'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Stock Ledger</h1>
                <p>Every stock change — purchases received, orders, cancellations and manual counts.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        @if($lowStock->isNotEmpty())
        <div class="alert alert-error">
            <strong>{{ $lowStock->count() }} product(s) at or below reorder level:</strong>
            {{ $lowStock->map(fn ($p) => $p->name.' ('.$p->stock.')')->implode(', ') }}
            — <a href="{{ route('admin.purchase-orders.index') }}" style="font-weight:700;">create a purchase order</a>
        </div>
        @endif

        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:16px;">Physical count adjustment</h3>
            <form method="POST" action="{{ route('admin.inventory.adjust') }}" onsubmit="return confirm('Apply this physical stock count? The adjustment will be permanently recorded in the ledger.')">
                @csrf
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Product</label>
                        <select name="product_id" class="form-input" required>
                            @foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} (system: {{ $product->stock }})</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Counted stock</label><input type="number" name="counted_stock" min="0" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Reason</label><input type="text" name="note" class="form-input" placeholder="e.g. Monthly count, 2 damaged" required maxlength="255"></div>
                    <button type="submit" class="btn-add">Adjust</button>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Movements</div>
                <form method="GET">
                    <select name="product_id" class="form-input" onchange="this.form.submit()">
                        <option value="">All products</option>
                        @foreach($products as $product)<option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>@endforeach
                    </select>
                </form>
            </div>
            @if($movements->count() > 0)
            <table class="data-table">
                <thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Change</th><th>Balance</th><th>By</th><th>Note</th></tr></thead>
                <tbody>
                    @foreach($movements as $m)
                    <tr>
                        <td style="color:#64748b;">{{ $m->created_at->format('M d, Y g:i A') }}</td>
                        <td style="font-weight:600;color:#0f172a;">{{ $m->product->name ?? '—' }}</td>
                        <td><x-status-badge :status="$m->type" /></td>
                        <td class="{{ $m->quantity >= 0 ? 'qty-in' : 'qty-out' }}">{{ sprintf('%+d', $m->quantity) }}</td>
                        <td>{{ $m->balance_after }}</td>
                        <td>{{ $m->user->name ?? 'System' }}</td>
                        <td style="color:#64748b;">{{ $m->note }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:16px;">{{ $movements->links() }}</div>
            @else
            <p class="empty">No stock movements yet.</p>
            @endif
        </div>
    </div>
</body>
</html>
