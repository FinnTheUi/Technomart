<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt #{{ $payment->id }} — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 24px; -webkit-font-smoothing: antialiased; }
        
        .receipt-container { width: 100%; max-width: 640px; background: #fff; border: 1px solid #e2e8f0; border-top: 8px solid #10b981; border-radius: 16px; padding: 40px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); position: relative; }
        .paid-stamp { position: absolute; top: 32px; right: 40px; color: #10b981; font-weight: 800; font-size: 24px; letter-spacing: 0.1em; border: 3px solid #10b981; padding: 4px 12px; border-radius: 8px; transform: rotate(5deg); opacity: 0.8; }
        
        .receipt-header { margin-bottom: 40px; }
        .receipt-header h3 { font-size: 28px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em; margin-bottom: 4px; }
        .receipt-header p { font-size: 14px; color: #64748b; font-weight: 500; }
        
        .receipt-details { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 40px; padding-bottom: 32px; border-bottom: 1px solid #e2e8f0; }
        .detail-block h4 { font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
        .detail-block p { font-size: 15px; color: #0f172a; font-weight: 600; margin-bottom: 2px; }
        .detail-block .sub { font-size: 13px; color: #64748b; font-weight: 400; }
        
        .receipt-table { width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 40px; }
        .receipt-table th { font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .receipt-table td { padding: 16px; border-bottom: 1px solid #e2e8f0; font-size: 15px; color: #0f172a; }
        .receipt-table th.right, .receipt-table td.right { text-align: right; }
        .receipt-table td.amount { font-weight: 700; color: #059669; }
        
        .receipt-total { display: flex; justify-content: space-between; align-items: center; padding: 20px 16px; background: #f8fafc; border-radius: 12px; margin-bottom: 40px; }
        .receipt-total .label { font-size: 16px; font-weight: 700; color: #475569; }
        .receipt-total .value { font-size: 24px; font-weight: 800; color: #10b981; }
        
        .receipt-meta { font-size: 13px; color: #64748b; }
        .receipt-meta p { margin-bottom: 4px; }
        
        .print-actions { margin-top: 40px; text-align: center; }
        .btn-print { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #fff; background: #0f172a; padding: 12px 24px; border-radius: 10px; border: none; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .btn-print:hover { background: #1e293b; transform: translateY(-1px); }
        .btn-print svg { width: 18px; height: 18px; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { border: none; box-shadow: none; border-top: 8px solid #10b981; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="paid-stamp">PAID</div>
        
        <div class="receipt-header">
            <h3>Tecnomart</h3>
            <p>Official Payment Receipt #{{ $payment->id }}</p>
        </div>
        
        <div class="receipt-details">
            <div class="detail-block">
                <h4>Billed To</h4>
                <p>{{ $payment->tenant->name ?? 'Tenant' }}</p>
                <p class="sub">{{ $payment->tenant->email ?? '' }}</p>
            </div>
            <div class="detail-block">
                <h4>Manager</h4>
                <p>{{ $payment->manager->name ?? 'Manager' }}</p>
            </div>
        </div>
        
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Stall Rental Fee (Stall #{{ $payment->stall_id }})</td>
                    <td class="right amount">${{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="receipt-total">
            <span class="label">Total Paid</span>
            <span class="value">${{ number_format($payment->amount, 2) }}</span>
        </div>
        
        <div class="receipt-meta">
            <p><strong>Paid Date:</strong> {{ $payment->paid_date }}</p>
            <p><strong>Transaction Reference:</strong> {{ $payment->receipt_url }}</p>
        </div>
        
        <div class="print-actions">
            <button onclick="window.print()" class="btn-print">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Receipt
            </button>
            <a href="javascript:history.back()" style="display:inline-block; margin-left: 12px; font-size: 14px; font-weight: 600; color: #64748b; text-decoration: none;">Back</a>
        </div>
    </div>
</body>
</html>
