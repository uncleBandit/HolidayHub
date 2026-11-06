{{-- What the review team said about a listing, shown to its host when the
     listing was sent back for changes or taken down, so they know what to fix.

     `listing` is the model that carries the moderation state: the canonical
     Accommodation for stays (status + suspension_reason), the Activity for
     experiences (rejection_reason / suspension_reason), or a Package
     (rejection_reason). Renders nothing otherwise. --}}
@props(['listing' => null])

@php
    $status = $listing?->status instanceof \BackedEnum ? $listing->status->value : $listing?->status;
    $reason = trim((string) ($listing?->rejection_reason ?? '')) ?: trim((string) ($listing?->suspension_reason ?? ''));
    $note = match ($status) {
        'rejected' => ['Changes requested by our review team', 'Make the changes below, then submit again. Saving your changes sends it back for review.'],
        'suspended' => ['Taken down by our review team', 'Guests can’t find or book it right now. Contact support once you’ve dealt with this.'],
        default => null,
    };
@endphp

@if ($note)
    <div {{ $attributes->merge(['class' => 'px-5 md:px-[max(5vw,calc((100vw-1350px)/2))]']) }}>
        <div class="flex items-start gap-3 rounded-[1rem] border border-clay/25 bg-[#FBE9E2] px-4 py-3.5 text-[13px] text-clay-dark" role="alert">
            <span class="mt-0.5 flex-none"><x-line-icon name="bell" :size="17" /></span>
            <div class="min-w-0">
                <p class="font-extrabold">{{ $note[0] }}</p>
                @if ($reason)
                    <p class="mt-1 whitespace-pre-line text-ink">“{{ $reason }}”</p>
                @endif
                <p class="mt-1.5 text-[12px] text-clay-dark/80">{{ $note[1] }}</p>
            </div>
        </div>
    </div>
@endif
