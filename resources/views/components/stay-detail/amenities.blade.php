{{-- Amenity grid: cream tiles with clay line icons. --}}
@props([
    'amenities' => [],
    'empty' => 'The host has not listed amenities yet. Message them before you book if something matters to you.',
])

@php
    $iconFor = function (string $name): string {
        $n = \Illuminate\Support\Str::lower($name);

        return match (true) {
            \Illuminate\Support\Str::contains($n, ['pool', 'beach', 'sea', 'ocean', 'jacuzzi', 'spa', 'tub', 'bath', 'water']) => 'waves',
            \Illuminate\Support\Str::contains($n, ['breakfast', 'restaurant', 'dining', 'kitchen', 'bar', 'room service', 'meal', 'coffee']) => 'utensils',
            \Illuminate\Support\Str::contains($n, ['parking', 'transfer', 'airport', 'shuttle', 'car']) => 'map',
            \Illuminate\Support\Str::contains($n, ['garden', 'pet', 'terrace', 'eco', 'farm']) => 'leaf',
            \Illuminate\Support\Str::contains($n, ['gym', 'fitness', 'hike', 'view', 'balcony']) => 'mountain',
            \Illuminate\Support\Str::contains($n, ['reception', '24', 'check-in', 'late']) => 'clock',
            \Illuminate\Support\Str::contains($n, ['bed', 'linen', 'room']) => 'bed',
            \Illuminate\Support\Str::contains($n, ['tour', 'safari', 'excursion', 'guide']) => 'compass',
            \Illuminate\Support\Str::contains($n, ['family', 'child', 'kid']) => 'users',
            default => 'check',
        };
    };
@endphp

@if (collect($amenities)->isNotEmpty())
    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ($amenities as $amenity)
            <li class="flex min-h-[4.25rem] items-center gap-3 rounded-[1rem] bg-sand/60 px-4 py-4 text-[13px] font-bold text-ink transition-colors duration-200 hover:bg-sand">
                <x-line-icon :name="$iconFor((string) $amenity->name)" :size="20" class="text-clay" />
                <span class="min-w-0 leading-snug">{{ $amenity->name }}</span>
            </li>
        @endforeach
    </ul>
@else
    <p class="rounded-[1rem] border border-dashed border-line bg-surface px-6 py-8 text-center text-[13px] text-muted">{{ $empty }}</p>
@endif
