@php($badgeCount = (int) ($count ?? 0))
<span
    class="nav-badge {{ $badgeCount === 0 ? 'hidden' : '' }}"
    data-nav-badge-key="{{ $badgeKey }}"
    aria-label="{{ $label ?? 'Unread items' }}: {{ $badgeCount }}"
    title="{{ $label ?? 'Unread items' }}"
>{{ $badgeCount > 99 ? '99+' : $badgeCount }}</span>
