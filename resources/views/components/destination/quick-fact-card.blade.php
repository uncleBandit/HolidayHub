@props(['icon' => 'compass', 'title', 'value'])

{{-- One cell of the floating quick-facts strip on a destination page. --}}
<div {{ $attributes->merge(['class' => 'flex min-w-0 items-center gap-3 px-4 py-4 sm:px-5']) }}>
    <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-cream text-clay">
        <x-line-icon :name="$icon" :size="18" />
    </span>
    <div class="min-w-0">
        <p class="text-[10px] font-extrabold uppercase tracking-[0.11em] text-muted">{{ $title }}</p>
        <p class="mt-0.5 font-dm-serif text-[1.15rem] leading-tight text-ink">{{ $value }}</p>
    </div>
</div>
