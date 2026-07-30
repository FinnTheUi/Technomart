<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <!-- Tailwind is needed for the partials -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        <header class="header">
            <h2>Profile</h2>
            <div class="header-actions">
                <div class="user-badge">{{ auth()->user()->name }}</div>
            </div>
        </header>

        <div class="content" style="background: transparent;">
            <div class="max-w-7xl mx-auto space-y-6">
                <div class="p-4 sm:p-8 bg-white border border-slate-200 shadow-sm sm:rounded-2xl">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white border border-slate-200 shadow-sm sm:rounded-2xl">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white border border-slate-200 shadow-sm sm:rounded-2xl">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
