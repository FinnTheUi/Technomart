<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Requests — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .two-col { display: grid; grid-template-columns: 350px 1fr; gap: 24px; }
        @media(max-width:1024px) { .two-col { grid-template-columns: 1fr; } }
        
        .request-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px; transition: border-color .2s; }
        .request-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .request-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; gap: 12px; }
        .request-subject { font-size: 15px; font-weight: 600; color: #0f172a; }
        .request-meta { font-size: 13px; color: #64748b; margin-top: 4px; }
        .request-desc { font-size: 14px; color: #334155; line-height: 1.5; }
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.tenant-sidebar', ['active' => 'maintenance'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Maintenance Requests</h1>
            </div>
        </div>
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="two-col">
            <!-- Submit Form -->
            <div class="panel">
                <h3 class="panel-title" style="margin-bottom:20px;">Submit New Request</h3>
                <form method="POST" action="{{ route('tenant.maintenance.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Select Stall</label>
                        <select name="stall_id" class="form-select" required>
                            @foreach($stalls as $stall)<option value="{{ $stall->id }}">{{ $stall->name }}</option>@endforeach
                        </select>
                        @error('stall_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-input" placeholder="E.g. Leaking pipe, Electrical issue" required>
                        @error('subject')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority Level</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">Low (Cosmetic/Minor)</option>
                            <option value="medium" selected>Medium (Standard issue)</option>
                            <option value="high">High (Impacting business)</option>
                            <option value="urgent">Urgent (Safety/Security risk)</option>
                        </select>
                        @error('priority')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea" placeholder="Please describe the issue in detail..." required></textarea>
                        @error('description')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo (Optional)</label>
                        <input type="file" name="image" class="form-input" accept="image/*" style="padding:8px 12px;">
                        @error('image')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn-submit">Submit Request</button>
                </form>
            </div>

            <!-- History -->
            <div class="panel">
                <h3 class="panel-title" style="margin-bottom:20px;">My Requests</h3>
                @if($requests->count() > 0)
                    @foreach($requests as $req)
                    <div class="request-card">
                        <div class="request-header">
                            <div>
                                <div class="request-subject"><a href="{{ route('tenant.maintenance.detail', $req) }}" style="text-decoration:none;color:#0f172a;">{{ $req->subject }}</a></div>
                                <div class="request-meta">{{ $req->stall->name ?? '' }} · Submitted {{ $req->created_at->diffForHumans() }}</div>
                            </div>
                            <div style="display:flex;gap:6px;flex-direction:column;align-items:flex-end;">
                                <span class="badge {{ $req->priority==='urgent'?'badge-red':($req->priority==='high'?'badge-yellow':($req->priority==='medium'?'badge-blue':'badge-green')) }}">{{ ucfirst($req->priority) }} Priority</span>
                                <x-status-badge :status="$req->status" />
                            </div>
                        </div>
                        <div class="request-desc">{{ Str::limit($req->description, 100) }}</div>
                    </div>
                    @endforeach
                @else
                <div style="text-align:center;padding:40px 20px;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="40" height="40" style="color:#94a3b8;margin-bottom:12px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p style="color:#64748b;font-size:14px;">No maintenance requests submitted.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
