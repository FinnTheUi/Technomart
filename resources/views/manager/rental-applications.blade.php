<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rental Applications — Technomart</title>
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
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
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
                        <td>
                            @switch($app->payment_method)
                                @case('gcash')
                                    <span class="badge badge-blue">GCash</span>
                                    <div class="detail-sub">Ref: <strong>{{ $app->gcash_reference }}</strong></div>
                                    @break
                                @case('online')
                                    <span class="badge badge-indigo">Paid online</span>
                                    <div class="detail-sub">{{ $app->gcash_reference }}</div>
                                    @break
                                @case('in_person')
                                    <span class="badge badge-yellow">In person</span>
                                    @break
                                @default
                                    <span class="badge badge-red">Not paid yet</span>
                            @endswitch
                        </td>
                        <td><x-status-badge :status="$app->status" /></td>
                        <td style="color:#64748b;font-size:13px;">{{ $app->created_at->format('M d, Y') }}</td>
                        <td>
                            @if($app->isPending() && $app->payment_method)
                                <div class="action-cell">
                                    <form method="POST" action="{{ route('manager.rental-applications.approve', $app) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-sm btn-approve" onclick="return confirm('Approve this application? Approving confirms you have checked the first month\'s payment (GCash reference or cash received). A tenant account, lease and rent schedule will be created.')">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('manager.rental-applications.reject', $app) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-sm btn-reject" onclick="return confirm('Are you sure you want to reject this application?')">Reject</button>
                                    </form>
                                </div>
                            @elseif($app->isPending())
                                <span class="detail-sub">Waiting for applicant to pay</span>
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
