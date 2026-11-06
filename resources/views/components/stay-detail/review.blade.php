{{-- A single guest review card. --}}
@props(['review'])

@php
    $review->loadMissing('guest');
    $guest = $review->guest;
    $author = $guest ? trim($guest->first_name.' '.mb_substr((string) $guest->last_name, 0, 1).($guest->last_name ? '.' : '')) : 'Verified guest';
    $rating = (int) round((float) $review->rating);
@endphp

<article class="dv-panel flex h-full flex-col p-6">
    <div class="flex items-center gap-3">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-forest font-dm-serif text-[15px] text-white">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</span>
        <div class="min-w-0">
            <p class="truncate text-[14px] font-bold text-ink">{{ $author }}</p>
            <p class="text-[11px] text-muted">{{ $review->created_at?->format('F Y') }}</p>
        </div>
        <span class="ml-auto flex items-center gap-0.5 text-clay" aria-label="{{ $rating }} out of 5 stars">
            @for ($i = 1; $i <= 5; $i++)
                <x-line-icon name="star" :size="13" :filled="$i <= $rating" :class="$i > $rating ? 'text-line' : ''" />
            @endfor
        </span>
    </div>

    @if (filled($review->title))
        <p class="mt-5 font-dm-serif text-[1.2rem] leading-snug text-forest">{{ $review->title }}</p>
    @endif
    <p @class(['text-[14px] leading-[1.75] text-muted', 'mt-2' => filled($review->title), 'mt-5' => blank($review->title)])>{{ $review->comment ?: 'Rated this stay without leaving a comment.' }}</p>
</article>
