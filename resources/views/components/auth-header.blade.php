@props([
    'title',
    'description' => null,
    'eyebrow' => null,
])

{{-- Shared heading for every guest screen. --}}
<div class="w-full">
    @if (filled($eyebrow))
        <p class="dv-eyebrow">{{ $eyebrow }}</p>
    @endif

    <h1 class="auth-title">{{ $title }}</h1>

    @if (filled($description))
        <p class="auth-sub">{{ $description }}</p>
    @endif
</div>