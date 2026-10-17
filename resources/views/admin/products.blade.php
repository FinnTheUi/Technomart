<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Stock Inventory — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .btn-add{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;color:#fff;background:#4f46e5;border:none;cursor:pointer;transition:.2s;font-family:'Inter',sans-serif}
        .btn-add:hover{background:#4338ca}
        .btn-add svg{width:18px;height:18px}
        
        /* Excel Spreadsheet Styles */
        .spreadsheet-container {
            width: 100%;
            overflow-x: auto;
            background: #fff;
            border: 1px solid #cbd5e1;
        }
        .spreadsheet-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            font-family: 'Calibri', 'Inter', sans-serif;
            text-align: center;
            white-space: nowrap;
        }
        .spreadsheet-table th, .spreadsheet-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
        }
        .spreadsheet-table thead {
            background-color: #f1f5f9;
        }
        .header-title-row th {
            background-color: #1e3a8a;
            color: white;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 12px;
        }
        .header-col-row th {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: 700;
            font-size: 12px;
        }
        .spreadsheet-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status-reorder { color: #dc2626; font-weight: 600; font-style: italic; }
        .status-available { color: #166534; font-weight: 600; }
        .remarks-text { font-size: 11px; color: #dc2626; font-weight: 600; }
        .date-cell { font-weight: 700; vertical-align: top; padding-top: 12px !important; }
        .total-val-cell { vertical-align: top; padding-top: 12px !important; }
        .product-name { font-weight: 600; color: #0f172a; }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'products'])

    <div class="main">
        <div class="page-header" style="margin-bottom: 20px;">
            <div>
                <h1>Stock Inventory</h1>
                <p>Daily historical inventory and sales tracking</p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="{{ route('admin.products.create') }}" class="btn-add">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    New Product
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom: 20px;">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error" style="margin-bottom: 20px;">{{ session('error') }}</div>
        @endif

        <div class="panel" style="margin-bottom: 24px;">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Catalog</div>
                    <div class="panel-subtitle">Stock changes are recorded in the <a href="{{ route('admin.inventory.index') }}" style="color:#4f46e5;">stock ledger</a>. Restock through <a href="{{ route('admin.purchase-orders.index') }}" style="color:#4f46e5;">purchase orders</a>.</div>
                </div>
            </div>
            <table class="data-table">
                <thead><tr><th>Product</th><th>Supplier</th><th>Price</th><th>Stock</th><th>Reorder at</th><th>Visible</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td style="font-weight:600;color:#0f172a;">{{ $product->name }}<div style="font-size:12px;color:#64748b;font-weight:400;">{{ $product->category }}</div></td>
                        <td>{{ $product->supplier->name ?? '—' }}</td>
                        <td>₱{{ number_format($product->price, 2) }}</td>
                        <td>
                            <span class="badge {{ $product->isLowStock() ? 'badge-red' : 'badge-green' }}">{{ $product->stock }}</span>
                            @if($product->isLowStock())<div style="font-size:11px;color:#dc2626;font-weight:600;">Reorder</div>@endif
                        </td>
                        <td>{{ $product->reorder_level }}</td>
                        <td>{{ $product->is_active ? 'Yes' : 'Hidden' }}</td>
                        <td style="display:flex;gap:6px;">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn-sm btn-view">Edit</a>
                            <form method="POST" action="{{ route('admin.products.delete', $product) }}" onsubmit="return confirm('Delete {{ $product->name }}? Products with orders are hidden instead.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-sm btn-reject">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:#64748b;">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="spreadsheet-container">
            <table class="spreadsheet-table">
                <thead>
                    <tr class="header-title-row">
                        <th colspan="12">PEPSI-COLA PRODUCTS<br><span style="font-size: 14px; font-weight: normal;">STOCK INVENTORY</span></th>
                    </tr>
                    <tr class="header-col-row">
                        <th>DATE</th>
                        <th class="text-left">PRODUCTS</th>
                        <th>UNIT PRICE</th>
                        <th>QTY IN HAND</th>
                        <th>QTY SOLD</th>
                        <th>INVENTORY VALUE</th>
                        <th>TOTAL INVENTORY VALUE</th>
                        <th>SALES VALUE</th>
                        <th>TOTAL OF SALES VALUE</th>
                        <th>AVAILABLE STOCK</th>
                        <th>STATUS</th>
                        <th>REMARKS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventoryReport as $dateKey => $data)
                        @php $productCount = count($data['records']); @endphp
                        @foreach($data['records'] as $index => $record)
                            <tr>
                                @if($index === 0)
                                    <td class="date-cell" rowspan="{{ $productCount }}">{{ $dateKey }}</td>
                                @endif
                                
                                <td class="text-left product-name">{{ $record['product']->name }}</td>
                                <td>{{ number_format($record['product']->price, 2) }}</td>
                                <td>{{ $record['qty_in_hand'] }}</td>
                                <td>{{ $record['qty_sold'] }}</td>
                                <td>{{ number_format($record['inventory_value'], 2) }}</td>
                                
                                @if($index === 0)
                                    <td class="total-val-cell" rowspan="{{ $productCount }}">{{ number_format($data['total_inventory_value'], 2) }}</td>
                                @endif
                                
                                <td>{{ number_format($record['sales_value'], 2) }}</td>
                                
                                @if($index === 0)
                                    <td class="total-val-cell" rowspan="{{ $productCount }}">{{ number_format($data['total_sales_value'], 2) }}</td>
                                @endif
                                
                                <td>{{ $record['available_stock'] }}</td>
                                <td class="{{ $record['status'] === 'Reorder' ? 'status-reorder' : 'status-available' }}">
                                    {{ $record['status'] }}
                                </td>
                                <td>
                                    {{-- Leave remarks blank unless Reorder is triggered as an example --}}
                                    @if($record['status'] === 'Reorder' && $record['qty_sold'] > 0)
                                        <span class="remarks-text">NEEDS RESTOCK</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
