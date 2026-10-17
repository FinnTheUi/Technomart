<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $stall->name }} — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .panel { max-width: 640px; }
        .fixed-info { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .fixed-info div { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; font-size: 13px; color: #64748b; }
        .fixed-info strong { display: block; color: #0f172a; font-size: 15px; }
        .hint { font-size: 12px; color: #64748b; margin-top: 4px; }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'stalls'])

    <div class="main">
        <a href="{{ route('manager.stalls') }}" class="back-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Stalls
        </a>
        <div class="page-header">
            <div>
                <h1>{{ $stall->name }}</h1>
            </div>
            <x-status-badge :status="$stall->status" />
        </div>

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="panel">
            <div class="fixed-info">
                <div>Floor<strong>{{ $stall->floor_number }}</strong></div>
                <div>Number<strong>{{ $stall->stall_number }}</strong></div>
                <div>Name<strong>{{ $stall->name }}</strong></div>
            </div>
            <p class="hint" style="margin-bottom:20px;">The name follows the stall's position on the floor plan and can't be changed.</p>

            <form method="POST" action="{{ route('manager.stalls.update', $stall) }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">Monthly price (₱)</label>
                    <input type="number" name="price" class="form-input" step="0.01" min="0" value="{{ old('price', $stall->price) }}" required>
                    <div class="hint">Applies to new leases and renewals. Existing rent bills keep their amounts.</div>
                    @error('price') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Size</label>
                        <input type="text" name="size" class="form-input" value="{{ old('size', $stall->size) }}" placeholder="e.g. 3m × 3m">
                        @error('size') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location note</label>
                        <input type="text" name="location" class="form-input" value="{{ old('location', $stall->location) }}" placeholder="e.g. Near entrance">
                        @error('location') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                @if($locations->isNotEmpty())
                <div class="form-group">
                    <label class="form-label">Branch</label>
                    <select name="location_id" class="form-input">
                        <option value="">—</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('location_id', $stall->location_id) == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <button type="submit" class="btn-submit">Save changes</button>
            </form>
        </div>

        <div class="panel">
            <h3 class="panel-title" style="margin-bottom:12px;">Tenant</h3>
            @if($stall->activeContract)
                <p style="font-size:14px;color:#0f172a;font-weight:600;">{{ $stall->activeContract->tenant->name ?? '—' }}</p>
                <p class="hint">Lease until {{ $stall->activeContract->end_date->format('M d, Y') }}. Renew or end it on the <a href="{{ route('manager.contracts.index') }}" style="color:#4f46e5;">Leases</a> page.</p>
            @elseif($stall->status === \App\Enums\StallStatus::Maintenance)
                <p class="hint">This stall is under maintenance. Set it back to available before assigning a tenant.</p>
            @else
                <p class="hint" style="margin-bottom:14px;">Place a registered tenant in this stall (for example, a walk-in agreement). A lease and monthly rent bills at ₱{{ number_format($stall->price, 2) }} are created, and the stall shows as occupied immediately.</p>
                @if($errors->has('tenant_id') || $errors->has('months'))
                    <div class="alert alert-error">{{ $errors->first('tenant_id') ?: $errors->first('months') }}</div>
                @endif
                <form method="POST" action="{{ route('manager.stalls.assignTenant', $stall) }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tenant</label>
                            <select name="tenant_id" class="form-input" required>
                                <option value="">Select a tenant…</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>{{ $tenant->name }} ({{ $tenant->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lease length</label>
                            <select name="months" class="form-input">
                                @foreach([1, 3, 6, 12] as $m)<option value="{{ $m }}">{{ $m }} month(s)</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" onclick="return confirm('Create a lease for this tenant?')">Assign tenant</button>
                </form>
            @endif
        </div>
    </div>
</body>
</html>
