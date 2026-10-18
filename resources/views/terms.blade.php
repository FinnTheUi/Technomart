<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>body{background:#f8fafc;color:#334155;font-family:Arial,sans-serif}.terms{max-width:860px;margin:40px auto;padding:36px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;line-height:1.7}.terms h1,.terms h2{color:#0f172a}.terms h2{margin-top:26px;font-size:18px}.terms a{color:#4f46e5}.updated{color:#64748b;font-size:13px}</style>
    @vite('resources/js/app.js')
</head>
<body>
<main class="terms">
    <a href="{{ route('home') }}">← Back to Technomart</a>
    <h1>Terms of Service</h1>
    <p class="updated">Last updated: October 18, 2026</p>
    <p>By using Technomart Rentelligence, you agree to provide accurate account, rental, payment, and contact information and to use the platform only for legitimate stall-rental activities.</p>

    <h2>Rental applications</h2>
    <p>Submitting an application does not guarantee a lease. A manager must review the application, confirm the required first payment, and approve the requested stall before the tenant account and lease become active.</p>

    <h2>Accounts and security</h2>
    <p>You are responsible for protecting your password and promptly correcting inaccurate account information. Do not share another person's account or attempt to access records that do not belong to you.</p>

    <h2>Payments and records</h2>
    <p>Payment references are subject to verification. Official receipts, lease records, due dates, late fees, and manager-confirmed payment records shown in the platform form part of the rental administration record.</p>

    <h2>Acceptable use</h2>
    <p>You must not submit fraudulent information, malicious files, abusive messages, or attempts to disrupt or bypass the platform's security and role restrictions.</p>

    <h2>Questions</h2>
    <p>For questions about these terms, use the official contact information provided by Technomart management. See the <a href="{{ route('privacy') }}">Privacy Notice</a> for information about personal-data handling.</p>
</main>
</body>
</html>
