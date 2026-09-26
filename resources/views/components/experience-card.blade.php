@props(['experience'])

@php
    $image = $experience->cover_image ?? null;

    if (is_array($image)) {
        $image = $image['url'] ?? $image['path'] ?? null;
    }

    $image = filled($image) ? $image : null;
    $currency = $experience->currency ?: 'USD';
@endphp

{{-- Deliberately not a link: there is no experience.show route yet. Wrapping
     this in an <a> would 500 the /discover page. Add the route, then link. --}}
<div class="group relative flex h-full flex-col overflow-hidden rounded-3xl bg-white shadow-lg transition-all duration-500 hover:shadow-2xl">
    <div class="relative h-48 w-full bg-gradient-to-br from-amber-500 via-orange-500 to-rose-400">
        @if ($image)
            <img src="{{ $image }}"
                 alt="{{ $experience->title }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

        @if ($experience->category)
            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-gray-800 shadow">
                {{ $experience->category }}
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">
            {{ $experience->location ?: $experience->city }}
        </p>

        <h3 class="mt-1 text-lg font-bold text-gray-900">
            {{ $experience->title }}
        </h3>

        @if ($experience->description)
            <p class="mt-2 line-clamp-2 flex-1 text-sm text-gray-600">
                {{ \Illuminate\Support\Str::limit($experience->description, 90) }}
            </p>
        @endif

        <div class="mt-4 flex items-center justify-between text-xs font-medium text-gray-600">
            @if ($experience->duration)
                <span>{{ $experience->duration }}</span>
            @endif

            @if (filled($experience->price))
                <span class="text-base font-extrabold text-gray-900">
                    {{ $currency }} {{ number_format($experience->price, 2) }}
                </span>
            @endif
        </div>
    </div>
</div>
