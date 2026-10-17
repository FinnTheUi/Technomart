<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Official Receipt {{ $payable->receipt_number }} — Technomart</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px; -webkit-font-smoothing: antialiased; }
        .receipt { width: 100%; max-width: 640px; background: #fff; border: 1px solid #e2e8f0; border-top: 8px solid #10b981; border-radius: 16px; padding: 40px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); position: relative; }
        .stamp { position: absolute; top: 32px; right: 40px; color: #10b981; font-weight: 800; font-size: 22px; letter-spacing: 0.1em; border: 3px solid #10b981; padding: 4px 12px; border-radius: 8px; transform: rotate(5deg); opacity: 0.8; }
        .header { margin-bottom: 32px; }
        .header h1 { font-size: 26px; font-weight: 900; letter-spacing: -0.02em; }
        .header p { font-size: 14px; color: #64748b; font-weight: 500; }
        .or-number { font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 6px; }
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding-bottom: 24px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; }
        .block h4 { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .block p { font-size: 15px; font-weight: 600; }
        .block .sub { font-size: 13px; color: #64748b; font-weight: 400; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; }
        td { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; font-size: 15px; }
        .right { text-align: right; }
        .total { display: flex; justify-content: space-between; align-items: center; padding: 18px 12px; background: #f8fafc; border-radius: 12px; margin-bottom: 24px; }
        .total .label { font-size: 16px; font-weight: 700; color: #475569; }
        .total .value { font-size: 24px; font-weight: 800; color: #10b981; }
        .meta { font-size: 13px; color: #64748b; line-height: 1.7; }
        .actions { margin-top: 32px; text-align: center; }
        .btn-print { font-family: inherit; font-size: 14px; font-weight: 600; color: #fff; background: #0f172a; padding: 12px 24px; border-radius: 10px; border: none; cursor: pointer; }
        .btn-back { margin-left: 12px; font-size: 14px; font-weight: 600; color: #64748b; text-decoration: none; }
        @media (max-width: 560px) { .receipt { padding: 28px 20px; } .details { grid-template-columns: 1fr; } .stamp { top: 20px; right: 20px; font-size: 16px; } }
        @media print { body { background: #fff; padding: 0; } .receipt { border: none; box-shadow: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="stamp">PAID</div>

        <div class="header">
            <h1>Technomart</h1>
            <p>Official Receipt</p>
            <div class="or-number">No. {{ $payable->receipt_number }}</div>
        </div>

        <div class="details">
            <div class="block">
                <h4>Received from</h4>
                <p>{{ $payer->name ?? 'Tenant' }}</p>
                <p class="sub">{{ $payer->email ?? '' }}</p>
            </div>
            <div class="block">
                <h4>Date paid</h4>
                <p>{{ ($payable->paid_date ?? $payable->paid_at ?? $payable->verified_at)?->format('M d, Y g:i A') }}</p>
                <p class="sub">Received by {{ $payable->verifier->name ?? 'Online payment gateway' }}</p>
            </div>
        </div>

        <table>
            <thead><tr><th>Description</th><th class="right">Amount</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $payable->paymentDescription() }}</td>
                    <td class="right">₱{{ number_format($payable->baseAmount(), 2) }}</td>
                </tr>
                @if($payable->lateFee() > 0)
                <tr>
                    <td>Late payment fee</td>
                    <td class="right">₱{{ number_format($payable->lateFee(), 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="total">
            <span class="label">Total paid</span>
            <span class="value">₱{{ number_format($payable->amountDue(), 2) }}</span>
        </div>

        <div class="meta">
            <p><strong>Payment method:</strong> {{ match ($payable->payment_method) { 'gcash' => 'GCash', 'cash' => 'Cash', 'online' => 'Online (PayMongo)', default => ucfirst((string) $payable->payment_method) } }}</p>
            @if($payable->reference_number)
                <p><strong>Transaction reference:</strong> {{ $payable->reference_number }}</p>
            @endif
        </div>

        <div class="actions">
            <button onclick="window.print()" class="btn-print">Print receipt</button>
            <a href="{{ url()->previous() }}" class="btn-back">Back</a>
        </div>
    </div>
</body>
</html>
