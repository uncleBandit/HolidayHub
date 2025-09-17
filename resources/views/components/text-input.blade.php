@props(['label', 'placeholder' => '', 'icon' => null])

<div class="relative w-full">
    {{-- Label with dynamic ID for accessibility --}}
    <label for="{{ $attributes->get('id') ?? $attributes->get('wire:model') }}" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-200">
        {{ $label }}
    </label>

    <div class="relative rounded-lg shadow-sm">
        {{-- Optional Icon --}}
        @if ($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-zinc-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="{{ $icon }}" />
                </svg>
            </div>
        @endif

        {{-- The core input element --}}
        <input
            {{ $attributes->merge([
                'class' => '
                    form-input block w-full rounded-lg text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500
                    border-zinc-300 dark:border-zinc-600 focus:border-tropical-blue focus:ring focus:ring-tropical-blue focus:ring-opacity-50
                    disabled:bg-zinc-100 disabled:dark:bg-zinc-700 disabled:opacity-50 transition-all duration-200
                    ' . ($icon ? ' pl-10' : '')
            ]) }}
        />
    </div>

    {{-- Inline error message --}}
    @error($attributes->get('wire:model'))
        <span class="mt-1 text-sm text-red-500 font-medium">
            {{ $message }}
        </span>
    @enderror
</div>
