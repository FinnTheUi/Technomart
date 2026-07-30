<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stalls — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .btn-add{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;color:#fff;background:#4f46e5;border:none;cursor:pointer;transition:.2s;font-family:'Inter',sans-serif;text-decoration:none}
        .btn-add:hover{background:#4338ca}
        .btn-add svg{width:18px;height:18px}
        select.status-select{background:#fff;border:1px solid #cbd5e1;color:#334155;padding:6px 10px;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;cursor:pointer;outline:none}
        select.status-select:focus{border-color:#4f46e5}
    </style>
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'stalls'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Stall Management</h1>
            </div>
            <a href="{{ route('manager.stalls.create') }}" class="btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Stall
            </a>
        </div>
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="panel">
            @if($stalls->count() > 0)
            <table class="data-table">
                <thead><tr><th>Name</th><th>Location</th><th>Size</th><th>Price/mo</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($stalls as $stall)
                    <tr>
                        <td style="font-weight:600;color:#0f172a;">{{ $stall->name }}</td>
                        <td>{{ $stall->location ?? '—' }}</td>
                        <td>{{ $stall->size ?? '—' }}</td>
                        <td style="font-weight:600;">₱{{ number_format($stall->price, 2) }}</td>
                        <td><span class="badge {{ $stall->status==='available'?'badge-green':($stall->status==='occupied'?'badge-blue':'badge-yellow') }}">{{ ucfirst($stall->status) }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('manager.stalls.updateStatus', $stall) }}" style="display:flex;gap:8px;align-items:center;">
                                @csrf @method('PATCH')
                                <select name="status" class="status-select">
                                    <option value="available" {{ $stall->status==='available'?'selected':'' }}>Available</option>
                                    <option value="occupied" {{ $stall->status==='occupied'?'selected':'' }}>Occupied</option>
                                    <option value="maintenance" {{ $stall->status==='maintenance'?'selected':'' }}>Maintenance</option>
                                </select>
                                <button type="submit" class="btn-sm btn-view">Update</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="text-align:center;padding:40px;color:#64748b;">No stalls yet. Click "Add Stall" to create one.</p>
            @endif
        </div>
    </div>
</body>
</html>
