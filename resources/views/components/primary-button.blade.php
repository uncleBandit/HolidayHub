@props([
    'theme' => 'primary',
    'loading' => false,
    'spinner' => null,
])

@php
    $baseClasses = '
        inline-flex items-center justify-center space-x-2 px-6 py-3 rounded-full text-sm font-semibold
        tracking-wide transition-all duration-300 transform active:scale-95 focus:outline-none focus:ring-2
        focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed
    ';

    $themes = [
        'primary' => '
            bg-tropical-blue text-white shadow-lg hover:bg-deep-ocean focus:ring-tropical-blue
        ',
        'secondary' => '
            bg-transparent text-tropical-blue border border-tropical-blue hover:bg-tropical-blue hover:text-white
            focus:ring-tropical-blue
        ',
        'danger' => '
            bg-sunset-orange text-white shadow-lg hover:bg-red-600 focus:ring-sunset-orange
        ',
        'outline' => '
            border border-zinc-300 dark:border-zinc-600 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100
            dark:hover:bg-zinc-700 focus:ring-zinc-400
        ',
    ];

    $appliedClasses = $baseClasses . ' ' . ($themes[$theme] ?? $themes['primary']);
@endphp

<button {{ $attributes->merge(['class' => trim($appliedClasses)]) }} @if($loading) disabled @endif>
    @if($loading)
        <div class="animate-spin h-4 w-4 rounded-full border-2 border-current border-t-transparent"></div>
        <span>{{ $spinner ?? 'Processing...' }}</span>
    @else
        {{ $slot }}
    @endif
</button>
