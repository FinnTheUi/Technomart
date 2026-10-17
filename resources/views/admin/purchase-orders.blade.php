<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Orders — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 16px; margin-bottom: 16px; }
        .items-table td { padding: 6px 8px; }
        .btn-add { background: #4f46e5; color: #fff; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; font-size: 14px; cursor: pointer; font-family: 'Inter', sans-serif; }
        .btn-link { background: none; border: none; color: #4f46e5; font-weight: 600; cursor: pointer; font-size: 13px; padding: 8px 0; }
        .empty { text-align: center; padding: 40px; color: #64748b; }
        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'purchase-orders'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Purchase Orders</h1>
                <p>Restock from suppliers. Stock is added when the goods are received.</p>
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

        @if($lowStock->isNotEmpty())
        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:12px;">Needs restocking</h3>
            <table class="data-table">
                <thead><tr><th>Product</th><th>Stock</th><th>Reorder at</th><th>Usual supplier</th></tr></thead>
                <tbody>
                    @foreach($lowStock as $product)
                    <tr>
                        <td style="font-weight:600;color:#0f172a;">{{ $product->name }}</td>
                        <td><span class="badge badge-red">{{ $product->stock }}</span></td>
                        <td>{{ $product->reorder_level }}</td>
                        <td>{{ $product->supplier->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:16px;">New purchase order</h3>
            @if($suppliers->isEmpty())
                <p class="detail-sub">Add a <a href="{{ route('admin.suppliers.index') }}" style="color:#4f46e5;">supplier</a> first.</p>
            @else
            <form method="POST" action="{{ route('admin.purchase-orders.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Supplier *</label>
                        <select name="supplier_id" class="form-input" required>
                            @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Notes</label><input type="text" name="notes" class="form-input" maxlength="1000"></div>
                </div>

                <table class="data-table items-table">
                    <thead><tr><th>Product</th><th>Quantity</th><th>Unit cost (₱)</th></tr></thead>
                    <tbody id="po-items">
                        @php $preset = $lowStock->values(); @endphp
                        @for($i = 0; $i < max(3, $preset->count()); $i++)
                        <tr>
                            <td>
                                <select name="items[{{ $i }}][product_id]" class="form-input">
                                    <option value="">—</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected(($preset[$i]->id ?? null) === $product->id)>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" name="items[{{ $i }}][quantity]" class="form-input" min="1" value="{{ isset($preset[$i]) ? max(1, $preset[$i]->reorder_level * 2 - $preset[$i]->stock) : '' }}"></td>
                            <td><input type="number" name="items[{{ $i }}][unit_cost]" class="form-input" min="0" step="0.01"></td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
                <button type="button" class="btn-link" onclick="addPoRow()">+ Add line</button>
                <div><button type="submit" class="btn-add">Create purchase order</button></div>
            </form>
            @endif
        </div>

        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:16px;">All purchase orders</h3>
            @if($purchaseOrders->count() > 0)
            <table class="data-table">
                <thead><tr><th>PO #</th><th>Supplier</th><th>Lines</th><th>Total cost</th><th>Status</th><th>Created</th><th></th></tr></thead>
                <tbody>
                    @foreach($purchaseOrders as $po)
                    <tr>
                        <td style="font-weight:700;">{{ $po->po_number }}</td>
                        <td>{{ $po->supplier->name ?? '—' }}</td>
                        <td>{{ $po->items_count }}</td>
                        <td>₱{{ number_format($po->total_cost, 2) }}</td>
                        <td><x-status-badge :status="$po->status" /></td>
                        <td style="color:#64748b;">{{ $po->created_at->format('M d, Y') }}</td>
                        <td><a href="{{ route('admin.purchase-orders.show', $po) }}" class="btn-sm btn-view">Open</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="empty">No purchase orders yet.</p>
            @endif
        </div>
    </div>

    <script>
        function addPoRow() {
            const body = document.getElementById('po-items');
            const index = body.rows.length;
            const row = body.rows[0].cloneNode(true);
            row.querySelectorAll('select, input').forEach(el => {
                el.name = el.name.replace(/items\[\d+\]/, `items[${index}]`);
                el.value = '';
            });
            body.appendChild(row);
        }
    </script>
</body>
</html>
