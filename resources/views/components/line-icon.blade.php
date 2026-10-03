@props([
    'name',
    'size' => 20,
    'stroke' => 1.8,
    'filled' => false,
])

@php
    $icons = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.7-7.5 1.1-1.1a5.5 5.5 0 0 0 0-7.8Z"/>',
        'map' => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/><path d="M9 3v15M15 6v15"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'play' => '<path d="m9 6 9 6-9 6Z"/>',
        'star' => '<path d="m12 2 3.1 6.3L22 9.3l-5 4.8 1.2 6.9-6.2-3.3L5.8 21 7 14.1 2 9.3l6.9-1Z"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5Z"/>',
        'bookmark' => '<path d="M6 4h12v17l-6-4-6 4Z"/>',
        'user' => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
        'bell' => '<path d="M18 9a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6Z"/><path d="M10.5 20a1.8 1.8 0 0 0 3 0"/>',
        'bed' => '<path d="M3 18v-8h13a4 4 0 0 1 4 4v4"/><path d="M3 14h17"/><circle cx="7.5" cy="11.5" r="1.8"/>',
        'waves' => '<path d="M2 8.5c2.5-2 4.5-2 7 0s4.5 2 7 0 4.5-2 6 0"/><path d="M2 14c2.5-2 4.5-2 7 0s4.5 2 7 0 4.5-2 6 0"/><path d="M2 19c2.5-2 4.5-2 7 0s4.5 2 7 0 4.5-2 6 0"/>',
        'mountain' => '<path d="m3 19 6.5-11 4 6 2.5-3.5L21 19Z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>',
        'leaf' => '<path d="M4 20c0-8 5-13 16-14 0 10-5 15-13 15H4Z"/><path d="M4 20c3-4 6-6 10-8"/>',
        'ticket' => '<path d="M4 8.5V6h16v2.5a2.5 2.5 0 0 0 0 7V18H4v-2.5a2.5 2.5 0 0 0 0-7Z"/><path d="M13 6v2M13 11v2M13 16v2"/>',
        'utensils' => '<path d="M7 3v7a2 2 0 0 0 4 0V3"/><path d="M9 10v11"/><path d="M17 3c-1.5 1.5-2 3-2 5s.5 3 2 3 2-1 2-3-.5-3.5-2-5Z"/><path d="M17 11v10"/>',
        'share' => '<circle cx="18" cy="5" r="2.6"/><circle cx="6" cy="12" r="2.6"/><circle cx="18" cy="19" r="2.6"/><path d="m8.3 10.8 7.4-4.3M8.3 13.2l7.4 4.3"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
        'sliders' => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h10M18 18h2"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="16" cy="18" r="2"/>',
        'volume' => '<path d="M11 5 6.5 9H3v6h3.5L11 19Z"/><path d="M15.5 8.8a4.5 4.5 0 0 1 0 6.4"/><path d="M18.4 6a8.5 8.5 0 0 1 0 12"/>',
        'volume-off' => '<path d="M11 5 6.5 9H3v6h3.5L11 19Z"/><path d="m16 9.5 5 5M21 9.5l-5 5"/>',
        'grid' => '<rect x="3" y="3" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="2"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2"/>',
        'check' => '<path d="m5 13 4.5 4.5L19 7"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
    ];

    $path = $icons[$name] ?? $icons['compass'];
@endphp

<svg
    {{ $attributes->merge(['class' => 'shrink-0']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="{{ $filled ? 'currentColor' : 'none' }}"
    stroke="currentColor"
    stroke-width="{{ $stroke }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>{!! $path !!}</svg>