<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Products — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .btn-add{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;color:#fff;background:#4f46e5;border:none;cursor:pointer;transition:.2s;font-family:'Inter',sans-serif}
        .btn-add:hover{background:#4338ca}
        .btn-add svg{width:18px;height:18px}
        .empty-state{text-align:center;padding:60px 20px;color:#64748b}
        .empty-state svg{width:48px;height:48px;opacity:.3;margin-bottom:16px}
        .empty-state p{font-size:15px}
    </style>
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'products'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Products</h1>
            </div>
            <a href="{{ route('admin.products.create') }}" class="btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Product
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="panel">
            @if($products->count() > 0)
            <table class="data-table">
                <thead>
                    <tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>
                            <div style="font-weight:600;color:#0f172a;">{{ $product->name }}</div>
                            @if($product->description)
                            <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ Str::limit($product->description, 50) }}</div>
                            @endif
                        </td>
                        <td>{{ $product->category ?? '—' }}</td>
                        <td style="font-weight:600;">₱{{ number_format($product->price, 2) }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>
                            <span class="badge {{ $product->stock > 0 ? 'badge-green' : 'badge-red' }}">
                                {{ $product->stock > 0 ? 'In Stock' : 'Out of Stock' }}
                            </span>
                        </td>
                        <td>
                            <div class="action-cell">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn-sm btn-view" style="color:#0284c7;background:#f0f9ff;border-color:#bae6fd;">Edit</a>
                                <form method="POST" action="{{ route('admin.products.delete', $product) }}" onsubmit="return confirm('Delete this product?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-sm btn-reject">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <p>No products yet. Click <strong>"Add Product"</strong> to get started.</p>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
