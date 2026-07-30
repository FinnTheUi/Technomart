<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Floor Plan — Tecnomart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <style>
        .floor-plan-container{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;position:relative;min-height:600px;box-shadow:0 1px 3px rgba(0,0,0,0.02)}
        .floor-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:12px;margin-top:20px}
        .stall-tile{aspect-ratio:1;border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;cursor:pointer;transition:all .3s;border:2px solid transparent;position:relative;padding:8px}
        .stall-tile:hover{transform:scale(1.05);z-index:2;box-shadow:0 10px 25px rgba(0,0,0,0.1)}
        .stall-tile.available{background:#ecfdf5;border-color:#a7f3d0;color:#059669}
        .stall-tile.occupied{background:#f0f9ff;border-color:#bae6fd;color:#0284c7}
        .stall-tile.maintenance{background:#fffbeb;border-color:#fde68a;color:#d97706}
        .stall-tile .stall-name{font-size:13px;font-weight:700;color:#0f172a;text-align:center;line-height:1.2}
        .stall-tile .stall-status{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
        .stall-tile .stall-price{font-size:11px;color:#64748b}
        .empty-slot{aspect-ratio:1;border-radius:12px;border:2px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:11px;background:#f8fafc}
        .legend{display:flex;gap:24px;margin-bottom:20px}
        .legend-item{display:flex;align-items:center;gap:8px;font-size:13px;color:#475569;font-weight:500}
        .legend-dot{width:12px;height:12px;border-radius:4px}
        
        /* Popover */
        .popover{display:none;position:fixed;z-index:100;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;min-width:260px;box-shadow:0 20px 40px rgba(0,0,0,0.1)}
        .popover.show{display:block}
        .popover h3{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:12px}
        .popover .info-row{display:flex;justify-content:space-between;padding:8px 0;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9}
        .popover .info-row:last-child{border-bottom:none}
        .popover .info-row span:last-child{color:#0f172a;font-weight:600}

        @media(max-width:1200px){.floor-grid{grid-template-columns:repeat(4,1fr)}}
        @media(max-width:768px){.floor-grid{grid-template-columns:repeat(3,1fr)}}
    </style>
</head>
<body>
    @include('partials.manager-sidebar', ['active' => 'floor-plan'])

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Floor Plan</h1>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="floor-plan-container">
            <div class="legend">
                <div class="legend-item"><div class="legend-dot" style="background:#10b981"></div> Available</div>
                <div class="legend-item"><div class="legend-dot" style="background:#0ea5e9"></div> Occupied</div>
                <div class="legend-item"><div class="legend-dot" style="background:#f59e0b"></div> Maintenance</div>
                <div style="margin-left:auto;font-size:13px;color:#64748b;font-weight:500;">{{ $stalls->count() }} stalls total</div>
            </div>

            <div class="floor-grid">
                @foreach($stalls as $stall)
                <div class="stall-tile {{ $stall->status }}" onclick="showPopover(event, {{ $stall->id }})" data-stall="{{ json_encode($stall) }}">
                    <div class="stall-name">{{ $stall->floor_label ?? $stall->name }}</div>
                    <div class="stall-status">{{ $stall->status }}</div>
                    <div class="stall-price">₱{{ number_format($stall->price,0) }}/mo</div>
                </div>
                @endforeach

                @for($i = $stalls->count(); $i < max(16, $stalls->count()); $i++)
                <div class="empty-slot">Empty</div>
                @endfor
            </div>
        </div>

        <!-- Stall Summary Table -->
        <div class="panel" style="margin-top: 24px;">
            <h3 class="panel-title" style="margin-bottom: 20px;">Stall Directory</h3>
            <table class="data-table">
                <thead><tr>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Size</th>
                    <th>Price</th>
                    <th>Status</th>
                </tr></thead>
                <tbody>
                @foreach($stalls as $stall)
                <tr>
                    <td style="font-weight:600;color:#0f172a;">{{ $stall->name }}</td>
                    <td>{{ $stall->location ?? '—' }}</td>
                    <td>{{ $stall->size ?? '—' }}</td>
                    <td style="font-weight:600;">₱{{ number_format($stall->price,2) }}</td>
                    <td><span class="badge {{ $stall->status==='available'?'badge-green':($stall->status==='occupied'?'badge-blue':'badge-yellow') }}">{{ ucfirst($stall->status) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Popover -->
    <div class="popover" id="stallPopover">
        <h3 id="popName"></h3>
        <div class="info-row"><span>Status</span><span id="popStatus"></span></div>
        <div class="info-row"><span>Location</span><span id="popLocation"></span></div>
        <div class="info-row"><span>Size</span><span id="popSize"></span></div>
        <div class="info-row"><span>Price</span><span id="popPrice"></span></div>
    </div>

    <script>
        const popover = document.getElementById('stallPopover');
        function showPopover(e, id) {
            const tile = e.currentTarget;
            const stall = JSON.parse(tile.dataset.stall);
            document.getElementById('popName').textContent = stall.name;
            document.getElementById('popStatus').textContent = stall.status.charAt(0).toUpperCase() + stall.status.slice(1);
            document.getElementById('popLocation').textContent = stall.location || '—';
            document.getElementById('popSize').textContent = stall.size || '—';
            document.getElementById('popPrice').textContent = '₱' + parseFloat(stall.price).toLocaleString(undefined, {minimumFractionDigits:2});
            const rect = tile.getBoundingClientRect();
            popover.style.top = (rect.bottom + 8) + 'px';
            popover.style.left = Math.min(rect.left, window.innerWidth - 280) + 'px';
            popover.classList.add('show');
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.stall-tile') && !e.target.closest('.popover')) {
                popover.classList.remove('show');
            }
        });
    </script>
</body>
</html>
