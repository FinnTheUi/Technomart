{{-- Floor Plan Visual Map Partial
     Usage: @include('partials.floor-plan-map', ['floor1Stalls' => $floor1Stalls, 'floor2Stalls' => $floor2Stalls, 'interactive' => true/false])
     $floor1Stalls and $floor2Stalls are keyed by stall_number.
     $interactive = true: manager view (details only); false: public view (links to apply).
     Availability refreshes live from route('stalls.availability').
--}}
@php
    $allStalls = collect($floor1Stalls)->merge(collect($floor2Stalls)->values());
    $countFor = fn ($status, $stalls = null) => collect($stalls ?? $allStalls)->filter(fn ($s) => $s->status->value === $status)->count();
    $tile = fn (int $floor, int $num) => ['floor' => $floor, 'num' => $num, 's' => ($floor === 1 ? $floor1Stalls : $floor2Stalls)[$num] ?? null];
@endphp

<div class="floorplan-wrapper" id="floorplan" data-public="{{ ($interactive ?? false) ? '0' : '1' }}"
     data-feed="{{ route('stalls.availability') }}" data-refresh="{{ config('stalls.live_refresh_seconds', 15) }}">
    {{-- Floor Tabs (bubble = available units on that floor) --}}
    <div class="floorplan-tabs">
        <button type="button" class="floorplan-tab active" onclick="switchFloor(1, this)" id="floorTab1">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            {{ config('stalls.floors.1.label', 'Floor 1 — Stalls') }}
            <span class="tab-bubble" data-floor-available="1" title="Available on this floor">{{ $countFor('available', $floor1Stalls) }}</span>
        </button>
        <button type="button" class="floorplan-tab" onclick="switchFloor(2, this)" id="floorTab2">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            {{ config('stalls.floors.2.label', 'Floor 2 — Carts') }}
            <span class="tab-bubble" data-floor-available="2" title="Available on this floor">{{ $countFor('available', $floor2Stalls) }}</span>
        </button>
    </div>

    {{-- Legend with live counts --}}
    <div class="floorplan-legend">
        @foreach(['available' => 'Available', 'occupied' => 'Occupied', 'maintenance' => 'Under maintenance'] as $status => $label)
            <div class="legend-item">
                <span class="legend-bubble {{ $status }}"></span> {{ $label }}
                <span class="legend-count {{ $status }}" data-count="{{ $status }}">{{ $countFor($status) }}</span>
            </div>
        @endforeach
        <div class="legend-item live-indicator" id="fpLive" title="Availability updates automatically">
            <span class="live-dot"></span> <span id="fpLiveText">Live</span>
        </div>
    </div>

    {{-- ════════ FLOOR 1 — U-SHAPE LAYOUT ════════ --}}
    <div class="floorplan-floor" id="floor1">
        <div class="floor1-layout">
            <div class="floor1-top-row">
                @foreach([22,21,20,19,18,17,16,15,14,13,12,11] as $num)
                    @include('partials.floor-plan-stall', $tile(1, $num))
                @endforeach
            </div>

            <div class="floor1-middle">
                <div class="floor1-left-col">
                    @foreach([23, 24] as $num)
                        @include('partials.floor-plan-stall', $tile(1, $num))
                    @endforeach
                </div>
                <div class="floor1-center">
                    <div class="open-space-label">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        Open Space
                    </div>
                </div>
                <div class="floor1-right-col">
                    @foreach([10, 9] as $num)
                        @include('partials.floor-plan-stall', $tile(1, $num))
                    @endforeach
                </div>
            </div>

            <div class="floor1-bottom-row">
                <div class="floor1-bottom-left">
                    @foreach([1,2,3,4] as $num)
                        @include('partials.floor-plan-stall', $tile(1, $num))
                    @endforeach
                </div>
                <div class="floor1-bottom-gap"></div>
                <div class="floor1-bottom-right">
                    @foreach([5,6,7,8] as $num)
                        @include('partials.floor-plan-stall', $tile(1, $num))
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ FLOOR 2 — SINGLE ROW OF CARTS ════════ --}}
    <div class="floorplan-floor" id="floor2" style="display:none;">
        <div class="floor2-layout">
            <div class="floor2-row">
                @foreach([10,9,8,7,6,5,4,3,2,1] as $num)
                    @include('partials.floor-plan-stall', $tile(2, $num))
                @endforeach
            </div>
        </div>
    </div>

    {{-- Popover --}}
    <div class="fp-popover" id="fpPopover">
        <div class="fp-popover-header">
            <h4 id="fpPopName"></h4>
            <button type="button" class="fp-popover-close" onclick="closeFpPopover()">×</button>
        </div>
        <div class="fp-popover-body">
            <div class="fp-info-row"><span>Status</span><span id="fpPopStatus"></span></div>
            <div class="fp-info-row"><span>Location</span><span id="fpPopLocation"></span></div>
            <div class="fp-info-row"><span>Size</span><span id="fpPopSize"></span></div>
            <div class="fp-info-row"><span>Monthly rent</span><span id="fpPopPrice"></span></div>
        </div>
        @if(!($interactive ?? false))
        <a href="{{ route('stalls.index') }}" id="fpPopApply" class="fp-popover-action">
            Select This Stall
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
        @endif
    </div>
</div>

<style>
/* ══════════ FLOOR PLAN MAP STYLES ══════════ */
.floorplan-wrapper { --fp-available: #10b981; --fp-occupied: #0ea5e9; --fp-maintenance: #f59e0b;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03); }

/* Tabs */
.floorplan-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.floorplan-tab { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer; transition: all 0.2s; }
.floorplan-tab:hover { border-color: #cbd5e1; color: #334155; background: #f1f5f9; }
.floorplan-tab.active { background: #eef2ff; border-color: #c7d2fe; color: #4f46e5; }
.floorplan-tab svg { flex-shrink: 0; }
.tab-bubble { min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px; background: var(--fp-available); color: #fff; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
.tab-bubble.zero { background: #cbd5e1; }

/* Legend */
.floorplan-legend { display: flex; gap: 18px; align-items: center; margin-bottom: 24px; flex-wrap: wrap; }
.floorplan-legend .legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #475569; font-weight: 600; }
.legend-bubble { width: 8px; height: 8px; border-radius: 50%; }
.legend-bubble.available, .legend-count.available { background: var(--fp-available); }
.legend-bubble.occupied, .legend-count.occupied { background: var(--fp-occupied); }
.legend-bubble.maintenance, .legend-count.maintenance { background: var(--fp-maintenance); }
.legend-count { color: #fff; font-size: 11px; font-weight: 800; min-width: 22px; height: 20px; padding: 0 6px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; }
.live-indicator { margin-left: auto; color: #64748b !important; }
.live-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--fp-available); animation: fpPulse 2s infinite; }
.live-indicator.offline .live-dot { background: #94a3b8; animation: none; }

/* Floor container */
.floorplan-floor { position: relative; }

/* Stall tiles */
.fp-stall { min-width: 64px; min-height: 64px; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; cursor: pointer; transition: background 0.3s, border-color 0.3s, transform 0.25s, box-shadow 0.25s; border: 2px solid transparent; position: relative; padding: 6px 8px; }
.fp-stall:hover { transform: translateY(-3px); z-index: 2; box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
.fp-stall.available { background: #ecfdf5; border-color: #a7f3d0; }
.fp-stall.available:hover { border-color: #34d399; }
.fp-stall.occupied { background: #f0f9ff; border-color: #bae6fd; }
.fp-stall.occupied:hover { border-color: #38bdf8; }
.fp-stall.maintenance { background: #fffbeb; border-color: #fde68a; }
.fp-stall.maintenance:hover { border-color: #fbbf24; }
.fp-stall.empty { background: #f8fafc; border: 2px dashed #e2e8f0; cursor: default; opacity: 0.5; }
.fp-stall.empty:hover { transform: none; box-shadow: none; }
.fp-stall.changed { animation: fpFlash 1.2s ease; }
.fp-num { font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1; }
.fp-name { font-size: 9px; font-weight: 600; color: #64748b; text-align: center; line-height: 1.2; max-width: 60px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Status bubble — a small dot whose color matches the stall's class */
.fp-bubble { position: absolute; top: 5px; right: 5px; width: 8px; height: 8px; border-radius: 50%; display: none; box-shadow: 0 0 0 2px #fff; }
.fp-stall.available .fp-bubble { display: block; background: var(--fp-available); animation: fpPulse 2.4s infinite; }
.fp-stall.occupied .fp-bubble { display: block; background: var(--fp-occupied); }
.fp-stall.maintenance .fp-bubble { display: block; background: var(--fp-maintenance); }

@keyframes fpPulse { 0%, 100% { opacity: 1; } 50% { opacity: .45; } }
@keyframes fpFlash { 0% { box-shadow: 0 0 0 4px rgba(79,70,229,0.45); } 100% { box-shadow: 0 0 0 0 rgba(79,70,229,0); } }

/* ═══ FLOOR 1 — U-SHAPE ═══ */
.floor1-layout { border: 2px solid #e2e8f0; border-radius: 16px; padding: 22px 20px 20px; background: #fafbfc; position: relative; margin-top: 8px; }
.floor1-layout::before { content: '⬆ Entrance'; position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: #fff; padding: 2px 12px; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; border: 1px solid #e2e8f0; border-radius: 6px; }
.floor1-top-row { display: grid; grid-template-columns: repeat(12, 1fr); gap: 8px; margin-bottom: 12px; }
.floor1-middle { display: grid; grid-template-columns: auto 1fr auto; gap: 10px; margin-bottom: 12px; min-height: 148px; }
.floor1-left-col, .floor1-right-col { display: flex; flex-direction: column; gap: 8px; }
.floor1-center { display: flex; align-items: center; justify-content: center; border: 2px dashed #e2e8f0; border-radius: 12px; background: #fff; min-height: 100%; }
.open-space-label { display: flex; flex-direction: column; align-items: center; gap: 6px; color: #cbd5e1; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; }
.floor1-bottom-row { display: grid; grid-template-columns: auto 1fr auto; gap: 10px; }
.floor1-bottom-left, .floor1-bottom-right { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.floor1-bottom-gap { min-width: 60px; }

/* ═══ FLOOR 2 — SINGLE ROW ═══ */
.floor2-layout { border: 2px solid #e2e8f0; border-radius: 16px; padding: 20px; background: #fafbfc; }
.floor2-row { display: grid; grid-template-columns: repeat(10, 1fr); gap: 10px; }

/* ═══ POPOVER ═══ */
.fp-popover { display: none; position: fixed; z-index: 200; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 0; min-width: 280px; box-shadow: 0 20px 40px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.03); overflow: hidden; animation: fpFadeIn 0.2s ease; }
.fp-popover.show { display: block; }
@keyframes fpFadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
.fp-popover-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px 12px; border-bottom: 1px solid #f1f5f9; }
.fp-popover-header h4 { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; }
.fp-popover-close { background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 0; line-height: 1; transition: color 0.2s; }
.fp-popover-close:hover { color: #0f172a; }
.fp-popover-body { padding: 12px 20px; }
.fp-info-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; color: #64748b; border-bottom: 1px solid #f8fafc; }
.fp-info-row:last-child { border-bottom: none; }
.fp-info-row span:last-child { color: #0f172a; font-weight: 600; }
.fp-status-pill { color: #fff !important; padding: 2px 10px; border-radius: 999px; font-size: 12px; }
.fp-status-pill.available { background: var(--fp-available); }
.fp-status-pill.occupied { background: var(--fp-occupied); }
.fp-status-pill.maintenance { background: var(--fp-maintenance); }
.fp-popover-action { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; background: #10b981; color: #fff; font-size: 13px; font-weight: 700; text-decoration: none; transition: background 0.2s; }
.fp-popover-action:hover { background: #059669; }

/* ═══ RESPONSIVE ═══ */
@media (max-width: 900px) {
    .floor1-top-row { grid-template-columns: repeat(6, 1fr); }
    .floor2-row { grid-template-columns: repeat(5, 1fr); }
    .fp-stall { min-width: 48px; min-height: 48px; }
    .fp-num { font-size: 14px; }
}
@media (max-width: 600px) {
    .floor1-top-row { grid-template-columns: repeat(4, 1fr); }
    .floor1-bottom-left, .floor1-bottom-right { grid-template-columns: repeat(2, 1fr); }
    .floor2-row { grid-template-columns: repeat(5, 1fr); }
    .floorplan-wrapper { padding: 16px; }
    .live-indicator { margin-left: 0; }
}
@media (prefers-reduced-motion: reduce) {
    .fp-stall.available .fp-bubble, .live-dot, .fp-stall.changed { animation: none; }
}
</style>

<script>
(function () {
    const STATUSES = ['available', 'occupied', 'maintenance'];
    const wrapper = document.getElementById('floorplan');
    const isPublic = wrapper.dataset.public === '1';

    window.switchFloor = function (floor, btn) {
        document.getElementById('floor1').style.display = floor === 1 ? 'block' : 'none';
        document.getElementById('floor2').style.display = floor === 2 ? 'block' : 'none';
        document.querySelectorAll('.floorplan-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        closeFpPopover();
    };

    window.fpClick = function (e) {
        const tile = e.currentTarget;
        if (!tile.dataset.stall) return;
        const stall = JSON.parse(tile.dataset.stall);
        const pop = document.getElementById('fpPopover');

        document.getElementById('fpPopName').textContent = stall.name;
        const status = document.getElementById('fpPopStatus');
        status.textContent = stall.status_label;
        status.className = 'fp-status-pill ' + stall.status;
        document.getElementById('fpPopLocation').textContent = stall.location || '—';
        document.getElementById('fpPopSize').textContent = stall.size || '—';
        document.getElementById('fpPopPrice').textContent = '₱' + Number(stall.price).toLocaleString(undefined, {minimumFractionDigits: 2});

        const applyLink = document.getElementById('fpPopApply');
        if (applyLink) {
            applyLink.style.display = stall.status === 'available' ? 'flex' : 'none';
            applyLink.href = '/stalls/' + stall.id + '/apply';
        }

        const rect = tile.getBoundingClientRect();
        pop.style.top = Math.min(rect.bottom + 8, window.innerHeight - 260) + 'px';
        pop.style.left = Math.min(rect.left, window.innerWidth - 300) + 'px';
        pop.dataset.stallId = stall.id;
        pop.classList.add('show');
    };

    window.closeFpPopover = function () {
        document.getElementById('fpPopover').classList.remove('show');
    };

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.fp-stall') && !e.target.closest('.fp-popover')) closeFpPopover();
    });

    // ── Live availability ──
    function applyStall(tile, stall) {
        const previous = tile.dataset.stall ? JSON.parse(tile.dataset.stall).status : null;
        tile.classList.remove('empty', ...STATUSES);
        tile.classList.add(stall.status);
        tile.dataset.stallId = stall.id;
        tile.dataset.stall = JSON.stringify(stall);
        tile.title = stall.name + ' — ' + stall.status_label;
        tile.querySelector('.fp-name').textContent = stall.name;

        if (previous && previous !== stall.status) {
            tile.classList.remove('changed');
            void tile.offsetWidth; // restart the flash animation
            tile.classList.add('changed');
        }
    }

    function refreshCounts() {
        STATUSES.forEach(status => {
            const el = wrapper.querySelector('[data-count="' + status + '"]');
            if (el) el.textContent = wrapper.querySelectorAll('.fp-stall.' + status).length;
        });
        [1, 2].forEach(floor => {
            const el = wrapper.querySelector('[data-floor-available="' + floor + '"]');
            if (!el) return;
            const n = wrapper.querySelectorAll('#floor' + floor + ' .fp-stall.available').length;
            el.textContent = n;
            el.classList.toggle('zero', n === 0);
        });
    }

    function setLive(ok) {
        const indicator = document.getElementById('fpLive');
        indicator.classList.toggle('offline', !ok);
        document.getElementById('fpLiveText').textContent = ok
            ? 'Live · updated ' + new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})
            : 'Reconnecting…';
    }

    async function poll() {
        if (document.hidden) return;
        try {
            const response = await fetch(wrapper.dataset.feed, {headers: {Accept: 'application/json'}, cache: 'no-store'});
            if (!response.ok) throw new Error(response.status);
            const data = await response.json();

            data.stalls.forEach(stall => {
                let tile = wrapper.querySelector('.fp-stall[data-stall-id="' + stall.id + '"]');
                // On public maps, stalls created since the page loaded fill their empty slot.
                if (!tile && isPublic) {
                    tile = wrapper.querySelector('.fp-stall.empty[data-floor="' + stall.floor + '"][data-num="' + stall.number + '"]');
                }
                if (tile) applyStall(tile, stall);
            });

            const pop = document.getElementById('fpPopover');
            if (pop.classList.contains('show')) {
                const open = wrapper.querySelector('.fp-stall[data-stall-id="' + pop.dataset.stallId + '"]');
                if (open) fpClick({currentTarget: open});
            }

            refreshCounts();
            setLive(true);
            document.dispatchEvent(new CustomEvent('stalls:updated', {detail: data}));
        } catch (e) {
            setLive(false);
        }
    }

    refreshCounts();
    setLive(true);
    setInterval(poll, Math.max(5, Number(wrapper.dataset.refresh)) * 1000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
</script>
