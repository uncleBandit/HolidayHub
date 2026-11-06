@props(['review'])

{{-- One guest review on a destination page: serif quote, star row, initial avatar. --}}
@php
    $name = $review->guest?->first_name ?? 'Guest';
    $rating = (int) round((float) $review->rating);
    $photos = is_array($review->photos ?? null) ? $review->photos : [];
@endphp

<article {{ $attributes->merge(['class' => 'dv-panel flex h-full flex-col p-6']) }}>
    <div class="flex items-center gap-0.5 text-clay" aria-label="{{ $rating }} out of 5 stars">
        @for ($i = 0; $i < 5; $i++)
            <span class="{{ $i < $rating ? 'text-clay' : 'text-line' }}">
                <x-line-icon name="star" :size="15" :filled="true" />
            </span>
        @endfor
    </div>

    @if (filled($review->comment))
        <p class="mt-4 flex-1 font-dm-serif text-[1.15rem] leading-snug text-ink">“{{ $review->comment }}”</p>
    @else
        <p class="mt-4 flex-1 text-[13px] italic text-muted">Rated without a written review.</p>
    @endif

    @if ($photos !== [])
        <div class="mt-4 flex gap-2 overflow-x-auto pb-1 no-scrollbar">
            @foreach ($photos as $photo)
                <img src="{{ $photo }}" alt="Photo from {{ $name }}" loading="lazy" class="h-16 w-16 flex-none rounded-[0.6rem] object-cover">
            @endforeach
        </div>
    @endif

    <div class="mt-5 flex items-center gap-3 border-t border-line pt-4">
        <span class="grid h-9 w-9 flex-none place-items-center rounded-full bg-forest text-[13px] font-extrabold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($name, 0, 1)) }}</span>
        <div class="min-w-0">
            <p class="truncate text-[13px] font-extrabold text-ink">{{ $name }}</p>
            <p class="text-[11px] text-muted">{{ $review->created_at?->diffForHumans() }}</p>
        </div>
    </div>
</article>
