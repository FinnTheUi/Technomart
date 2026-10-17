<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Log — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .filter-bar{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px}
        .filter-bar select, .filter-bar input{background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:10px 14px;font-size:13px;color:#0f172a;font-family:'Inter',sans-serif;outline:none}
        .filter-bar select:focus, .filter-bar input:focus{border-color:#4f46e5}
        .filter-btn{background:#eef2ff;border:1px solid #c7d2fe;color:#4f46e5;border-radius:10px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:.2s}
        .filter-btn:hover{background:#e0e7ff}
        .filter-clear{background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b;border-radius:10px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:.2s;text-decoration:none;display:inline-flex;align-items:center}
        .filter-clear:hover{background:#e2e8f0}
        .action-badge{display:inline-block;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600;background:#eef2ff;color:#4f46e5;border:1px solid #c7d2fe}
        .pagination-wrap{display:flex;justify-content:center;gap:4px;margin-top:20px}
        .pagination-wrap a, .pagination-wrap span{padding:8px 14px;border-radius:8px;font-size:13px;color:#64748b;border:1px solid #e2e8f0;text-decoration:none}
        .pagination-wrap a:hover{background:#f8fafc}
        .pagination-wrap .active{background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;font-weight:600}
        .empty-state{text-align:center;color:#64748b;padding:40px}
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'audit-log'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>System Audit Log</h1>
            </div>
        </div>

        <div class="panel">
            <form method="GET" action="{{ route('admin.audit-log') }}">
                <div class="filter-bar">
                    <select name="action">
                        <option value="">All Actions</option>
                        @foreach($actions as $a)
                        <option value="{{ $a }}" {{ request('action')===$a?'selected':'' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                    <select name="user_id">
                        <option value="">All Users</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id')==$u->id?'selected':'' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="From">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="To">
                    <button type="submit" class="filter-btn">Filter</button>
                    <a href="{{ route('admin.audit-log') }}" class="filter-clear">Clear</a>
                    <a href="{{ route('admin.audit-log.export', request()->only(['action', 'user_id', 'date_from', 'date_to'])) }}" class="filter-clear">Export CSV</a>
                </div>
            </form>

            <table class="data-table">
                <thead>
                    <tr><th>Timestamp</th><th>User</th><th>Action</th><th>Description</th><th>Entity</th></tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;font-size:12px;color:#64748b;">{{ $log->created_at->format('M d, Y H:i') }}</td>
                        <td style="font-weight:600;color:#0f172a;">{{ $log->user->name ?? 'System' }}</td>
                        <td><span class="action-badge">{{ $log->action }}</span></td>
                        <td style="max-width:300px;">{{ Str::limit($log->description, 80) }}</td>
                        <td style="font-size:12px;color:#64748b;">{{ $log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="empty-state">No activity logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="pagination-wrap">{!! $logs->withQueryString()->links() !!}</div>
        </div>
    </div>
</body>
</html>
