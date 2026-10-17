<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Locations — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
        .form-group.full{grid-column:span 2}
        .btn-add{background:#4f46e5;color:#fff;border:none;border-radius:10px;padding:12px 24px;font-weight:600;font-size:14px;cursor:pointer;font-family:'Inter',sans-serif;transition:.2s}
        .btn-add:hover{background:#4338ca}
        .count-badge{background:#eef2ff;color:#4f46e5;font-size:12px;font-weight:700;padding:4px 10px;border-radius:6px;border:1px solid #c7d2fe}
        .empty-state{text-align:center;padding:60px 20px;color:#64748b}
    </style>
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'locations'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Locations / Branches</h1>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <!-- Add Location Form -->
        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:20px;">Add New Location</h3>
            <form method="POST" action="{{ route('admin.locations.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Name *</label><input type="text" name="name" class="form-input" required placeholder="e.g. Main Market Branch"></div>
                    <div class="form-group"><label class="form-label">Address</label><input type="text" name="address" class="form-input" placeholder="e.g. 123 Market St, City"></div>
                    <div class="form-group full"><label class="form-label">Description</label><textarea name="description" class="form-textarea" rows="2" placeholder="Brief description..."></textarea></div>
                </div>
                <button type="submit" class="btn-add">➕ Add Location</button>
            </form>
        </div>

        <!-- Locations Table -->
        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:20px;">All Locations</h3>
            <table class="data-table">
                <thead><tr><th>#</th><th>Name</th><th>Address</th><th>Stalls</th><th>Created</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($locations as $loc)
                    <tr>
                        <td style="font-weight:600;">{{ $loc->id }}</td>
                        <td style="font-weight:600;color:#0f172a;">{{ $loc->name }}</td>
                        <td>{{ $loc->address ?? '—' }}</td>
                        <td><span class="count-badge">{{ $loc->stalls_count }}</span></td>
                        <td>{{ $loc->created_at->format('M d, Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.locations.delete', $loc) }}" onsubmit="return confirm('Delete this location?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-sm btn-reject">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="empty-state">No locations created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
