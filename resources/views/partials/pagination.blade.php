{{-- Compact pagination. Usage: {{ $paginator->links('partials.pagination') }} --}}
@if ($paginator->hasPages())
<nav class="pager" role="navigation" aria-label="Pagination">
    <span class="pager-info">
        Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
    </span>
    <div class="pager-links">
        @if ($paginator->onFirstPage())
            <span class="pager-btn disabled" aria-disabled="true">‹ Prev</span>
        @else
            <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Prev</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pager-btn dots">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pager-btn active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pager-btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
        @else
            <span class="pager-btn disabled" aria-disabled="true">Next ›</span>
        @endif
    </div>
</nav>

@once
<style>
    .pager { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 28px; }
    .pager-info { font-size: 13px; color: #64748b; font-weight: 500; }
    .pager-links { display: flex; gap: 6px; flex-wrap: wrap; }
    .pager-btn { min-width: 38px; height: 38px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; color: #334155; font-size: 14px; font-weight: 600; text-decoration: none; transition: .2s; }
    a.pager-btn:hover { border-color: #c7d2fe; color: #4f46e5; background: #eef2ff; }
    .pager-btn.active { background: #4f46e5; border-color: #4f46e5; color: #fff; }
    .pager-btn.disabled, .pager-btn.dots { color: #cbd5e1; cursor: default; }
    @media (max-width: 600px) { .pager { justify-content: center; } .pager-info { width: 100%; text-align: center; } }
</style>
@endonce
@endif
