{{-- Sticky section nav for stay detail pages. Each tab scrolls to its section;
     when `wireTab` is set the tab also calls the component's switchTab() so the
     `activeTab` query-string state keeps working (and deep links scroll in). --}}
@props([
    'tabs' => [],
    'active' => null,
    'wireTab' => false,
])

@php
    $active = $active && array_key_exists($active, $tabs) ? $active : array_key_first($tabs);
@endphp

<nav
    class="sticky top-[76px] z-20 -mx-4 mt-10 bg-cream/90 px-4 backdrop-blur-xl sm:-mx-6 sm:px-6 md:top-[88px] lg:-mx-8 lg:px-8"
    aria-label="Sections"
    x-data="{
        current: @js($active),
        go(id) {
            this.current = id;
            document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
    }"
    x-init="if (@js($wireTab && $active !== array_key_first($tabs))) $nextTick(() => go(current))"
    data-no-reveal
>
    <div class="dv-tabs">
        @foreach ($tabs as $id => $label)
            <button
                type="button"
                class="dv-tab"
                :class="current === @js($id) && 'dv-tab-active'"
                x-on:click="go(@js($id))"
                @if ($wireTab) wire:click="switchTab('{{ $id }}')" @endif
            >{{ $label }}</button>
        @endforeach
    </div>
</nav>
