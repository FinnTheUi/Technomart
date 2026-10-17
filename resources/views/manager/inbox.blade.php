<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Inbox — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .request-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;margin-bottom:16px;transition:border-color .2s;box-shadow:0 1px 3px rgba(0,0,0,0.02)}
        .request-card:hover{border-color:#cbd5e1;box-shadow:0 4px 12px rgba(0,0,0,0.05)}
        .request-card.priority-urgent{border-left:4px solid #ef4444}
        .request-card.priority-high{border-left:4px solid #f59e0b}
        .request-card.priority-medium{border-left:4px solid #0ea5e9}
        .request-card.priority-low{border-left:4px solid #10b981}
        .request-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;gap:12px}
        .request-subject{font-size:16px;font-weight:700;color:#0f172a}
        .request-meta{font-size:13px;color:#64748b;margin-top:4px}
        .request-desc{font-size:14px;color:#334155;line-height:1.6;margin-bottom:16px}
        .request-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
        select.status-select{background:#fff;border:1px solid #cbd5e1;color:#0f172a;padding:6px 10px;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;cursor:pointer;outline:none}
        select.status-select:focus{border-color:#4f46e5}
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'inbox'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Maintenance Inbox</h1>
            </div>
        </div>
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($requests->count() > 0)
            <div id="request-records">
            @foreach($requests as $req)
            <div class="request-card priority-{{ $req->priority }}">
                <div class="request-header">
                    <div>
                        <div class="request-subject">{{ $req->subject }}</div>
                        <div class="request-meta">
                            <strong>{{ $req->tenant->name ?? 'Unknown' }}</strong> · {{ $req->stall->name ?? '' }} · {{ $req->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <span class="badge {{ $req->priority==='urgent'?'badge-red':($req->priority==='high'?'badge-yellow':($req->priority==='medium'?'badge-blue':'badge-green')) }}">{{ ucfirst($req->priority) }}</span>
                        <x-status-badge :status="$req->status" />
                    </div>
                </div>
                <div class="request-desc">{{ $req->description }}</div>
                <div class="request-footer">
                    <form method="POST" action="{{ route('manager.maintenance.updateStatus', $req) }}" style="display:flex;gap:8px;align-items:center;">
                        @csrf @method('PATCH')
                        <select name="status" class="status-select">
                            <option value="pending" {{ $req->status->value==='pending'?'selected':'' }}>Pending</option>
                            <option value="in_progress" {{ $req->status->value==='in_progress'?'selected':'' }}>In Progress</option>
                            <option value="resolved" {{ $req->status->value==='resolved'?'selected':'' }}>Resolved</option>
                            <option value="closed" {{ $req->status->value==='closed'?'selected':'' }}>Closed</option>
                        </select>
                        <button type="submit" class="btn-sm btn-view">Update</button>
                    </form>
                </div>
            </div>
            @endforeach
            {{ $requests->links('partials.pagination') }}
            </div>
        @else
            <div style="text-align:center;padding:80px 20px;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48" style="color:#94a3b8;margin-bottom:16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                <p style="color:#64748b;font-size:15px;">No maintenance requests yet. You'll see them here when tenants submit issues.</p>
            </div>
        @endif
    </div>
</body>
</html>
