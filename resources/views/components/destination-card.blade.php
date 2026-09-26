@props(['destination'])

@php
    // Destinations store a single cover image, but older rows may only have a
    // gallery. Fall back gracefully instead of rendering a broken <img>.
    $image = $destination->cover_image ?? null;

    if (! $image && is_array($destination->gallery ?? null)) {
        $image = $destination->gallery[0] ?? null;
    }

    if (is_array($image)) {
        $image = $image['url'] ?? $image['path'] ?? null;
    }

    $image = filled($image) ? $image : null;
    $currency = $destination->currency ?: 'USD';
@endphp

<a href="{{ route('destination.show', $destination) }}"
   class="group relative block overflow-hidden rounded-3xl shadow-lg transition-all duration-500 hover:shadow-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
    <div class="relative h-56 w-full bg-gradient-to-br from-indigo-500 via-sky-500 to-cyan-400">
        @if ($image)
            <img src="{{ $image }}"
                 alt="{{ $destination->name }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

        @if ($destination->best_season)
            <span class="absolute right-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-gray-800 shadow">
                {{ $destination->best_season }}
            </span>
        @endif
    </div>

    <div class="relative p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">
            {{ collect([$destination->city, $destination->country])->filter()->implode(', ') }}
        </p>

        <h3 class="mt-1 text-lg font-bold text-gray-900">
            {{ $destination->name }}
        </h3>

        @if ($destination->description)
            <p class="mt-2 line-clamp-2 text-sm text-gray-600">
                {{ \Illuminate\Support\Str::limit($destination->description, 90) }}
            </p>
        @endif

        <span class="mt-4 inline-block text-sm font-semibold text-indigo-700 group-hover:underline">
            Explore {{ $currency }} →
        </span>
    </div>
</a>
