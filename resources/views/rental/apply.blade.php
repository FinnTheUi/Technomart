<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rental Application — {{ $stall->name }} — Tecnomart</title>
    <meta name="description" content="Apply to rent {{ $stall->name }} on Tecnomart.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }

        /* Nav */
        .site-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; padding: 0 24px; background: rgba(255,255,255,0.9); backdrop-filter: blur(20px); border-bottom: 1px solid #e2e8f0; }
        .nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 72px; }
        .nav-brand { display: flex; align-items: center; gap: 12px; }
        .nav-brand svg { width: 36px; height: 36px; }
        .nav-brand span { font-weight: 800; font-size: 18px; color: #0f172a; }
        .nav-back { font-size: 14px; font-weight: 600; color: #475569; padding: 8px 16px; border-radius: 8px; transition: 0.2s; display: flex; align-items: center; gap: 6px; }
        .nav-back:hover { color: #0f172a; background: #f1f5f9; }
        .nav-back svg { width: 16px; height: 16px; }

        /* Form Container */
        .form-container { position: relative; z-index: 1; max-width: 680px; margin: 0 auto; padding: 120px 24px 80px; }

        /* Stall Summary Card */
        .stall-summary { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .stall-summary-left { display: flex; align-items: center; gap: 16px; }
        .stall-summary-icon { width: 48px; height: 48px; border-radius: 14px; background: #d1fae5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #059669; }
        .stall-summary-icon svg { width: 24px; height: 24px; }
        .stall-summary-name { font-size: 18px; font-weight: 700; color: #0f172a; }
        .stall-summary-loc { font-size: 13px; color: #64748b; margin-top: 2px; }
        .stall-summary-price { font-size: 24px; font-weight: 800; color: #059669; }
        .stall-summary-period { font-size: 11px; color: #64748b; text-align: right; font-weight: 500; }

        /* Form Header */
        .form-header { margin-bottom: 32px; }
        .form-header h1 { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .form-header p { font-size: 14px; color: #475569; line-height: 1.6; }

        /* Form Card */
        .form-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 36px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 13px 16px; border-radius: 10px; background: #fff; border: 1px solid #cbd5e1; color: #0f172a; font-size: 15px; font-family: 'Inter', sans-serif; transition: border-color 0.2s, box-shadow 0.2s; outline: none; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
        .form-input::placeholder, .form-textarea::placeholder { color: #94a3b8; }
        .form-textarea { min-height: 80px; resize: vertical; }
        .form-select { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%2364748b' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; background-size: 16px; padding-right: 40px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        /* Checkbox */
        .form-checkbox { display: flex; align-items: flex-start; gap: 12px; cursor: pointer; padding: 16px; border-radius: 12px; background: #fffbeb; border: 1px solid #fde68a; transition: border-color 0.2s; }
        .form-checkbox:hover { border-color: #f59e0b; }
        .form-checkbox input[type="checkbox"] { width: 20px; height: 20px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; accent-color: #f59e0b; flex-shrink: 0; margin-top: 2px; }
        .form-checkbox .cb-text { font-size: 13px; color: #475569; line-height: 1.5; }
        .form-checkbox .cb-text strong { color: #d97706; }

        /* Error */
        .form-error { font-size: 12px; color: #ef4444; margin-top: 6px; font-weight: 500; }
        .error-box { padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; background: #fef2f2; border: 1px solid #fecaca; }
        .error-box ul { list-style: none; }
        .error-box li { font-size: 13px; color: #ef4444; padding: 3px 0; font-weight: 500; }
        .error-box li::before { content: "• "; }

        /* Submit Button */
        .btn-submit { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; font-size: 16px; font-weight: 700; color: #fff; padding: 16px 32px; border-radius: 10px; background: #4f46e5; transition: 0.2s; border: none; cursor: pointer; font-family: 'Inter', sans-serif; margin-top: 8px; }
        .btn-submit:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79,70,229,0.3); }
        .btn-submit svg { width: 18px; height: 18px; }

        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .stall-summary { flex-direction: column; align-items: flex-start; }
            .nav-brand span { display: none; }
        }
    </style>
</head>
<body>
    <header class="site-nav">
        <div class="nav-inner">
            <a href="/" class="nav-brand">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#6366F1"/><stop offset="100%" stop-color="#06B6D4"/></linearGradient></defs><rect width="100" height="100" rx="22" fill="url(#logoGrad)"/><path d="M 28 30 L 72 30" stroke="white" stroke-width="8" stroke-linecap="round"/><path d="M 50 30 L 50 74" stroke="white" stroke-width="8" stroke-linecap="round"/><circle cx="50" cy="26" r="4" fill="rgba(255,255,255,0.4)"/></svg>
                <span>Tecnomart</span>
            </a>
            <a href="{{ route('stalls.index') }}" class="nav-back">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                Back to Stalls
            </a>
        </div>
    </header>

    <div class="form-container">
        <!-- Stall Summary -->
        <div class="stall-summary">
            <div class="stall-summary-left">
                <div class="stall-summary-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <div class="stall-summary-name">{{ $stall->name }}</div>
                    <div class="stall-summary-loc">{{ $stall->location ?? 'No location specified' }}{{ $stall->size ? ' • ' . $stall->size : '' }}</div>
                </div>
            </div>
            <div style="text-align: right;">
                <div class="stall-summary-price">₱{{ number_format($stall->price, 2) }}</div>
                <div class="stall-summary-period">per month</div>
            </div>
        </div>

        <!-- Form Header -->
        <div class="form-header">
            <h1>Rental Application</h1>
            <p>Fill out the form below to apply for this stall. All fields are required.</p>
        </div>

        <!-- Validation Errors -->
        @if($errors->any())
            <div class="error-box">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <div class="form-card">
            <form method="POST" action="{{ route('stalls.submitApplication', $stall) }}" id="rental-form">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" class="form-input" id="name" name="name" placeholder="Juan Dela Cruz" value="{{ old('name') }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <textarea class="form-textarea" id="address" name="address" placeholder="Your complete address" required>{{ old('address') }}</textarea>
                    @error('address') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-input" id="email" name="email" placeholder="you@email.com" value="{{ old('email') }}" required>
                        @error('email') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="text" class="form-input" id="phone" name="phone" placeholder="09XX XXX XXXX" value="{{ old('phone') }}" required>
                        @error('phone') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="rental_duration">Rental Duration</label>
                    <select class="form-select" id="rental_duration" name="rental_duration" required>
                        <option value="" disabled {{ old('rental_duration') ? '' : 'selected' }}>Select number of months</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ old('rental_duration') == $i ? 'selected' : '' }}>{{ $i }} {{ $i === 1 ? 'month' : 'months' }} — ₱{{ number_format($stall->price * $i, 2) }}</option>
                        @endfor
                    </select>
                    @error('rental_duration') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-checkbox" for="terms">
                        <input type="checkbox" id="terms" name="terms" value="1" {{ old('terms') ? 'checked' : '' }}>
                        <div class="cb-text">
                            I agree to the <strong>Terms and Conditions</strong> of Tecnomart, including the rental policy, payment terms, and stall usage guidelines. I understand that providing false information may result in application denial.
                        </div>
                    </label>
                    @error('terms') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn-submit" id="btn-proceed-to-payment">
                    Proceed to Payment
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
