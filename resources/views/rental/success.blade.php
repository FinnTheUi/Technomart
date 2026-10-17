<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $application->payment_method === 'gcash' ? 'Payment Successful' : 'Application Submitted' }} — Technomart</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }

        .site-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; padding: 0 24px; background: rgba(255,255,255,0.9); backdrop-filter: blur(20px); border-bottom: 1px solid #e2e8f0; }
        .nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 72px; }
        .nav-brand { display: flex; align-items: center; gap: 12px; }
        .nav-brand svg { width: 36px; height: 36px; }
        .nav-brand span { font-weight: 800; font-size: 18px; color: #0f172a; }

        .success-container { position: relative; z-index: 1; max-width: 600px; margin: 0 auto; padding: 120px 24px 80px; text-align: center; }

        /* Success Icon */
        .success-icon-wrap { width: 88px; height: 88px; border-radius: 50%; margin: 0 auto 28px; display: flex; align-items: center; justify-content: center; }
        .success-icon-wrap.paid { background: #d1fae5; border: 2px solid #a7f3d0; color: #059669; }
        .success-icon-wrap.pending { background: #fef3c7; border: 2px solid #fde68a; color: #d97706; }
        .success-icon-wrap svg { width: 40px; height: 40px; }

        .success-title { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 12px; }
        .success-subtitle { font-size: 15px; color: #475569; line-height: 1.7; max-width: 440px; margin: 0 auto 36px; }

        /* Credentials Card */
        .credentials-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; margin-bottom: 28px; text-align: left; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .credentials-title { font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 20px; }
        .credential-row { margin-bottom: 18px; }
        .credential-row:last-child { margin-bottom: 0; }
        .credential-label { font-size: 12px; color: #64748b; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        .credential-value { font-size: 18px; font-weight: 700; color: #0f172a; font-family: 'Courier New', monospace; padding: 12px 16px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; word-break: break-all; }
        .credential-note { font-size: 12px; color: #d97706; margin-top: 12px; display: flex; align-items: center; gap: 6px; font-weight: 500; }
        .credential-note svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* Receipt Card */
        .receipt-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; margin-bottom: 28px; text-align: left; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .receipt-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px; }
        .receipt-row .r-label { color: #64748b; }
        .receipt-row .r-value { color: #0f172a; font-weight: 600; }
        .receipt-divider { height: 1px; background: #e2e8f0; margin: 8px 0; }
        .receipt-total { display: flex; justify-content: space-between; padding: 12px 0 0; }
        .receipt-total .r-label { font-size: 16px; font-weight: 700; color: #0f172a; }
        .receipt-total .r-value { font-size: 22px; font-weight: 800; color: #059669; }

        /* Info Card (Pending) */
        .info-card { background: #fffbeb; border: 1px solid #fde68a; border-radius: 16px; padding: 24px; margin-bottom: 28px; display: flex; gap: 16px; text-align: left; }
        .info-card svg { width: 24px; height: 24px; color: #d97706; flex-shrink: 0; margin-top: 2px; }
        .info-card-text h3 { font-size: 15px; font-weight: 700; color: #b45309; margin-bottom: 6px; }
        .info-card-text p { font-size: 13px; color: #78350f; line-height: 1.6; }

        /* Buttons */
        .action-buttons { display: flex; flex-direction: column; gap: 12px; max-width: 320px; margin: 0 auto; }
        .btn-primary { display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 15px; font-weight: 600; color: #fff; padding: 14px 28px; border-radius: 10px; background: #4f46e5; border: none; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3); }
        .btn-primary:hover { background: #4338ca; transform: translateY(-1px); }
        .btn-secondary { display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; font-weight: 600; color: #475569; padding: 12px 28px; border-radius: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; transition: 0.2s; }
        .btn-secondary:hover { color: #0f172a; background: #e2e8f0; }

        @media (max-width: 640px) { .nav-brand span { display: none; } }
    </style>
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

    <div class="success-container">
        @if(in_array($application->payment_method, ['gcash', 'online'], true))
            {{-- ===== GCASH / ONLINE PAYMENT ===== --}}
            <div class="success-icon-wrap paid">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="success-title">{{ $application->payment_status === \App\Enums\PaymentStatus::Paid ? 'Payment Successful!' : 'Payment Submitted!' }}</h1>
            <p class="success-subtitle">
                @if($application->payment_status === \App\Enums\PaymentStatus::Paid)
                    Your online payment was confirmed.
                @else
                    We received your GCash reference number; the manager will verify it.
                @endif
                Your application is awaiting manager approval. Once approved, a confirmation will be sent to <strong style="color: #0f172a;">{{ $application->email }}</strong> and you can sign in with the password you chose.
            </p>

            {{-- Receipt --}}
            <div class="receipt-card">
                <div class="credentials-title">Payment Details</div>
                <div class="receipt-row">
                    <span class="r-label">Stall</span>
                    <span class="r-value">{{ $application->stall->name }}</span>
                </div>
                @if($application->stall->location)
                <div class="receipt-row">
                    <span class="r-label">Location</span>
                    <span class="r-value">{{ $application->stall->location }}</span>
                </div>
                @endif
                <div class="receipt-row">
                    <span class="r-label">Monthly Rate</span>
                    <span class="r-value">₱{{ number_format($application->stall->price, 2) }}</span>
                </div>
                <div class="receipt-row">
                    <span class="r-label">Duration</span>
                    <span class="r-value">{{ $application->rental_duration }} month(s)</span>
                </div>
                <div class="receipt-row">
                    <span class="r-label">GCash Reference</span>
                    <span class="r-value">{{ $application->gcash_reference }}</span>
                </div>
                <div class="receipt-divider"></div>
                <div class="receipt-total">
                    <span class="r-label">Amount Paid</span>
                    <span class="r-value">₱{{ number_format($application->stall->price, 2) }}</span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="{{ route('login') }}" class="btn-primary" id="btn-login-now">
                    Sign In to Dashboard
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="/" class="btn-secondary">Back to Home</a>
            </div>

        @else
            {{-- ===== PAY IN PERSON — PENDING ===== --}}
            <div class="success-icon-wrap pending">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="success-title">Application Submitted!</h1>
            <p class="success-subtitle">
                Your rental application for <strong style="color: #0f172a;">{{ $application->stall->name }}</strong> has been submitted successfully and is now awaiting manager approval.
            </p>

            <div class="info-card">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="info-card-text">
                    <h3>What happens next?</h3>
                    <p>
                        Visit our office to complete your payment in person. Once confirmed by a manager, your tenant account will be created and login credentials will be sent to <strong style="color: #0f172a;">{{ $application->email }}</strong>.
                    </p>
                </div>
            </div>

            {{-- Application Summary --}}
            <div class="receipt-card">
                <div class="credentials-title">Application Summary</div>
                <div class="receipt-row">
                    <span class="r-label">Applicant</span>
                    <span class="r-value">{{ $application->name }}</span>
                </div>
                <div class="receipt-row">
                    <span class="r-label">Stall</span>
                    <span class="r-value">{{ $application->stall->name }}</span>
                </div>
                <div class="receipt-row">
                    <span class="r-label">Monthly Rate</span>
                    <span class="r-value">₱{{ number_format($application->stall->price, 2) }}</span>
                </div>
                <div class="receipt-row">
                    <span class="r-label">Duration</span>
                    <span class="r-value">{{ $application->rental_duration }} month(s)</span>
                </div>
                <div class="receipt-row">
                    <span class="r-label">Payment Method</span>
                    <span class="r-value">In Person</span>
                </div>
                <div class="receipt-row">
                    <span class="r-label">Status</span>
                    <span class="r-value" style="color: #d97706;">Pending Approval</span>
                </div>
                <div class="receipt-divider"></div>
                <div class="receipt-total">
                    <span class="r-label">Amount Due</span>
                    <span class="r-value">₱{{ number_format($application->stall->price, 2) }}</span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="{{ route('stalls.index') }}" class="btn-primary">
                    Browse More Stalls
                </a>
                <a href="/" class="btn-secondary">Back to Home</a>
            </div>
        @endif
    </div>
</body>
</html>
