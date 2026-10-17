<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leases — Technomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .lease-actions { display: flex; flex-direction: column; gap: 8px; min-width: 240px; }
        .lease-actions form { display: flex; gap: 6px; align-items: center; }
        .lease-actions select, .lease-actions input { padding: 6px 8px; font-size: 13px; }
        .renewal-flag { background: #eef2ff; border: 1px solid #c7d2fe; color: #4338ca; font-size: 12px; font-weight: 600; border-radius: 8px; padding: 4px 8px; display: inline-block; }
        .ending-soon { color: #d97706; font-weight: 600; }
        .empty { text-align: center; padding: 40px; color: #64748b; }
    </style>
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'contracts'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Leases</h1>
                <p>Renew or terminate leases on your stalls. Ended leases expire automatically each night.</p>
            </div>
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
            @if($contracts->count() > 0)
            <table class="data-table">
                <thead><tr><th>#</th><th>Tenant</th><th>Stall</th><th>Term</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($contracts as $contract)
                    <tr>
                        <td style="font-weight:600;">{{ $contract->id }}</td>
                        <td>
                            <div style="font-weight:600;color:#0f172a;">{{ $contract->tenant->name ?? 'N/A' }}</div>
                            <div class="detail-sub">{{ $contract->tenant->email ?? '' }}</div>
                        </td>
                        <td>{{ $contract->stall->name ?? 'N/A' }}</td>
                        <td>
                            {{ $contract->start_date->format('M d, Y') }} – {{ $contract->end_date->format('M d, Y') }}
                            @if($contract->isActive() && $contract->end_date->lte(today()->addDays(30)))
                                <div class="ending-soon">Ends {{ $contract->end_date->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td>
                            <x-status-badge :status="$contract->status" />
                            @if($contract->hasPendingRenewalRequest())
                                <div style="margin-top:6px;"><span class="renewal-flag">Renewal requested: {{ $contract->renewal_months_requested }} mo.</span></div>
                            @endif
                            @if($contract->termination_reason)
                                <div class="detail-sub">{{ $contract->termination_reason }}</div>
                            @endif
                            @if($contract->latestActivity)<div class="detail-sub" title="{{ $contract->latestActivity->description }}">Last activity: {{ $contract->latestActivity->created_at->diffForHumans() }}</div>@endif
                        </td>
                        <td>
                            <div class="lease-actions">
                                @if($contract->isActive())
                                    <form method="POST" action="{{ route('manager.contracts.renew', $contract) }}">
                                        @csrf
                                        <select name="months" class="form-input">
                                            @foreach([1, 3, 6, 12] as $m)
                                                <option value="{{ $m }}" @selected($contract->renewal_months_requested == $m)>{{ $m }} mo.</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn-sm btn-approve" onclick="return confirm('Extend this lease and create rent bills for the added months?')">Renew</button>
                                    </form>
                                    <details>
                                        <summary class="btn-sm btn-reject" style="list-style:none;cursor:pointer;display:inline-block;">Terminate…</summary>
                                        <form method="POST" action="{{ route('manager.contracts.terminate', $contract) }}" style="margin-top:6px;">
                                            @csrf
                                            <input type="text" name="reason" class="form-input" placeholder="Reason" required maxlength="255">
                                            <button type="submit" class="btn-sm btn-reject" onclick="return confirm('Terminate this lease? Unpaid future rent will be cancelled and the stall becomes available.')">Confirm</button>
                                        </form>
                                    </details>
                                @endif
                                <a href="{{ route('manager.contracts.pdf', $contract) }}" target="_blank" class="btn-sm btn-view" style="align-self:flex-start;">Print lease</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="empty">No leases yet. Leases are created when you approve rental applications.</p>
            @endif
        </div>
    </div>
</body>
</html>
