<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rental Applications — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .detail-sub{font-size:12px;color:#64748b;margin-top:2px}
        .action-cell{display:flex;gap:6px;align-items:center}
        .empty{text-align:center;padding:40px;color:#64748b;font-size:14px}
    </style>
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'rental-applications'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Rental Applications</h1>
            </div>
        </div>
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="panel">
            @if($applications->count() > 0)
            <table class="data-table">
                <thead><tr><th>Applicant</th><th>Stall</th><th>Duration</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($applications as $app)
                    <tr>
                        <td>
                            <div style="font-weight:600;color:#0f172a;">{{ $app->name }}</div>
                            <div class="detail-sub">{{ $app->email }}</div>
                            <div class="detail-sub">{{ $app->phone }}</div>
                        </td>
                        <td style="font-weight:600;color:#334155;">{{ $app->stall->name ?? 'N/A' }}</td>
                        <td>{{ $app->rental_duration }} mo.</td>
                        <td style="font-weight:600;">₱{{ number_format(($app->stall->price ?? 0) * $app->rental_duration, 2) }}</td>
                        <td><span class="badge {{ $app->payment_method === 'gcash' ? 'badge-blue' : 'badge-yellow' }}">{{ $app->payment_method === 'gcash' ? 'GCash' : 'In Person' }}</span></td>
                        <td>
                            @if($app->status === 'pending_approval')
                                <span class="badge badge-yellow">Pending</span>
                            @elseif($app->status === 'approved')
                                <span class="badge badge-green">Approved</span>
                            @else
                                <span class="badge badge-red">Rejected</span>
                            @endif
                        </td>
                        <td style="color:#64748b;font-size:13px;">{{ $app->created_at->format('M d, Y') }}</td>
                        <td>
                            @if($app->status === 'pending_approval')
                                <div class="action-cell">
                                    <form method="POST" action="{{ route('manager.rental-applications.approve', $app) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-sm btn-approve" onclick="return confirm('Approve this application? This will create a tenant account and mark the stall as occupied.')">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('manager.rental-applications.reject', $app) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-sm btn-reject" onclick="return confirm('Are you sure you want to reject this application?')">Reject</button>
                                    </form>
                                </div>
                            @else
                                <span style="color:#94a3b8;font-size:13px;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
                <p class="empty">No rental applications found.</p>
            @endif
        </div>
    </div>
</body>
</html>
