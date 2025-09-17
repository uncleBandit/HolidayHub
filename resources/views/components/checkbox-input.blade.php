@props(['label' => '', 'description' => null])

<div class="flex items-start gap-4">
    <div class="flex items-center h-6">
        <input
            type="checkbox"
            {{ $attributes->merge([
                'class' => '
                    form-checkbox h-5 w-5 rounded text-tropical-blue dark:text-tropical-blue
                    border-zinc-300 dark:border-zinc-600 focus:ring-tropical-blue focus:ring-offset-2 focus:ring-offset-white
                    dark:bg-zinc-800 transition duration-150 ease-in-out
                '
            ]) }}
        />
    </div>

    <div class="flex flex-col">
        <label for="{{ $attributes->get('id') ?? $attributes->get('wire:model') }}" class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 cursor-pointer">
            {{ $label }}
        </label>

        @if ($description)
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                {{ $description }}
            </p>
        @endif

        @error($attributes->get('wire:model'))
            <span class="mt-1 text-xs text-red-500 font-medium">
                {{ $message }}
            </span>
        @enderror
    </div>
</div>
