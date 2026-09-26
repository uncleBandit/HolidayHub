@props(['villa'])

@php
    $gallery = $villa->gallery ?? null;
    $image = is_array($gallery) ? ($gallery[0] ?? null) : $gallery;

    if (is_array($image)) {
        $image = $image['url'] ?? $image['path'] ?? null;
    }

    $image = filled($image) ? $image : null;
@endphp

<a href="{{ route('villa.show', $villa->slug) }}"
   class="group relative block overflow-hidden rounded-3xl shadow-lg transition-all duration-500 hover:shadow-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
    <div class="relative h-56 w-full bg-gradient-to-br from-emerald-500 via-teal-500 to-sky-400">
        @if ($image)
            <img src="{{ $image }}"
                 alt="{{ $villa->name }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

        @if ($villa->has_private_pool)
            <span class="absolute right-3 top-3 rounded-full bg-lime-400 px-3 py-1 text-xs font-bold text-gray-900 shadow">
                Private pool
            </span>
        @endif
    </div>

    <div class="relative p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
            {{ collect([$villa->city, $villa->country])->filter()->implode(', ') }}
        </p>

        <h3 class="mt-1 text-lg font-bold text-gray-900">
            {{ $villa->name }}
        </h3>

        <div class="mt-2 flex items-center gap-4 text-xs font-medium text-gray-600">
            <span>{{ $villa->max_guests }} guests</span>
            <span>{{ $villa->bedrooms }} bedrooms</span>
            <span>{{ $villa->bathrooms }} baths</span>
        </div>

        @if ($villa->avg_price_per_night)
            <p class="mt-4 text-lg font-extrabold text-gray-900">
                {{ number_format($villa->avg_price_per_night, 2) }}
                <span class="text-xs font-medium text-gray-500">/ night</span>
            </p>
        @endif
    </div>
</a>
