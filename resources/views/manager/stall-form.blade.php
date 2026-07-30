<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Stall — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .panel { max-width: 600px; }
    </style>
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
                <h1>Add New Stall</h1>
            </div>
        </div>
        <div class="panel">
            <form method="POST" action="{{ route('manager.stalls.store') }}">@csrf
                <div class="form-group">
                    <label class="form-label">Stall Name</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Stall A-01" required>
                    @error('name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-input" placeholder="e.g. Building A, Floor 1">
                        @error('location')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Size</label>
                        <input type="text" name="size" class="form-input" placeholder="e.g. 3x3 meters">
                        @error('size')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Monthly Rent (₱)</label>
                    <input type="number" name="price" class="form-input" step="0.01" min="0" placeholder="0.00" required>
                    @error('price')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn-submit">Create Stall</button>
            </form>
        </div>
    </div>
</body>
</html>
