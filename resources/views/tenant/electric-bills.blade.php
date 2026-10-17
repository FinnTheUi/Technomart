<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Electric Bills — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .btn-pay { padding: 7px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #fff; background: #10b981; border: none; cursor: pointer; font-family: 'Inter', sans-serif; transition: 0.2s; text-decoration: none; }
        .btn-pay:hover { background: #059669; }
        .settled { font-size: 13px; color: #059669; font-weight: 500; }
        .empty { text-align: center; padding: 40px; color: #64748b; font-size: 14px; }
        .bill-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .bill-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; }
        .bill-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; }
        .bill-meta { font-size: 13px; color: #64748b; margin-top: 4px; }
        .bill-body { padding: 20px; }
        .bill-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        @media(max-width:640px) { .bill-grid { grid-template-columns: 1fr; } }
        .bill-field { font-size: 13px; }
        .bill-label { color: #64748b; font-weight: 500; margin-bottom: 4px; }
        .bill-value { color: #0f172a; font-weight: 600; font-size: 14px; }
        .bill-summary { background: #f1f5f9; border-radius: 8px; padding: 16px; display: flex; justify-content: space-between; align-items: center; }
        .bill-total-label { font-size: 14px; font-weight: 600; color: #475569; }
        .bill-total-value { font-size: 24px; font-weight: 800; color: #0f172a; }
        .bill-footer { padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; align-items: center; background: #fff; }
    </style>
</head>
<body>
    @include('partials.tenant-sidebar', ['active' => 'electric-bills'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>My Electric Bills</h1>
                <p>View and pay your electric power bills</p>
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

        @if($bills->count() > 0)
            @foreach($bills as $bill)
            <div class="bill-card">
                <div class="bill-header">
                    <div>
                        <h3 class="bill-title">ELECTRIC POWER BILL</h3>
                        <div class="bill-meta">Meter #: {{ $bill->meter_number ?? 'N/A' }} | Billed: {{ $bill->billing_date->format('M Y') }}</div>
                    </div>
                    <div>
                        <x-status-badge :status="$bill->status" />
                    </div>
                </div>
                <div class="bill-body">
                    <div class="bill-grid">
                        <div class="bill-field">
                            <div class="bill-label">Location / Stall</div>
                            <div class="bill-value">{{ $bill->stall->name ?? 'N/A' }}</div>
                        </div>
                        <div class="bill-field">
                            <div class="bill-label">Period Covered</div>
                            <div class="bill-value">{{ $bill->period_from->format('M d, Y') }} — {{ $bill->period_to->format('M d, Y') }}</div>
                        </div>
                        <div class="bill-field">
                            <div class="bill-label">Meter Reading</div>
                            <div class="bill-value">
                                <span style="color:#64748b; font-weight:normal; font-size:12px;">Present:</span> {{ $bill->reading_present }}<br>
                                <span style="color:#64748b; font-weight:normal; font-size:12px;">Previous:</span> {{ $bill->reading_previous }}
                            </div>
                        </div>
                        <div class="bill-field">
                            <div class="bill-label">Consumption</div>
                            <div class="bill-value">
                                <span style="color:#64748b; font-weight:normal; font-size:12px;">KWH Used:</span> {{ $bill->kwh_used }}<br>
                                <span style="color:#64748b; font-weight:normal; font-size:12px;">Rate:</span> ₱{{ $bill->rate_per_kwh }}/KWH
                            </div>
                        </div>
                        <div class="bill-field">
                            <div class="bill-label">Signatures</div>
                            <div class="bill-value" style="font-size:12px;">
                                <span style="color:#64748b; font-weight:normal;">Prepared by:</span> {{ $bill->prepared_by ?? '—' }}<br>
                                <span style="color:#64748b; font-weight:normal;">Noted by:</span> {{ $bill->noted_by ?? '—' }}
                            </div>
                        </div>
                        <div class="bill-field">
                            <div class="bill-label">Due Date</div>
                            <div class="bill-value" style="color:#dc2626;">{{ $bill->due_date->format('M d, Y') }}</div>
                        </div>
                    </div>

                    <div class="bill-summary">
                        <div class="bill-total-label">Total Amount Due</div>
                        <div class="bill-total-value">₱{{ number_format($bill->amountDue(), 2) }}</div>
                        @if($bill->lateFee() > 0)
                            <div style="font-size:12px;color:#b91c1c;">includes ₱{{ number_format($bill->lateFee(), 2) }} late fee</div>
                        @endif
                    </div>
                </div>
                
                <div class="bill-footer">
                    @if($bill->isPaid())
                        <span class="settled">✓ Paid on {{ $bill->paid_date?->format('M d, Y g:i A') ?? '—' }}</span>&nbsp;
                    @endif
                    @include('partials.billing.pay-actions', ['payable' => $bill])
                </div>
            </div>
            @endforeach
        @else
            <div class="panel">
                <p class="empty">No electric bills yet. Your manager will issue bills based on meter readings.</p>
            </div>
        @endif
    </div>
</body>
</html>
