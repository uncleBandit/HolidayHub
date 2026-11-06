{{-- The walk-through step shared by every accommodation wizard: one intro,
     a live "scenes so far" nudge, and the SceneBuilder. Only the wording
     changes per kind of stay, so all creation screens look the same.

     `owner` is the saved listing (null until step 1 has been saved);
     `tips` is three [title, text] pairs. --}}
@props([
    'owner' => null,
    'noun' => 'property',
    'headline' => '',
    'story' => '',
    'tips' => [],
    'spaces' => '',
    'suggested' => 5,
])

<div class="mt-6 space-y-6">
    <div class="relative overflow-hidden rounded-[1.2rem] bg-[#0C1511] p-6 text-white sm:p-8">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-clay/25 blur-3xl"></div>
        <p class="relative text-[10.5px] font-extrabold uppercase tracking-[0.18em] text-[#E8C9A0]">The reel walk</p>
        <p class="relative mt-2 max-w-[34rem] font-dm-serif text-[clamp(1.4rem,2.6vw,1.9rem)] leading-snug">{{ $headline }}</p>
        <p class="relative mt-3 max-w-[38rem] text-[12.5px] leading-relaxed text-white/70">{{ $story }}</p>
        <ol class="relative mt-5 grid gap-3 text-[12px] sm:grid-cols-3">
            @foreach ($tips as [$title, $text])
                <li class="rounded-[0.9rem] border border-white/15 bg-white/5 p-3.5"><b class="block text-[13px] text-white">{{ $title }}</b><span class="text-white/65">{{ $text }}</span></li>
            @endforeach
        </ol>
    </div>

    @if ($owner?->exists)
        {{-- The nudge counts the builder's scenes live, so it updates as scenes are added or removed. --}}
        <div
            x-data="{
                count: {{ $owner->scenes()->count() }},
                target: {{ (int) $suggested }},
                init() {
                    const recount = () => this.count = this.$refs.builder.querySelectorAll('[data-scene]').length;
                    new MutationObserver(recount).observe(this.$refs.builder, { childList: true, subtree: true });
                },
            }"
            class="space-y-4"
        >
            <div x-show="count < target" x-cloak class="flex items-start gap-3 rounded-[0.9rem] border border-[#E9D3A6] bg-[#F6EEDD] px-4 py-3 text-[12.5px] text-[#6B5230]">
                <x-line-icon name="play" :size="16" filled class="mt-0.5 flex-none text-clay" />
                <p>
                    <b x-text="count === 0 ? 'Your walk-through is empty.' : count + (count === 1 ? ' scene so far.' : ' scenes so far.')"></b>
                    Walk-throughs with at least {{ $suggested }} scenes feel complete: try {{ $spaces }}.
                </p>
            </div>
            <div x-show="count >= target" x-cloak class="flex items-center gap-2 rounded-[0.9rem] bg-[#E7EEE8] px-4 py-3 text-[12.5px] font-semibold text-forest">
                <x-line-icon name="check" :size="15" /> <span><span x-text="count"></span> scenes: a full walk through your {{ $noun }}. Preview it as guests will see it.</span>
            </div>

            <div x-ref="builder">
                <livewire:scenes.scene-builder :owner="$owner" :guide="false" wire:key="scenes-{{ $owner->getMorphClass() }}-{{ $owner->getKey() }}" />
            </div>
        </div>
    @else
        <p class="rounded-[0.9rem] bg-cream px-4 py-3 text-[12.5px] text-muted">Name your {{ $noun }} in step 1 first; the walk-through is saved against it.</p>
    @endif

    <p class="text-[11.5px] text-muted">The walk-through is optional, and every change saves straight away. You can keep adding scenes after your {{ $noun }} is live.</p>
</div>
