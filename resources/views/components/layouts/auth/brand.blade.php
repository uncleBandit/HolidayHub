@props([
    'clip' => null,
    'eyebrow' => null,
    'quote' => null,
    'stats' => null,
])

{{-- The auth shell.

     Guest screens get the same editorial split as the rest of the discovery
     language: full-bleed stay footage on one side, a quiet cream form column on
     the other. Catalogue counts are cached and fall back to plain wording when
     the database is empty, so signing in never depends on seeded content. --}}

@php
    $footage = $clip ?? config('stays.clips.'.config('stays.hero'), []);

    $brand = [
        ['label' => 'Discover', 'route' => 'welcome'],
        ['label' => 'Destinations', 'route' => 'destination.index'],
        ['label' => 'Stays', 'route' => 'stays.index'],
    ];

    $stats ??= (function () {
        try {
            $stays = \Illuminate\Support\Facades\Cache::remember(
                'auth.stays.count',
                300,
                fn () => \App\Modules\Accommodation\Domain\Models\Accommodation::published()->count()
            );

            $places = \Illuminate\Support\Facades\Cache::remember(
                'auth.destinations.count',
                300,
                fn () => \App\Modules\Destinations\Domain\Models\Destination::query()->count()
            );
        } catch (\Throwable) {
            return ['Hotels, villas and B&Bs', 'One reel at a time'];
        }

        return array_values(array_filter([
            $stays > 0 ? number_format($stays).' places to stay' : null,
            $places > 0 ? number_format($places).' destinations' : null,
            'One reel at a time',
        ]));
    })();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
</head>
<body class="antialiased">
    <div class="auth-shell">
        {{-- ─────────────────────────  Editorial panel  ─────────────────────── --}}
        <aside class="auth-panel">
            @if (filled($footage))
                <video
                    x-ref="panel"
                    class="auth-media"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="none"
                    poster="{{ $footage['poster'] ?? '' }}"
                    aria-hidden="true"
                    x-init="if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) $refs.panel.pause()"
                >
                    <source src="{{ $footage['video'] ?? '' }}" type="video/mp4">
                </video>
            @endif

            <div class="auth-panel-shade" aria-hidden="true"></div>

            <div class="flex items-center gap-2.5 p-10 xl:p-14">
                <a href="{{ route('welcome') }}" class="flex items-center gap-2.5" aria-label="HolidayHub home">
                    <span class="dv-brand-mark" aria-hidden="true"><span></span><span></span></span>
                    <span class="font-dm-serif text-[1.55rem] tracking-[-0.03em]">HolidayHub</span>
                </a>
            </div>

            <div class="auth-panel-copy">
                @if (filled($eyebrow))
                    <p class="dv-eyebrow-light">{{ $eyebrow }}</p>
                @endif

                <p class="auth-quote">
                    {{ $quote ?? 'Every stay here was filmed before it was listed.' }}
                </p>

                <p class="auth-stats">
                    @foreach ($stats as $stat)
                        <span>{{ $stat }}</span>
                    @endforeach
                </p>
            </div>
        </aside>

        {{-- ────────────────────────────  Form column  ──────────────────────── --}}
        <main class="auth-form-column">
            <div class="auth-topbar">
                <a href="{{ route('welcome') }}" class="flex items-center gap-2 lg:invisible" aria-label="HolidayHub home">
                    <span class="dv-brand-mark" aria-hidden="true"><span></span><span></span></span>
                    <span class="font-dm-serif text-[1.35rem] tracking-[-0.03em] lg:invisible">HolidayHub</span>
                </a>

                <nav class="ml-auto hidden items-center gap-6 lg:flex" aria-label="Main navigation">
                    @foreach ($brand as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            class="text-[11px] font-bold text-muted transition-colors duration-200 hover:text-forest"
                        >{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <a href="{{ route('stays.index') }}" class="dv-btn dv-btn-ghost ml-auto !min-h-[2.4rem] !px-4 lg:hidden">
                    Browse stays
                </a>
            </div>

            <div class="w-full max-w-[26rem]">
                {{ $slot }}
            </div>
        </main>
    </div>

    @include('layouts.partials.scripts')
</body>
</html>