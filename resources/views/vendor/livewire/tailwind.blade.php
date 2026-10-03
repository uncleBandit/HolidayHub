@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
        JS
        : '';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between gap-4">
        <p class="hidden text-[11px] text-muted sm:block">
            {{ __('Showing') }}
            <span class="font-semibold text-forest">{{ $paginator->firstItem() }}</span>
            {{ __('to') }}
            <span class="font-semibold text-forest">{{ $paginator->lastItem() }}</span>
            {{ __('of') }}
            <span class="font-semibold text-forest">{{ $paginator->total() }}</span>
            {{ __('results') }}
        </p>

        <span class="flex items-center gap-2">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <span class="grid h-10 w-10 place-items-center rounded-full border border-line bg-white/50 text-muted/50">
                        <x-line-icon name="chevron" :size="16" class="rotate-180" />
                    </span>
                </span>
            @else
                <button type="button"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    aria-label="{{ __('pagination.previous') }}"
                    class="grid h-10 w-10 place-items-center rounded-full border border-line bg-white text-forest transition-colors duration-200 hover:border-forest">
                    <x-line-icon name="chevron" :size="16" class="rotate-180" />
                </button>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1 text-[12px] text-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="grid h-10 min-w-[2.5rem] place-items-center rounded-full bg-clay px-3 text-[12px] font-extrabold text-white">
                                        {{ $page }}
                                    </span>
                                </span>
                            @else
                                <button type="button"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                    class="grid h-10 min-w-[2.5rem] place-items-center rounded-full border border-line bg-white px-3 text-[12px] font-bold text-forest transition-colors duration-200 hover:border-forest">
                                    {{ $page }}
                                </button>
                            @endif
                        </span>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <button type="button"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    wire:loading.attr="disabled"
                    aria-label="{{ __('pagination.next') }}"
                    class="grid h-10 w-10 place-items-center rounded-full border border-line bg-white text-forest transition-colors duration-200 hover:border-forest">
                    <x-line-icon name="chevron" :size="16" />
                </button>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <span class="grid h-10 w-10 place-items-center rounded-full border border-line bg-white/50 text-muted/50">
                        <x-line-icon name="chevron" :size="16" />
                    </span>
                </span>
            @endif
        </span>
    </nav>
@endif