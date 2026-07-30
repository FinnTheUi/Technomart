<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tecnomart — Smart Stall Rental Platform</title>
    <meta name="description" content="Tecnomart is the modern platform for stall rental management. Real-time analytics, automated billing, and predictive insights for managers and tenants.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }

        /* ===== NAV ===== */
        .site-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; padding: 0 24px; background: rgba(255,255,255,0.9); backdrop-filter: blur(20px); border-bottom: 1px solid #e2e8f0; transition: background 0.3s; }
        .nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 72px; }
        .nav-brand { display: flex; align-items: center; gap: 12px; }
        .nav-brand svg { width: 36px; height: 36px; flex-shrink: 0; }
        .nav-brand span { font-weight: 800; font-size: 18px; letter-spacing: -0.02em; color: #0f172a; }
        .nav-links { display: flex; align-items: center; gap: 12px; }
        .nav-link { font-size: 14px; font-weight: 600; color: #475569; padding: 8px 16px; border-radius: 8px; transition: color 0.2s, background 0.2s; }
        .nav-link:hover { color: #0f172a; background: #f1f5f9; }
        .nav-cta { font-size: 14px; font-weight: 600; color: #fff; padding: 9px 20px; border-radius: 10px; background: #4f46e5; transition: 0.2s; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
        .nav-cta:hover { transform: translateY(-1px); background: #4338ca; }

        /* ===== HERO ===== */
        .hero { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 120px 24px 80px; text-align: center; }
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px 6px 12px; border-radius: 999px; background: #e0e7ff; border: 1px solid #c7d2fe; color: #4338ca; font-size: 13px; font-weight: 600; margin-bottom: 32px; animation: fadeUp 0.8s ease-out; }
        .hero-badge .dot { width: 8px; height: 8px; border-radius: 50%; background: #4f46e5; animation: pulse-dot 2s ease-in-out infinite; }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.7); } }
        .hero h1 { font-size: clamp(2.5rem, 6vw, 4.5rem); font-weight: 900; line-height: 1.08; letter-spacing: -0.03em; color: #0f172a; max-width: 800px; margin-bottom: 24px; animation: fadeUp 0.8s ease-out 0.1s both; }
        .hero h1 .gradient-text { color: #4f46e5; }
        .hero .subtitle { font-size: clamp(1rem, 2vw, 1.25rem); color: #475569; line-height: 1.7; max-width: 600px; margin-bottom: 40px; animation: fadeUp 0.8s ease-out 0.2s both; }
        .hero-buttons { display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; animation: fadeUp 0.8s ease-out 0.3s both; }
        .btn-primary { display: inline-flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 600; color: #fff; padding: 14px 32px; border-radius: 12px; background: #4f46e5; transition: 0.2s; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); background: #4338ca; box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4); }
        .btn-primary svg { width: 18px; height: 18px; }
        .btn-secondary { display: inline-flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 600; color: #475569; padding: 14px 32px; border-radius: 12px; background: #fff; border: 1px solid #cbd5e1; transition: 0.2s; cursor: pointer; }
        .btn-secondary:hover { transform: translateY(-2px); color: #0f172a; border-color: #94a3b8; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        /* ===== FEATURES ===== */
        .features { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 0 24px 100px; }
        .features-header { text-align: center; margin-bottom: 56px; }
        .features-header .label { display: inline-block; font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #4f46e5; margin-bottom: 12px; }
        .features-header h2 { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; color: #0f172a; letter-spacing: -0.025em; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .feature-card { position: relative; overflow: hidden; padding: 36px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .feature-card:hover { transform: translateY(-4px); border-color: #cbd5e1; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        .feature-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; transition: transform 0.4s; }
        .feature-card:hover .feature-icon { transform: scale(1.1); }
        .feature-icon svg { width: 24px; height: 24px; }
        .feature-icon.indigo { background: #e0e7ff; color: #4f46e5; border: 1px solid #c7d2fe; }
        .feature-icon.emerald { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        .feature-icon.sky { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .feature-icon.amber { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .feature-card h3 { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 10px; letter-spacing: -0.01em; }
        .feature-card p { font-size: 14px; color: #475569; line-height: 1.7; }

        /* ===== STATS BAR ===== */
        .stats-bar { position: relative; z-index: 1; max-width: 900px; margin: -40px auto 80px; padding: 32px 40px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 24px; animation: fadeUp 0.8s ease-out 0.4s both; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .stat { text-align: center; }
        .stat .number { font-size: 32px; font-weight: 800; letter-spacing: -0.03em; color: #059669; }
        .stat .label { font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

        /* ===== CTA SECTION ===== */
        .cta-section { position: relative; z-index: 1; max-width: 800px; margin: 0 auto; padding: 0 24px 100px; text-align: center; }
        .cta-section h2 { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; color: #0f172a; letter-spacing: -0.025em; margin-bottom: 16px; }
        .cta-section p { font-size: 16px; color: #475569; line-height: 1.7; margin-bottom: 32px; }

        /* ===== FOOTER ===== */
        .site-footer { position: relative; z-index: 1; border-top: 1px solid #e2e8f0; padding: 32px 24px; background: #fff; }
        .footer-inner { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
        .footer-inner .copy { font-size: 13px; color: #64748b; font-weight: 500; }
        .footer-links { display: flex; gap: 24px; }
        .footer-links a { font-size: 13px; color: #475569; font-weight: 500; transition: color 0.2s; }
        .footer-links a:hover { color: #0f172a; }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 640px) {
            .hero-buttons { flex-direction: column; width: 100%; max-width: 340px; }
            .btn-primary, .btn-secondary { justify-content: center; width: 100%; }
            .stats-bar { flex-direction: column; align-items: center; }
            .nav-brand span { display: none; }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <header class="site-nav">
        <div class="nav-inner">
            <a href="/" class="nav-brand">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6366F1" />
                            <stop offset="100%" stop-color="#06B6D4" />
                        </linearGradient>
                    </defs>
                    <rect width="100" height="100" rx="22" fill="url(#logoGrad)" />
                    <path d="M 28 30 L 72 30" stroke="white" stroke-width="8" stroke-linecap="round" />
                    <path d="M 50 30 L 50 74" stroke="white" stroke-width="8" stroke-linecap="round" />
                    <circle cx="50" cy="26" r="4" fill="rgba(255,255,255,0.4)" />
                </svg>
                <span>Tecnomart</span>
            </a>
            <nav class="nav-links">
                @auth
                    <a href="{{ url('/dashboard') }}" class="nav-cta">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="nav-link">Sign In</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="nav-cta">Get Started Free</a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">

        <h1>
            Manage stall rentals with<br>
            <span class="gradient-text">intelligent precision.</span>
        </h1>

        <p class="subtitle">
            Connect managers and tenants on one seamless platform. Automate billing, monitor leases in real time, and leverage predictive analytics to stay ahead.
        </p>

        <div class="hero-buttons">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary">
                    Enter Dashboard
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            @else
                <a href="{{ route('stalls.index') }}" class="btn-primary">
                    Rent a Stall
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="{{ route('login') }}" class="btn-secondary">Sign In</a>
            @endauth
        </div>
    </section>


    <!-- Features -->
    <section class="features">
        <div class="features-header">
            <div class="label">Platform Features</div>
            <h2>Everything you need to run your rental business</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon indigo">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <h3>Predictive Analytics</h3>
                <p>AI-powered algorithms detect late-payment patterns early, so you can send reminders and protect your cash flow before issues arise.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon emerald">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3>Automated Billing</h3>
                <p>Process rent payments effortlessly and generate professional digital receipts instantly for every completed transaction.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon sky">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h3>Stall Management</h3>
                <p>Track availability, occupancy, and lease terms across all your properties from a single, real-time dashboard view.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon amber">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <h3>Role-based Access</h3>
                <p>Secure, dedicated dashboards for Admins, Stall Managers, and Tenants — each with exactly the tools they need.</p>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <h2>Ready to modernize your rental operations?</h2>
        <p>Join Tecnomart today and experience the future of stall management — smarter payments, happier tenants, zero hassle.</p>
        <div class="hero-buttons">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary">Go to Dashboard</a>
            @else
                <a href="{{ route('stalls.index') }}" class="btn-primary">
                    Browse Available Stalls
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            @endauth
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-inner">
            <span class="copy">&copy; {{ date('Y') }} Tecnomart. All rights reserved.</span>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </footer>

</body>
</html>
