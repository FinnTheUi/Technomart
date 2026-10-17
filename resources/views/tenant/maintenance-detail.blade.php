<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Detail — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .panel { max-width: 800px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .info-item label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .08em; display: block; margin-bottom: 4px; }
        .info-item .val { font-size: 14px; font-weight: 600; color: #0f172a; }
        .desc-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; font-size: 14px; color: #334155; line-height: 1.6; margin-bottom: 24px; }
        .image-preview { max-width: 400px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 24px; }
        
        /* Timeline */
        .timeline { position: relative; padding-left: 32px; }
        .timeline::before { content: ''; position: absolute; left: 11px; top: 8px; bottom: 8px; width: 2px; background: #e2e8f0; }
        .timeline-item { position: relative; margin-bottom: 24px; }
        .timeline-dot { position: absolute; left: -32px; top: 4px; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; }
        .dot-active { background: #d1fae5; border: 2px solid #10b981; color: #059669; }
        .dot-pending { background: #f1f5f9; border: 2px solid #cbd5e1; color: #94a3b8; }
        .dot-current { background: #e0e7ff; border: 2px solid #4f46e5; color: #4338ca; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); } 50% { box-shadow: 0 0 0 8px rgba(79, 70, 229, 0); } }
        .timeline-content h4 { font-size: 14px; font-weight: 600; color: #0f172a; }
        .timeline-content p { font-size: 12px; color: #64748b; margin-top: 2px; }
        .btn-back { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; background: #fff; border: 1px solid #cbd5e1; color: #334155; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; text-decoration: none; margin-top: 12px; transition: 0.2s; }
        .btn-back:hover { background: #f1f5f9; }
    </style>
</head>
<body>
    @include('partials.tenant-sidebar', ['active' => 'maintenance'])
    
    <div class="main">
        <div class="page-header">
            <div>
                <h1>Maintenance Request</h1>
            </div>
        </div>
        <div class="panel">
            <div class="info-grid">
                <div class="info-item"><label>Subject</label><div class="val">{{ $maintenanceRequest->subject }}</div></div>
                <div class="info-item"><label>Stall</label><div class="val">{{ $maintenanceRequest->stall->name ?? 'N/A' }}</div></div>
                <div class="info-item"><label>Priority</label><div class="val"><span class="badge {{ $maintenanceRequest->priority==='urgent'?'badge-red':($maintenanceRequest->priority==='high'?'badge-yellow':'badge-blue') }}">{{ ucfirst($maintenanceRequest->priority) }}</span></div></div>
                <div class="info-item"><label>Current Status</label><div class="val"><x-status-badge :status="$maintenanceRequest->status" /></div></div>
                <div class="info-item"><label>Submitted</label><div class="val">{{ $maintenanceRequest->created_at->format('M d, Y g:i A') }}</div></div>
                <div class="info-item"><label>Last Updated</label><div class="val">{{ $maintenanceRequest->updated_at->diffForHumans() }}</div></div>
            </div>
            <div class="desc-box"><strong style="color:#0f172a;">Description:</strong><br><br>{{ $maintenanceRequest->description }}</div>
            @if($maintenanceRequest->image_path)
            <p style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">Attached Photo</p>
            <img src="{{ asset('storage/'.$maintenanceRequest->image_path) }}" alt="Maintenance photo" class="image-preview">
            @endif
        </div>

        <div class="panel">
            <h3 style="font-size:17px;font-weight:700;color:#0f172a;margin-bottom:20px;">Status Timeline</h3>
            @php
                $statuses = ['pending' => 'Submitted', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
                $statusOrder = array_keys($statuses);
                $currentIndex = array_search($maintenanceRequest->status->value, $statusOrder);
            @endphp
            <div class="timeline">
                @foreach($statuses as $key => $label)
                @php $index = array_search($key, $statusOrder); @endphp
                <div class="timeline-item">
                    <div class="timeline-dot {{ $index < $currentIndex ? 'dot-active' : ($index === $currentIndex ? 'dot-current' : 'dot-pending') }}">
                        @if($index < $currentIndex) ✓ @elseif($index === $currentIndex) ● @else ○ @endif
                    </div>
                    <div class="timeline-content">
                        <h4 style="{{ $index <= $currentIndex ? 'color:#0f172a' : 'color:#94a3b8' }}">{{ $label }}</h4>
                        @if($index === 0)<p>{{ $maintenanceRequest->created_at->format('M d, Y g:i A') }}</p>
                        @elseif($index <= $currentIndex)<p>{{ $maintenanceRequest->updated_at->format('M d, Y g:i A') }}</p>
                        @else<p>Pending</p>@endif
                    </div>
                </div>
                @endforeach
            </div>

            @if($timeline->count() > 0)
            <h4 style="font-size:14px;font-weight:600;color:#475569;margin-top:24px;margin-bottom:12px;">Activity Log</h4>
            @foreach($timeline as $entry)
            <div style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px;color:#64748b;">
                <span style="color:#334155;">{{ $entry->description }}</span>
                <span style="float:right;font-size:11px;">{{ $entry->created_at->diffForHumans() }}</span>
            </div>
            @endforeach
            @endif
        </div>

        <a href="{{ route('tenant.maintenance') }}" class="btn-back">← Back to Maintenance</a>
    </div>
</body>
</html>
