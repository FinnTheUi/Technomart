<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Products — Technomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .product-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; transition: transform .3s, border-color .3s; }
        .product-card:hover { transform: translateY(-3px); border-color: #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .product-icon { width: 56px; height: 56px; border-radius: 14px; background: #f0fdf4; border: 1px solid #d1fae5; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .product-icon svg { width: 28px; height: 28px; color: #10b981; }
        .product-name { font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .product-desc { font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 16px; min-height: 40px; }
        .product-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .product-price { font-size: 22px; font-weight: 800; color: #059669; }
        .product-stock { font-size: 12px; color: #64748b; font-weight: 500; }
        .order-form { display: flex; gap: 10px; align-items: center; }
        .qty-input { width: 70px; padding: 8px 12px; font-size: 14px; font-family: 'Inter', sans-serif; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; outline: none; text-align: center; font-weight: 600; }
        .qty-input:focus { border-color: #4f46e5; }
        .btn-order { flex: 1; padding: 10px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; color: #fff; background: #4f46e5; border: none; cursor: pointer; font-family: 'Inter', sans-serif; transition: 0.2s; }
        .btn-order:hover { background: #4338ca; }
        .badge-cat { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; margin-bottom: 12px; }
    </style>
</head>
<body>
    @include('partials.tenant-sidebar', ['active' => 'products'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Order Products</h1>
            </div>
        </div>
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if($products->count() > 0)
        <div class="product-grid">
            @foreach($products as $product)
            <div class="product-card">
                <div class="product-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
                @if($product->category)<span class="badge-cat">{{ $product->category }}</span>@endif
                <div class="product-name">{{ $product->name }}</div>
                <div class="product-desc">{{ $product->description ?? 'No description available.' }}</div>
                <div class="product-meta">
                    <div class="product-price">₱{{ number_format($product->price, 2) }}</div>
                    <div class="product-stock">{{ $product->stock }} in stock</div>
                </div>
                <form method="POST" action="{{ route('tenant.orders.store') }}" class="order-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="number" name="quantity" class="qty-input" value="1" min="1" max="{{ $product->stock }}">
                    <button type="submit" class="btn-order">Order Now</button>
                </form>
            </div>
            @endforeach
        </div>
        @else
        <div style="text-align:center;padding:80px 20px;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48" style="color:#94a3b8;margin-bottom:16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <p style="color:#64748b;font-size:15px;">No products available at the moment. Check back later!</p>
        </div>
        @endif
    </div>
</body>
</html>
