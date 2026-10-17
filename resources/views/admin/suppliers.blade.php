<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suppliers — Technomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px; }
        .btn-add { background: #4f46e5; color: #fff; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; font-size: 14px; cursor: pointer; font-family: 'Inter', sans-serif; }
        .edit-row form { display: grid; grid-template-columns: repeat(5, 1fr) auto; gap: 6px; margin-top: 8px; }
        .count-badge { background: #eef2ff; color: #4f46e5; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; }
        .empty { text-align: center; padding: 40px; color: #64748b; }
        @media (max-width: 900px) { .form-grid, .edit-row form { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'suppliers'])

    <div class="main">
        <div class="page-header">
            <div><h1>Suppliers</h1></div>
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
            <h3 class="panel-title" style="margin-bottom:16px;">Add supplier</h3>
            <form method="POST" action="{{ route('admin.suppliers.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Name *</label><input type="text" name="name" class="form-input" required value="{{ old('name') }}"></div>
                    <div class="form-group"><label class="form-label">Contact person</label><input type="text" name="contact_person" class="form-input" value="{{ old('contact_person') }}"></div>
                    <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-input" value="{{ old('phone') }}"></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="{{ old('email') }}"></div>
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Address</label><input type="text" name="address" class="form-input" value="{{ old('address') }}"></div>
                </div>
                <button type="submit" class="btn-add">Add supplier</button>
            </form>
        </div>

        <div class="panel">
            @if($suppliers->count() > 0)
            <table class="data-table">
                <thead><tr><th>Supplier</th><th>Contact</th><th>Products</th><th>Purchase orders</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($suppliers as $supplier)
                    <tr>
                        <td style="font-weight:600;color:#0f172a;">{{ $supplier->name }}<div class="detail-sub">{{ $supplier->address }}</div></td>
                        <td>{{ $supplier->contact_person ?? '—' }}<div class="detail-sub">{{ $supplier->phone }} {{ $supplier->email }}</div></td>
                        <td><span class="count-badge">{{ $supplier->products_count }}</span></td>
                        <td><span class="count-badge">{{ $supplier->purchase_orders_count }}</span></td>
                        <td class="edit-row">
                            <details>
                                <summary class="btn-sm btn-view" style="list-style:none;cursor:pointer;display:inline-block;">Edit</summary>
                                <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" class="form-input" value="{{ $supplier->name }}" required>
                                    <input type="text" name="contact_person" class="form-input" value="{{ $supplier->contact_person }}" placeholder="Contact">
                                    <input type="text" name="phone" class="form-input" value="{{ $supplier->phone }}" placeholder="Phone">
                                    <input type="email" name="email" class="form-input" value="{{ $supplier->email }}" placeholder="Email">
                                    <input type="text" name="address" class="form-input" value="{{ $supplier->address }}" placeholder="Address">
                                    <button type="submit" class="btn-sm btn-approve">Save</button>
                                </form>
                            </details>
                            @if($supplier->purchase_orders_count === 0)
                            <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete {{ $supplier->name }}?')" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-sm btn-reject">Delete</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="empty">No suppliers yet.</p>
            @endif
        </div>
    </div>
</body>
</html>
