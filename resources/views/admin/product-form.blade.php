<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product ? 'Edit Product' : 'Add Product' }} — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .panel { max-width: 600px; }
    </style>
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
                        @error('stock') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
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
