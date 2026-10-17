<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #e2e8f0; }
        .profile-avatar { width: 80px; height: 80px; background: linear-gradient(135deg, #6366f1, #06b6d4); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: bold; }
        .profile-meta h2 { font-size: 24px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
        .profile-meta p { font-size: 14px; color: #64748b; margin: 0; }
        .btn-outline { padding: 8px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 500; color: #334155; background: white; cursor: pointer; transition: 0.2s; }
        .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
        .btn-danger { padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; color: white; background: #ef4444; cursor: pointer; transition: 0.2s; }
        .btn-danger:hover { background: #dc2626; }
        .info-grid { display: grid; grid-template-columns: 150px 1fr; gap: 16px; align-items: center; margin-bottom: 16px; }
        .info-label { font-size: 14px; font-weight: 500; color: #64748b; }
        .info-value { font-size: 15px; font-weight: 600; color: #0f172a; }
        
        /* Toggle modes */
        .view-mode { display: block; }
        .edit-mode { display: none; }
        .is-editing .view-mode { display: none; }
        .is-editing .edit-mode { display: block; }
    </style>
</head>
<body>
    @if(auth()->user()->role === 'admin')
        @include('partials.admin-sidebar', ['active' => ''])
    @elseif(auth()->user()->role === 'manager')
        @include('partials.manager-sidebar', ['active' => ''])
    @elseif(auth()->user()->role === 'tenant')
        @include('partials.tenant-sidebar', ['active' => ''])
    @endif

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Account Profile</h1>
                <p>Manage your account settings and preferences</p>
            </div>
        </div>

        @if(session('status') === 'profile-updated')
            <div class="alert alert-success">Profile updated successfully.</div>
        @endif
        @if(session('status') === 'password-updated')
            <div class="alert alert-success">Password updated successfully.</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <ul style="margin:0; padding-left:20px;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="panel" id="profilePanel">
            <div class="profile-header">
                <div class="profile-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div class="profile-meta" style="flex: 1;">
                    <h2>{{ $user->name }}</h2>
                    <p>{{ ucfirst($user->role) }} Account</p>
                </div>
                <div class="view-mode">
                    <button type="button" class="btn-outline" onclick="toggleEdit()">Edit Profile</button>
                </div>
            </div>

            <!-- VIEW MODE -->
            <div class="view-mode">
                <div class="info-grid">
                    <div class="info-label">Full Name</div>
                    <div class="info-value">{{ $user->name }}</div>
                </div>
                <div class="info-grid">
                    <div class="info-label">Email Address</div>
                    <div class="info-value">{{ $user->email }}</div>
                </div>
                <div class="info-grid">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value">{{ $user->phone_number ?: 'Not provided' }}</div>
                </div>
                <div class="info-grid">
                    <div class="info-label">Address</div>
                    <div class="info-value">{{ $user->address ?: 'Not provided' }}</div>
                </div>
            </div>

            <!-- EDIT MODE -->
            <div class="edit-mode">
                <form method="post" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')
                    <div style="max-width: 500px; display: flex; flex-direction: column; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone_number" class="form-input" value="{{ old('phone_number', $user->phone_number) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-input" rows="3">{{ old('address', $user->address) }}</textarea>
                        </div>
                        <div style="display: flex; gap: 12px; margin-top: 8px;">
                            <button type="submit" class="btn-submit">Save Changes</button>
                            <button type="button" class="btn-outline" onclick="toggleEdit()">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- CHANGE PASSWORD -->
        <div class="panel" style="margin-top: 24px;">
            <h3 class="panel-title" style="margin-bottom: 20px;">Change Password</h3>
            <form method="post" action="{{ route('password.update') }}">
                @csrf
                @method('put')
                <div style="max-width: 500px; display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-input" required>
                    </div>
                    <div style="margin-top: 8px;">
                        <button type="submit" class="btn-submit" style="background: #0f172a;">Update Password</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- DISABLE ACCOUNT -->
        <div class="panel" style="margin-top: 24px; border: 1px solid #fecaca; background: #fef2f2;">
            <h3 class="panel-title" style="margin-bottom: 8px; color: #dc2626;">Disable Account</h3>
            <p style="color: #991b1b; font-size: 14px; margin-bottom: 20px;">
                Disabling your account will log you out immediately. Your data will be retained, but you will not be able to log back in until an administrator reactivates your account.
            </p>
            <form method="post" action="{{ route('profile.disable') }}" onsubmit="return confirm('Are you sure you want to disable your account? You will be logged out.');">
                @csrf
                @method('delete')
                <div style="max-width: 500px; display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" style="color: #991b1b;">Confirm Password to Disable</label>
                        <input type="password" name="password" class="form-input" placeholder="Password" required style="border-color: #fca5a5;">
                    </div>
                    <div>
                        <button type="submit" class="btn-danger">Disable Account</button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    <script>
        function toggleEdit() {
            document.getElementById('profilePanel').classList.toggle('is-editing');
        }
        
        // If there are validation errors on update, automatically open edit mode
        @if($errors->has('name') || $errors->has('email') || $errors->has('phone_number') || $errors->has('address'))
            document.getElementById('profilePanel').classList.add('is-editing');
        @endif
    </script>
</body>
</html>
