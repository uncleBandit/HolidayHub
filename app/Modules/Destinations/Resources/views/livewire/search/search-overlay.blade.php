<div
    x-data="{ open: @entangle('open') }"
    x-on:focus-search.window="$refs.input?.focus()"
    x-on:escape.window="if (open) { open = false }"
>
    @if ($open)
        <div class="fixed inset-x-0 top-[68px] z-50 bg-ink/50 backdrop-blur-[3px] md:top-[82px]">
            <div class="bg-paper px-6 py-8 shadow-[0_24px_40px_rgba(23,33,30,0.12)] md:px-[clamp(24px,8vw,140px)] md:pb-10">
                <form wire:submit="submitSearch" class="mx-auto max-w-4xl">
                    <div class="flex h-[68px] items-center gap-5 border-b border-ink">
                        <span class="text-ink-soft"><x-line-icon name="search" :size="24" /></span>

                        <input
                            x-ref="input"
                            type="search"
                            wire:model.live.debounce.400ms="query"
                            aria-label="Search destinations"
                            placeholder="Search places, stays, or experiences"
                            class="min-w-0 flex-1 border-0 bg-transparent p-0 font-display text-[clamp(24px,3vw,38px)] text-ink placeholder:text-ink-soft/70 focus:ring-0"
                        >

                        <button type="button" wire:click="closeSearch" class="text-sm text-ink-soft transition-colors hover:text-ink">
                            Close
                        </button>
                    </div>
                </form>

                @if (strlen(trim($query)) >= 2 && $results->isNotEmpty())
                    <div class="mx-auto mt-5 max-w-4xl divide-y divide-line border-b border-line">
                        @foreach ($results as $hit)
                            <a href="{{ $hit['url'] }}" class="group flex items-center gap-5 py-3.5">
                                <img src="{{ $hit['image'] }}" alt="{{ $hit['name'] }}" class="h-12 w-12 flex-none rounded-xl object-cover" loading="lazy">
                                <span class="flex min-w-0 flex-col">
                                    <span class="truncate text-[15px] font-medium text-ink">{{ $hit['name'] }}</span>
                                    <span class="text-[11px] text-ink-soft">{{ $hit['meta'] }}</span>
                                </span>
                                <span class="ml-auto text-ink-soft transition-transform group-hover:translate-x-1 group-hover:text-coral">
                                    <x-line-icon name="arrow" :size="18" />
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mx-auto mt-5 flex max-w-4xl flex-wrap items-center gap-2.5 text-[13px] text-ink-soft">
                        <span>Try</span>
                        @foreach (['Coastal Italy', 'Food in Kyoto', 'Desert retreats'] as $idea)
                            <a
                                href="{{ route('destination.index', ['q' => $idea]) }}"
                                class="rounded-full border border-line bg-white px-3.5 py-2 text-ink transition-colors hover:border-ink"
                            >{{ $idea }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>