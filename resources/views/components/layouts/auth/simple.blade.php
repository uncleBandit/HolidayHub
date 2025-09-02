<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-zinc-900">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 bg-neutral-200">
            <div class="flex w-full max-w-sm flex-col gap-2 p-8 bg-white rounded-lg shadow-xl">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-12 w-12 mb-2 items-center justify-center rounded-full bg-neutral-100 border border-neutral-300">
                        <x-app-logo-icon class="size-9 fill-current text-neutral-800 dark:text-neutral-200" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
