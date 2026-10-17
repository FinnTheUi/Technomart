<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stalls — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        select.status-select{background:#fff;border:1px solid #cbd5e1;color:#334155;padding:6px 10px;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;cursor:pointer;outline:none}
        select.status-select:focus{border-color:#4f46e5}
        .floor-title{font-size:15px;font-weight:800;color:#0f172a;margin-bottom:14px;display:flex;align-items:center;gap:10px}
        .floor-count{font-size:12px;font-weight:600;color:#64748b}
        .num-chip{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:28px;border-radius:8px;background:#eef2ff;color:#4338ca;font-weight:800;font-size:13px}
        .tenant{font-weight:600;color:#0f172a}
        .muted{color:#94a3b8;font-size:13px}
        .stall-search{display:flex;gap:10px;align-items:center;margin-bottom:18px}
        .stall-search input{width:min(100%,420px);height:42px;padding:0 14px;border:1px solid #cbd5e1;border-radius:10px;font:inherit;outline:none}
        .stall-search input:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.12)}
        .stall-search button{height:42px;padding:0 18px;border:0;border-radius:10px;background:#4f46e5;color:#fff;font:inherit;font-weight:700;cursor:pointer}
        .stall-search .clear-search{color:#475569;font-size:14px;font-weight:600;text-decoration:none}
        @media(max-width:600px){.stall-search{align-items:stretch;flex-wrap:wrap}.stall-search input{width:100%}}
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'stalls'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Stall Management</h1>
                <p>Stalls are created automatically from the floor layout and named by their number. Set prices, details, maintenance and tenants here.</p>
            </div>
            <a href="{{ route('manager.floor-plan') }}" class="btn-sm btn-view">View floor plan</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('manager.stalls') }}" class="stall-search" role="search">
            <input type="search" name="q" value="{{ $search }}" placeholder="Search by stall name or number" maxlength="50" aria-label="Search stalls by name or number">
            <button type="submit">Search</button>
            @if($search !== '')
                <a href="{{ route('manager.stalls') }}" class="clear-search">Clear</a>
            @endif
        </form>

        @forelse($stalls as $floor => $floorStalls)
        <div class="panel">
            <div class="floor-title">
                {{ config("stalls.floors.{$floor}.label", "Floor {$floor}") }}
                <span class="floor-count">{{ $floorStalls->count() }} shown · {{ $floorStalls->where('status', \App\Enums\StallStatus::Available)->count() }} available</span>
            </div>
            <table class="data-table">
                <thead><tr><th>#</th><th>Name</th><th>Tenant</th><th>Price/mo</th><th>Size</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($floorStalls as $stall)
                    @php $leased = $stall->activeContract !== null; @endphp
                    <tr>
                        <td><span class="num-chip">{{ $stall->stall_number }}</span></td>
                        <td style="font-weight:600;color:#0f172a;">{{ $stall->name }}</td>
                        <td>
                            @if($leased)
                                <div class="tenant">{{ $stall->activeContract->tenant->name ?? '—' }}</div>
                                <div class="muted">until {{ $stall->activeContract->end_date->format('M d, Y') }}</div>
                            @else
                                <span class="muted">Vacant</span>
                            @endif
                        </td>
                        <td style="font-weight:600;">₱{{ number_format($stall->price, 2) }}</td>
                        <td>{{ $stall->size ?? '—' }}</td>
                        <td><x-status-badge :status="$stall->status" /></td>
                        <td>
                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                <a href="{{ route('manager.stalls.edit', $stall) }}" class="btn-sm btn-view">{{ $leased ? 'Edit' : 'Edit / Assign tenant' }}</a>
                                <form method="POST" action="{{ route('manager.stalls.updateStatus', $stall) }}" style="display:flex;gap:6px;align-items:center;">
                                    @csrf @method('PATCH')
                                    <select name="status" class="status-select">
                                        @php $inService = $leased ? 'occupied' : 'available'; @endphp
                                        <option value="{{ $inService }}" @selected($stall->status->value !== 'maintenance')>{{ $leased ? 'Occupied' : 'Available' }}</option>
                                        <option value="maintenance" @selected($stall->status->value === 'maintenance')>Maintenance</option>
                                    </select>
                                    <button type="submit" class="btn-sm btn-view">Set</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @empty
        <div class="panel">
            <p style="text-align:center;padding:40px;color:#64748b;">{{ $search !== '' ? 'No stalls match your search.' : "No stalls are assigned to you. The floor layout's stalls belong to the mall's manager." }}</p>
        </div>
        @endforelse
        {{ $stallPage->links('partials.pagination') }}
    </div>
</body>
</html>
