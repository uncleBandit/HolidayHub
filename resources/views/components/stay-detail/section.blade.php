{{-- One titled section of a stay detail page. --}}
@props([
    'id',
    'eyebrow' => null,
    'title' => null,
    'last' => false,
])

<section id="{{ $id }}" {{ $attributes->class(['scroll-mt-[150px] py-12 md:scroll-mt-[160px] md:py-14', 'border-b border-line' => ! $last]) }}>
    @if ($eyebrow || $title || isset($aside))
        <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div>
                @if ($eyebrow)
                    <p class="dv-eyebrow">{{ $eyebrow }}</p>
                @endif
                @if ($title)
                    <h2 class="dv-title">{{ $title }}</h2>
                @endif
            </div>
            {{ $aside ?? '' }}
        </div>
    @endif

    {{ $slot }}
</section>
