<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Floor Plan — Technomart</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    @vite('resources/js/app.js')
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'floor-plan'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Floor Plan</h1>
                <p>Visual layout of all stalls — click any stall for details</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Interactive Floor Plan Map --}}
        @include('partials.floor-plan-map', ['floor1Stalls' => $floor1Stalls, 'floor2Stalls' => $floor2Stalls, 'interactive' => true])

        {{-- Stall Summary Table --}}
        <div class="panel" id="stall-directory" style="margin-top: 24px;">
            <h3 class="panel-title" style="margin-bottom: 20px;">Stall Directory</h3>
            <table class="data-table">
                <thead><tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Floor</th>
                    <th>Location</th>
                    <th>Size</th>
                    <th>Price</th>
                    <th>Status</th>
                </tr></thead>
                <tbody>
                @foreach($directoryStalls as $stall)
                <tr data-directory-stall="{{ $stall->name }}">
                    <td style="color:#94a3b8;font-weight:600;">{{ $stall->stall_number ?? '—' }}</td>
                    <td style="font-weight:600;color:#0f172a;">{{ $stall->name }}</td>
                    <td>
                        @if($stall->floor_number == 2)
                            <span class="badge badge-purple" style="background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;">Floor 2</span>
                        @else
                            <span class="badge badge-blue" style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;">Floor 1</span>
                        @endif
                    </td>
                    <td>{{ $stall->location ?? '—' }}</td>
                    <td>{{ $stall->size ?? '—' }}</td>
                    <td style="font-weight:600;">₱{{ number_format($stall->price,2) }}</td>
                    <td><x-status-badge :status="$stall->status" /></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            {{ $directoryStalls->links('partials.pagination') }}
        </div>
    </div>
</body>
</html>
