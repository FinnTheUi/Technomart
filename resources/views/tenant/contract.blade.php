<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contract #{{ $contract->id }} — Technomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .contract-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 36px; max-width: 800px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .contract-title { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; }
        .contract-meta { font-size: 13px; color: #64748b; margin-bottom: 28px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
        .info-item label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .08em; display: block; margin-bottom: 4px; }
        .info-item .info-value { font-size: 15px; font-weight: 600; color: #0f172a; }
        .terms-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 28px; font-size: 14px; color: #334155; line-height: 1.7; }
        .signature-section { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
        .signature-section h3 { font-size: 16px; font-weight: 700; color: #059669; margin-bottom: 12px; }
        .signature-info { font-size: 13px; color: #475569; line-height: 1.6; }
        .btn-group { display: flex; gap: 12px; margin-top: 16px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; font-family: 'Inter', sans-serif; transition: 0.2s; text-decoration: none; }
        .btn-primary { background: #10b981; color: #fff; }
        .btn-primary:hover { background: #059669; }
        .btn-outline { background: #fff; border: 1px solid #cbd5e1; color: #334155; }
        .btn-outline:hover { background: #f1f5f9; }
    </style>
</head>
<body>
    @include('partials.tenant-sidebar', ['active' => 'contracts'])
    
    <div class="main">
        <div class="page-header">
            <div>
                <h1>📋 Lease Agreement</h1>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="contract-card">
            <div class="contract-title">
                Stall Lease Agreement
                <x-status-badge :status="$contract->status" />
            </div>
            <div class="contract-meta">Generated on {{ $contract->created_at->format('F d, Y') }}</div>

            <div class="info-grid">
                <div class="info-item"><label>Tenant Name</label><div class="info-value">{{ $contract->tenant->name }}</div></div>
                <div class="info-item"><label>Stall</label><div class="info-value">{{ $contract->stall->name ?? 'N/A' }}</div></div>
                <div class="info-item"><label>Start Date</label><div class="info-value">{{ $contract->start_date->format('M d, Y') }}</div></div>
                <div class="info-item"><label>End Date</label><div class="info-value">{{ $contract->end_date->format('M d, Y') }}</div></div>
                <div class="info-item"><label>Monthly Rent</label><div class="info-value">₱{{ number_format($contract->stall->price ?? 0, 2) }}</div></div>
                <div class="info-item"><label>Duration</label><div class="info-value">{{ $contract->start_date->diffInMonths($contract->end_date) }} month(s)</div></div>
            </div>

            <div class="terms-box">
                <strong style="color:#0f172a;">Terms & Conditions</strong><br><br>
                {{ $contract->terms ?? 'Standard rental terms apply. The tenant agrees to pay rent on time, maintain the stall in good condition, and follow all facility rules and regulations.' }}
            </div>

            @if($contract->isSigned())
            <div class="signature-section">
                <h3>Digitally Signed</h3>
                <div class="signature-info">
                    <strong>Signed by:</strong> {{ $contract->tenant->name }}<br>
                    <strong>Date:</strong> {{ $contract->signed_at->format('F d, Y g:i A') }}<br>
                    <strong>IP Address:</strong> {{ $contract->signed_ip }}<br>
                    <strong>Document Hash:</strong> <code style="font-size:11px;color:#64748b;background:#f1f5f9;padding:2px 4px;border-radius:4px;">{{ $contract->document_hash }}</code>
                </div>
            </div>
            <div class="btn-group">
                <a href="{{ route('tenant.contracts.pdf', $contract) }}" class="btn btn-outline" target="_blank">Download / Print PDF</a>
            </div>
            @else
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:20px;margin-bottom:20px;">
                <p style="font-size:14px;color:#d97706;font-weight:600;margin-bottom:8px;">This contract has not been signed yet.</p>
                <p style="font-size:13px;color:#64748b;">By clicking "Sign Contract" below, you agree to the terms and conditions outlined in this lease agreement. Your digital signature will be recorded with your IP address and timestamp.</p>
            </div>
            <form method="POST" action="{{ route('tenant.contracts.sign', $contract) }}">
                @csrf
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">✍️ Sign Contract</button>
                    <a href="{{ route('tenant.contracts') }}" class="btn btn-outline">← Back to Contracts</a>
                </div>
            </form>
            @endif
        </div>

        @if($contract->isActive())
        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:12px;">Renew this lease</h3>
            @if($contract->hasPendingRenewalRequest())
                <p class="detail-sub">You asked to extend by {{ $contract->renewal_months_requested }} month(s) on {{ $contract->renewal_requested_at->format('M d, Y') }}. Your manager will review the request.</p>
            @else
                <p class="detail-sub" style="margin-bottom:12px;">Your lease ends on {{ $contract->end_date->format('M d, Y') }}. Ask your manager to extend it:</p>
                <form method="POST" action="{{ route('tenant.contracts.requestRenewal', $contract) }}" style="display:flex;gap:10px;align-items:center;">
                    @csrf
                    <select name="months" class="form-input" style="max-width:180px;">
                        @foreach([1, 3, 6, 12] as $m)<option value="{{ $m }}">{{ $m }} month(s)</option>@endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Request renewal</button>
                </form>
            @endif
        </div>
        @elseif($contract->terminated_at)
        <div class="panel">
            <h3 class="panel-title">Lease terminated</h3>
            <p class="detail-sub">Terminated on {{ $contract->terminated_at->format('M d, Y') }}. Reason: {{ $contract->termination_reason }}</p>
        </div>
        @endif
    </div>
</body>
</html>
