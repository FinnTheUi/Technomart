<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product ? 'Edit Product' : 'Add Product' }} — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .panel { max-width: 600px; }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'products'])

    <div class="main">
        <a href="{{ route('admin.products') }}" class="back-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Products
        </a>

        <div class="page-header">
            <div>
                <h1>{{ $product ? 'Edit Product' : 'Add New Product' }}</h1>
            </div>
        </div>

        <div class="panel">
            <form method="POST" action="{{ $product ? route('admin.products.update', $product) : route('admin.products.store') }}">
                @csrf
                @if($product) @method('PUT') @endif

                <div class="form-group">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $product->name ?? '') }}" placeholder="e.g. Fresh Vegetables Bundle" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" placeholder="Brief description of the product...">{{ old('description', $product->description ?? '') }}</textarea>
                    @error('description') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" name="price" class="form-input" step="0.01" min="0" value="{{ old('price', $product->price ?? '') }}" placeholder="0.00" required>
                        @error('price') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock" class="form-input" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required>
                        @if($product)<div style="font-size:12px;color:#64748b;margin-top:4px;">Changing this records a stock adjustment in the ledger.</div>@endif
                        @error('stock') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Reorder level</label>
                    <input type="number" name="reorder_level" class="form-input" min="0" value="{{ old('reorder_level', $product->reorder_level ?? 10) }}">
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Admins are alerted when stock falls to this level.</div>
                    @error('reorder_level') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-input" value="{{ old('category', $product->category ?? '') }}" placeholder="e.g. Food, Merchandise, Supplies">
                    @error('category') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn-submit">{{ $product ? 'Update Product' : 'Add Product' }}</button>
            </form>
        </div>
    </div>
</body>
</html>
