<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports — Technomart</title><link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>.report-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px}.report-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px}.report-card h2{font-size:17px;margin-bottom:8px}.report-card p{font-size:13px;color:#64748b;min-height:42px}.report-card a{display:inline-block;margin-top:16px;padding:9px 14px;background:#4f46e5;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:700}.filters{display:flex;gap:12px;align-items:end;margin-bottom:22px}.filters label{font-size:12px;color:#64748b}.filters input{display:block;margin-top:5px;padding:9px;border:1px solid #cbd5e1;border-radius:8px}</style>
</head>
<body>
@include('partials.admin-sidebar', ['active' => 'reports'])
<main class="main"><div class="page-header"><h1>Operational Reports</h1></div>
<form class="filters" method="GET"><label>From<input type="date" name="date_from" value="{{ request('date_from') }}"></label><label>To<input type="date" name="date_to" value="{{ request('date_to') }}"></label><button type="submit" class="filter-btn">Apply dates</button></form>
<div class="report-grid">
@foreach(['rent' => ['Rent Collections','Bills, balances, payment dates and official receipts'], 'orders' => ['Supply Orders','Tenant purchases, quantities, totals and fulfilment status'], 'inventory' => ['Stock Movements','Complete stock ledger with running balances']] as $key => [$title,$description])
<section class="report-card"><h2>{{ $title }}</h2><p>{{ $description }}</p><a href="{{ route('admin.reports.download', ['report' => $key] + request()->only(['date_from','date_to'])) }}">Download CSV</a></section>
@endforeach
</div></main></body></html>
