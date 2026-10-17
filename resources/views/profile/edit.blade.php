<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .social-profile-card { padding:0; overflow:hidden; }
        .profile-cover { height:190px; position:relative; background:linear-gradient(120deg,#312e81 0%,#4f46e5 42%,#06b6d4 100%); }
        .profile-cover::after { content:''; position:absolute; inset:0; background:radial-gradient(circle at 78% 22%,rgba(255,255,255,.28),transparent 26%),radial-gradient(circle at 18% 80%,rgba(255,255,255,.15),transparent 30%); }
        .profile-header { display:flex; align-items:flex-end; gap:22px; min-height:112px; padding:0 32px 24px; border-bottom:1px solid #e2e8f0; position:relative; }
        .profile-avatar-wrap { position:relative; width:132px; height:76px; flex-shrink:0; }
        .profile-avatar { position:absolute; left:0; bottom:0; width:132px; height:132px; border:5px solid #fff; box-shadow:0 8px 24px rgba(15,23,42,.18); background:linear-gradient(135deg,#6366f1,#06b6d4); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-size:46px; font-weight:800; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; }
        .profile-camera { position:absolute; right:-3px; bottom:3px; z-index:3; width:38px; height:38px; border-radius:50%; border:3px solid #fff; display:grid; place-items:center; background:#eef2ff; color:#4338ca; cursor:pointer; box-shadow:0 3px 10px rgba(15,23,42,.2); }
        .profile-camera:hover { background:#e0e7ff; transform:translateY(-1px); }.profile-camera svg{width:18px;height:18px}
        .profile-meta { flex:1; min-width:0; padding-top:18px; }
        .profile-meta h2 { font-size:26px; font-weight:800; color:#0f172a; margin:0 0 3px; }
        .profile-meta p { font-size: 14px; color: #64748b; margin: 0; }
        .profile-handle { display:inline-flex; margin-top:8px; padding:4px 10px; border-radius:999px; background:#eef2ff; color:#4338ca; font-size:12px; font-weight:700; text-transform:capitalize; }
        .profile-body { padding:28px 32px 32px; }
        .btn-outline { padding: 8px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 500; color: #334155; background: white; cursor: pointer; transition: 0.2s; }
        .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
        .btn-danger { padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; color: white; background: #ef4444; cursor: pointer; transition: 0.2s; }
        .btn-danger:hover { background: #dc2626; }
        .profile-info-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .info-grid { display:block; padding:16px 18px; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; min-width:0; }
        .info-label { font-size: 14px; font-weight: 500; color: #64748b; }
        .info-value { font-size:15px; font-weight:650; color:#0f172a; margin-top:5px; overflow-wrap:anywhere; }
        .profile-edit-form { max-width:680px; display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .profile-edit-form .wide,.profile-edit-actions { grid-column:1/-1; }.profile-photo-input{padding:9px;background:#f8fafc}
        
        /* Toggle modes */
        .view-mode { display: block; }
        .edit-mode { display: none; }
        .is-editing .view-mode { display: none; }
        .is-editing .edit-mode { display: block; }
        @media(max-width:700px){.profile-cover{height:140px}.profile-header{align-items:center;flex-direction:column;text-align:center;padding:0 18px 22px}.profile-avatar-wrap{height:68px;margin-top:-2px}.profile-meta{padding-top:8px}.profile-header>.view-mode{width:100%}.profile-header>.view-mode .btn-outline{width:100%}.profile-body{padding:22px 18px}.profile-info-list,.profile-edit-form{grid-template-columns:1fr}.profile-edit-form .wide,.profile-edit-actions{grid-column:auto}}
    </style>
    @vite('resources/js/app.js')
</head>
<body>
    @if(auth()->user()->role === 'admin')
        @include('partials.admin-sidebar', ['active' => 'profile'])
    @elseif(auth()->user()->role === 'manager')
        @include('partials.manager-sidebar', ['active' => 'profile'])
    @elseif(auth()->user()->role === 'tenant')
        @include('partials.tenant-sidebar', ['active' => 'profile'])
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

        <div class="panel social-profile-card" id="profilePanel">
            <div class="profile-cover" aria-hidden="true"></div>
            <div class="profile-header">
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar" id="profileAvatar">
                        @if($user->profilePhotoUrl())
                            <img id="profileAvatarImage" src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }} profile photo">
                        @else
                            <span id="profileAvatarFallback">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @endif
                    </div>
                    @if($user->isAdmin())
                        <button type="button" class="profile-camera" onclick="startPhotoChange()" aria-label="Change profile photo" title="Change profile photo">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h2l2-3h6l2 3h2a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="2"/></svg>
                        </button>
                    @endif
                </div>
                <div class="profile-meta">
                    <h2>{{ $user->name }}</h2>
                    <p>{{ $user->email }}</p>
                    <span class="profile-handle">{{ $user->role }} account</span>
                </div>
                <div class="view-mode">
                    <button type="button" class="btn-outline" onclick="toggleEdit()">Edit Profile</button>
                </div>
            </div>

            <div class="profile-body">
            <!-- VIEW MODE -->
            <div class="view-mode profile-info-list">
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
                <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('patch')
                    <div class="profile-edit-form">
                        <div class="form-group wide">
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
                        <div class="form-group wide">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-input" rows="3">{{ old('address', $user->address) }}</textarea>
                        </div>
                        @if($user->isAdmin())
                            <div class="form-group wide">
                                <label class="form-label" for="profile_photo">Profile Photo</label>
                                <input id="profile_photo" type="file" name="profile_photo" class="form-input profile-photo-input" accept="image/jpeg,image/png,image/webp" onchange="previewProfilePhoto(this)">
                                <div class="detail-sub">JPG, PNG or WebP, up to 2 MB and 2000 × 2000 pixels.</div>
                            </div>
                        @endif
                        <div class="profile-edit-actions" style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn-submit">Save Changes</button>
                            <button type="button" class="btn-outline" onclick="toggleEdit()">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
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

        function startPhotoChange() {
            document.getElementById('profilePanel').classList.add('is-editing');
            document.getElementById('profile_photo').click();
        }

        function previewProfilePhoto(input) {
            const file = input.files && input.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            const avatar = document.getElementById('profileAvatar');
            let image = document.getElementById('profileAvatarImage');
            if (!image) {
                image = document.createElement('img');
                image.id = 'profileAvatarImage';
                image.alt = 'Selected profile photo preview';
                avatar.replaceChildren(image);
            }
            image.src = URL.createObjectURL(file);
        }
        
        // If there are validation errors on update, automatically open edit mode
        @if($errors->has('name') || $errors->has('email') || $errors->has('phone_number') || $errors->has('address') || $errors->has('profile_photo'))
            document.getElementById('profilePanel').classList.add('is-editing');
        @endif
    </script>
</body>
</html>
