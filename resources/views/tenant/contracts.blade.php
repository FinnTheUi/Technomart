<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Contracts — Technomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body>
    @include('partials.tenant-sidebar', ['active' => 'contracts'])
    
    <div class="main">
        <div class="page-header">
            <div>
                <h1>📋 My Contracts</h1>
            </div>
        </div>
        <div class="panel">
            @if($contracts->count() > 0)
            <table class="data-table">
                <thead><tr><th>#</th><th>Stall</th><th>Start</th><th>End</th><th>Status</th><th>Signed</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($contracts as $c)
                <tr>
                    <td style="font-weight:600;">{{ $c->id }}</td>
                    <td style="font-weight:600;color:#0f172a;">{{ $c->stall->name ?? 'N/A' }}</td>
                    <td>{{ $c->start_date->format('M d, Y') }}</td>
                    <td>{{ $c->end_date->format('M d, Y') }}</td>
                    <td><x-status-badge :status="$c->status" /></td>
                    <td><span class="badge {{ $c->isSigned()?'badge-green':'badge-yellow' }}">{{ $c->isSigned()?'Signed':'Pending' }}</span></td>
                    <td><a href="{{ route('tenant.contracts.show', $c) }}" class="btn-sm btn-view">View</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <p style="text-align:center;color:#64748b;padding:40px;font-size:14px;">No contracts found.</p>
            @endif
        </div>
    </div>
</body>
</html>
