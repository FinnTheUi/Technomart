<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Users — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .badge-admin{background:#f5f3ff;color:#7c3aed}
        .badge-manager{background:#f0f9ff;color:#0284c7}
        .badge-tenant{background:#ecfdf5;color:#059669}
        select.role-select{background:#fff;border:1px solid #cbd5e1;color:#334155;padding:6px 10px;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;cursor:pointer;outline:none}
        select.role-select:focus{border-color:#4f46e5}
        .btn-save{padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;font-family:'Inter',sans-serif;background:#eef2ff;color:#4f46e5;border:1px solid #c7d2fe;transition:.2s}
        .btn-save:hover{background:#e0e7ff}
        .btn-delete{padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;font-family:'Inter',sans-serif;background:#fef2f2;color:#dc2626;border:1px solid #fecdd3;transition:.2s}
        .btn-delete:hover{background:#fee2e2}
        .actions-cell{display:flex;gap:8px;align-items:center}
        .create-panel{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:28px;margin-bottom:24px}
        .create-panel-title{font-size:17px;font-weight:700;color:#0f172a;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .create-panel-title svg{width:22px;height:22px;color:#4f46e5}
        .btn-create{padding:10px 22px;border-radius:10px;font-size:14px;font-weight:600;color:#fff;background:#4f46e5;border:none;cursor:pointer;font-family:'Inter',sans-serif;white-space:nowrap;align-self:flex-end;transition:.2s}
        .btn-create:hover{background:#4338ca}
    </style>
</head>
<body>
    @include('partials.admin-sidebar', ['active' => 'users'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Manage Users</h1>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <!-- Create Manager Account -->
        <div class="create-panel">
            <div class="create-panel-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Create Manager Account
            </div>
            <form method="POST" action="{{ route('admin.users.storeManager') }}">
                @csrf
                <div class="form-row-inline">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input" placeholder="Manager name" value="{{ old('name') }}" required>
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" placeholder="manager@example.com" value="{{ old('email') }}" required>
                        @error('email')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" placeholder="Min 8 characters" required>
                        @error('password')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-input" placeholder="Confirm password" required>
                    </div>
                    <button type="submit" class="btn-create">+ Create Manager</button>
                </div>
            </form>
        </div>

        <div class="panel">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Current Role</th><th>Status</th><th>Change Role</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td style="font-weight:600;color:#0f172a;">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge badge-{{ $user->role }}">{{ ucfirst($user->role) }}</span></td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-red' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                            @unless($user->hasVerifiedEmail())<div style="font-size:11px;color:#94a3b8;">email unverified</div>@endunless
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.updateRole', $user) }}" class="actions-cell" id="role-form-{{ $user->id }}">
                                @csrf @method('PATCH')
                                <select name="role" class="role-select">
                                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="manager" {{ $user->role === 'manager' ? 'selected' : '' }}>Manager</option>
                                    <option value="tenant" {{ $user->role === 'tenant' ? 'selected' : '' }}>Tenant</option>
                                </select>
                                <button type="submit" class="btn-save">Save</button>
                            </form>
                        </td>
                        <td>
                            @if($user->id !== auth()->id())
                            <div style="display:flex;gap:6px;">
                            @unless($user->hasVerifiedEmail())
                            <form method="POST" action="{{ route('admin.users.resendVerification', $user) }}">
                                @csrf
                                <button type="submit" class="btn-save">Resend verification</button>
                            </form>
                            @endunless
                            <form method="POST" action="{{ route('admin.users.toggleActive', $user) }}" onsubmit="return confirm('{{ $user->is_active ? 'Deactivate' : 'Reactivate' }} {{ $user->name }}?{{ $user->is_active ? ' Active leases will be terminated.' : '' }}');">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn-save">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.invalidateSessions', $user) }}" onsubmit="return confirm('Sign {{ $user->name }} out on all devices?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-save">Sign out devices</button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.delete', $user) }}" onsubmit="return confirm('Delete {{ $user->name }}? Accounts with billing or lease history are deactivated instead, so records are kept.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                            </div>
                            @else
                            <span style="font-size:12px;color:#94a3b8;">You</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
