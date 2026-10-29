<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout — Technomart</title>
    <meta name="description" content="Complete your stall rental payment on Technomart.">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }

        .site-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; padding: 0 24px; background: rgba(255,255,255,0.9); backdrop-filter: blur(20px); border-bottom: 1px solid #e2e8f0; }
        .nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 72px; }
        .nav-brand { display: flex; align-items: center; gap: 12px; }
        .nav-brand svg { width: 36px; height: 36px; }
        .nav-brand span { font-weight: 800; font-size: 18px; color: #0f172a; }

        .checkout-container { position: relative; z-index: 1; max-width: 580px; margin: 0 auto; padding: 120px 24px 80px; }

        .checkout-header { text-align: center; margin-bottom: 36px; }
        .checkout-header h1 { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .checkout-header p { font-size: 14px; color: #475569; }

        /* Order Summary */
        .order-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; margin-bottom: 28px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .order-title { font-size: 15px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 20px; }
        .order-rows { display: flex; flex-direction: column; gap: 14px; }
        .order-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; }
        .order-row .label { color: #64748b; }
        .order-row .value { color: #0f172a; font-weight: 600; }
        .order-divider { height: 1px; background: #e2e8f0; margin: 16px 0; }
        .order-total { display: flex; justify-content: space-between; align-items: center; }
        .order-total .label { font-size: 16px; font-weight: 700; color: #0f172a; }
        .order-total .value { font-size: 28px; font-weight: 800; color: #059669; }

        /* Payment Options */
        .payment-options { display: flex; flex-direction: column; gap: 16px; }
        .payment-title { font-size: 15px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }

        .payment-btn { display: flex; align-items: center; gap: 16px; width: 100%; padding: 20px 24px; border-radius: 16px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; font-family: 'Inter', sans-serif; transition: 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .payment-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        .payment-btn-gcash { border-color: #93c5fd; background: #eff6ff; }
        .payment-btn-gcash:hover { border-color: #3b82f6; }
        .payment-btn-person:hover { border-color: #94a3b8; }

        .payment-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .payment-icon-gcash { background: #bfdbfe; color: #2563eb; }
        .payment-icon-person { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        .payment-icon svg { width: 24px; height: 24px; }

        .payment-info { flex: 1; text-align: left; }
        .payment-name { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .payment-desc { font-size: 13px; color: #64748b; line-height: 1.4; }
        .payment-arrow { color: #94a3b8; flex-shrink: 0; }
        .payment-arrow svg { width: 20px; height: 20px; }

        @media (max-width: 640px) {
            .nav-brand span { display: none; }
        }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    <header class="site-nav">
        <div class="nav-inner">
            <a href="/" class="nav-brand">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#6366F1"/><stop offset="100%" stop-color="#06B6D4"/></linearGradient></defs><rect width="100" height="100" rx="22" fill="url(#logoGrad)"/><path d="M 28 30 L 72 30" stroke="white" stroke-width="8" stroke-linecap="round"/><path d="M 50 30 L 50 74" stroke="white" stroke-width="8" stroke-linecap="round"/><circle cx="50" cy="26" r="4" fill="rgba(255,255,255,0.4)"/></svg>
                <span>Technomart</span>
            </a>
        </div>
    </header>

    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Checkout</h1>
            <p>Review your order and choose how you'd like to pay.</p>
        </div>

        <!-- Order Summary -->
        <div class="order-card">
            <div class="order-title">Order Summary</div>
            <div class="order-rows">
                <div class="order-row">
                    <span class="label">Stall</span>
                    <span class="value">{{ $application->stall->name }}</span>
                </div>
                @if($application->stall->location)
                <div class="order-row">
                    <span class="label">Location</span>
                    <span class="value">{{ $application->stall->location }}</span>
                </div>
                @endif
                <div class="order-row">
                    <span class="label">Monthly Rate</span>
                    <span class="value">₱{{ number_format($application->stall->price, 2) }}</span>
                </div>
                <div class="order-row">
                    <span class="label">Duration</span>
                    <span class="value">{{ $application->rental_duration }} {{ $application->rental_duration === 1 ? 'month' : 'months' }}</span>
                </div>
                <div class="order-row">
                    <span class="label">Applicant</span>
                    <span class="value">{{ $application->name }}</span>
                </div>
            </div>
            <div class="order-divider"></div>
            <div class="order-total">
                <span class="label">Amount Due</span>
                <span class="value">₱{{ number_format($totalAmount, 2) }}</span>
            </div>
        </div>

        <!-- Payment Options -->
        <div class="payment-options">
            <div class="payment-title">Select Payment Method</div>

            @if(session('error'))
                <div style="padding:12px 16px;border-radius:12px;margin-bottom:12px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:14px;">{{ session('error') }}</div>
            @endif

            @if($onlinePayments)
            <form method="POST" action="{{ $application->signedUrl('rental.payGateway') }}">
                @csrf
                <button type="submit" class="payment-btn payment-btn-gcash" id="btn-pay-gateway">
                    <div class="payment-icon payment-icon-gcash">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div class="payment-info">
                        <div class="payment-name">Pay Online (GCash, Maya or card)</div>
                        <div class="payment-desc">Secure checkout by PayMongo. Payment is confirmed automatically.</div>
                    </div>
                    <div class="payment-arrow">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </button>
            </form>
            @endif

            <div class="payment-btn" style="display:block;cursor:default;" id="gcash-reference">
                <div style="display:flex;gap:16px;align-items:flex-start;">
                    <div class="payment-icon payment-icon-gcash">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="payment-info" style="flex:1;">
                        <div class="payment-name">Send via GCash</div>
                        <div class="payment-desc">
                            Amount to send: PHP {{ number_format($totalAmount, 2) }}.
                            The manager checks it before approving your application.
                            @include('partials.billing.gcash-qr', ['managers' => collect([$application->stall->manager])])
                        </div>
                        <form method="POST" action="{{ $application->signedUrl('rental.payOnline') }}" style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                            @csrf
                            <input type="text" name="reference_number" inputmode="numeric" maxlength="20" required placeholder="13-digit reference no." value="{{ old('reference_number') }}"
                                   style="flex:1;min-width:180px;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;font-family:inherit;">
                            <button type="submit" id="btn-pay-gcash" style="padding:10px 18px;border:none;border-radius:10px;background:#0ea5e9;color:#fff;font-weight:700;cursor:pointer;font-family:inherit;">Submit</button>
                        </form>
                        @error('reference_number')<div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ $application->signedUrl('rental.payInPerson') }}">
                @csrf
                <button type="submit" class="payment-btn payment-btn-person" id="btn-pay-in-person">
                    <div class="payment-icon payment-icon-person">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div class="payment-info">
                        <div class="payment-name">Pay In Person</div>
                        <div class="payment-desc">Visit our office to pay. Your application will be held pending manager approval.</div>
                    </div>
                    <div class="payment-arrow">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
