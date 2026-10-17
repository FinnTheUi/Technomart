<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Electric Bills — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .btn-paid{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;}
        .btn-paid:hover{background:#d1fae5;}
        .btn-overdue{background:#fef2f2;color:#dc2626;border:1px solid #fecdd3;}
        .btn-overdue:hover{background:#fee2e2;}
        .calc-field { background-color: #f1f5f9; cursor: not-allowed; font-weight: bold; }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'electric-bills'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Electric Bills</h1>
                <p>Manage and issue electric power bills to tenants</p>
            </div>
        </div>
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <ul style="margin:0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Create Electric Bill -->
        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:20px;">Issue Electric Power Bill</h3>
            <form method="POST" action="{{ route('manager.electric-bills.store') }}">
                @csrf
                <div class="form-row-inline" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Tenant</label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">Select tenant...</option>
                            @foreach($tenants as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stall</label>
                        <select name="stall_id" class="form-select" required>
                            <option value="">Select stall...</option>
                            @foreach($stalls as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Meter #</label>
                        <input type="text" name="meter_number" class="form-input" placeholder="e.g. 2012-06-006511">
                    </div>
                </div>

                <div class="form-row-inline" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
                    <div class="form-group">
                        <label class="form-label">Period Covered (From)</label>
                        <input type="date" name="period_from" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Period Covered (To)</label>
                        <input type="date" name="period_to" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Billing Date</label>
                        <input type="date" name="billing_date" class="form-input" required>
                    </div>
                </div>

                <div class="form-row-inline" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-top: 16px;">
                    <div class="form-group">
                        <label class="form-label">Previous Reading</label>
                        <input type="number" name="reading_previous" id="prev_read" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Present Reading</label>
                        <input type="number" name="reading_present" id="pres_read" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">KWH Used (Auto)</label>
                        <input type="text" id="kwh_used" class="form-input calc-field" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rate (₱/KWH)</label>
                        <input type="number" name="rate_per_kwh" id="rate" class="form-input" step="0.01" min="0" value="11.36" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Amount Due (₱)</label>
                        <input type="text" id="total_amount" class="form-input calc-field" readonly>
                    </div>
                </div>

                <div class="form-row-inline" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
                    <div class="form-group">
                        <label class="form-label">Prepared By</label>
                        <input type="text" name="prepared_by" class="form-input" value="{{ auth()->user()->name }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Noted By</label>
                        <input type="text" name="noted_by" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-input" required>
                    </div>
                </div>

                <div style="margin-top: 24px; text-align: right;">
                    <button type="submit" class="btn-submit">Issue Electric Bill</button>
                </div>
            </form>
        </div>

        <!-- Bill Records -->
        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:20px;">Electric Bill Records</h3>
            @if($bills->count() > 0)
            <div style="overflow-x: auto;">
                <table class="data-table" style="min-width: 1000px;">
                    <thead><tr>
                        <th>#</th>
                        <th>Tenant</th>
                        <th>Stall</th>
                        <th>Period</th>
                        <th>KWH</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr></thead>
                    <tbody>
                        @foreach($bills as $b)
                        <tr>
                            <td style="font-weight:600;">{{ $b->id }}</td>
                            <td style="font-weight:600;color:#0f172a;">{{ $b->tenant->name ?? 'N/A' }}</td>
                            <td>{{ $b->stall->name ?? 'N/A' }}</td>
                            <td style="font-size: 13px;">{{ $b->period_from->format('M d') }} - {{ $b->period_to->format('M d, Y') }}</td>
                            <td style="font-weight:500;">{{ $b->kwh_used }}</td>
                            <td style="font-weight:700; color:#1e293b;">₱{{ number_format($b->amountDue(), 2) }}@if($b->lateFee() > 0)<div class="detail-sub">incl. ₱{{ number_format($b->lateFee(), 2) }} late fee</div>@endif</td>
                            <td>{{ $b->due_date->format('M d, Y') }}</td>
                            <td><x-status-badge :status="$b->status" /></td>
                            <td>@include('partials.billing.review-actions', ['payable' => $b, 'overdueUrl' => route('manager.electric-bills.markOverdue', $b)])</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p style="text-align:center;padding:40px;color:#64748b;">No electric bills issued yet.</p>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const prevInput = document.getElementById('prev_read');
            const presInput = document.getElementById('pres_read');
            const rateInput = document.getElementById('rate');
            const kwhOutput = document.getElementById('kwh_used');
            const amountOutput = document.getElementById('total_amount');

            function calculate() {
                const prev = parseFloat(prevInput.value) || 0;
                const pres = parseFloat(presInput.value) || 0;
                const rate = parseFloat(rateInput.value) || 0;

                let kwh = pres - prev;
                if (kwh < 0) kwh = 0;

                const amount = kwh * rate;

                kwhOutput.value = kwh.toFixed(2);
                amountOutput.value = '₱ ' + amount.toFixed(2);
            }

            prevInput.addEventListener('input', calculate);
            presInput.addEventListener('input', calculate);
            rateInput.addEventListener('input', calculate);
        });
    </script>
</body>
</html>
