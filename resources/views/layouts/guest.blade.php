<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Technomart') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/js/app.js'])
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
            a { color: inherit; text-decoration: none; }

            /* Container */
            .auth-wrapper { position: relative; z-index: 1; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 24px; }

            /* Logo */
            .auth-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; }
            .auth-logo svg { width: 44px; height: 44px; }
            .auth-logo span { font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }

            /* Card */
            .auth-card { width: 100%; max-width: 440px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 40px 36px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01); animation: cardIn 0.4s ease-out; }
            @keyframes cardIn { from { opacity: 0; transform: translateY(10px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }

            .auth-card h2 { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 8px; letter-spacing: -0.02em; }
            .auth-card .auth-subtitle { font-size: 14px; color: #64748b; margin-bottom: 28px; }

            /* Form Elements */
            .form-group { margin-bottom: 20px; }
            .form-label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px; }
            .form-input { width: 100%; padding: 12px 16px; font-size: 15px; font-family: 'Inter', sans-serif; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; color: #0f172a; transition: border-color 0.2s, box-shadow 0.2s; outline: none; }
            .form-input::placeholder { color: #94a3b8; }
            .form-input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); background: #fff; }

            /* Password wrapper */
            .password-wrapper { position: relative; }
            .password-wrapper .form-input { padding-right: 48px; }
            .password-toggle { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; display: flex; align-items: center; justify-content: center; transition: color 0.2s; }
            .password-toggle:hover { color: #4f46e5; }
            .password-toggle svg { width: 20px; height: 20px; }

            /* Checkbox */
            .form-check { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
            .form-check input[type="checkbox"] { width: 18px; height: 18px; border-radius: 5px; background: #fff; border: 1px solid #cbd5e1; accent-color: #4f46e5; cursor: pointer; }
            .form-check label { font-size: 14px; color: #475569; cursor: pointer; }

            /* Buttons */
            .btn-submit { width: 100%; padding: 13px 24px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700; color: #fff; background: #4f46e5; border: none; border-radius: 12px; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); transition: 0.2s; }
            .btn-submit:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.3); }

            /* Links */
            .auth-links { display: flex; align-items: center; justify-content: space-between; margin-top: 20px; flex-wrap: wrap; gap: 8px; }
            .auth-link { font-size: 13px; color: #4f46e5; transition: color 0.2s; font-weight: 500; }
            .auth-link:hover { color: #4338ca; text-decoration: underline; }
            .auth-link strong { font-weight: 700; }

            /* Google sign-in */
            .btn-google { width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 24px; font-size: 15px; font-weight: 600; color: #0f172a; background: #fff; border: 1px solid #cbd5e1; border-radius: 12px; transition: 0.2s; }
            .btn-google:hover { background: #f8fafc; border-color: #94a3b8; }
            .btn-google svg { width: 20px; height: 20px; }
            .auth-divider { display: flex; align-items: center; gap: 12px; margin: 22px 0; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
            .auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }

            /* Error */
            .form-error { font-size: 13px; color: #ef4444; margin-top: 6px; }
            .form-error-block { padding: 12px 16px; margin: 0 0 20px; border-radius: 12px; background: #fef2f2; border: 1px solid #fecaca; }

            /* Session status */
            .session-status { padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; background: #d1fae5; border: 1px solid #a7f3d0; color: #059669; font-size: 14px; font-weight: 500; }

            @media (max-width: 480px) { .auth-card { padding: 32px 24px; } }
        </style>
    </head>
    <body>
        <div class="auth-wrapper">
            <a href="/" class="auth-logo">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="authGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6366F1" />
                            <stop offset="100%" stop-color="#06B6D4" />
                        </linearGradient>
                    </defs>
                    <rect width="100" height="100" rx="22" fill="url(#authGrad)" />
                    <path d="M 28 30 L 72 30" stroke="white" stroke-width="8" stroke-linecap="round" />
                    <path d="M 50 30 L 50 74" stroke="white" stroke-width="8" stroke-linecap="round" />
                    <circle cx="50" cy="26" r="4" fill="rgba(255,255,255,0.4)" />
                </svg>
                <span>Technomart</span>
            </a>

            <div class="auth-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
