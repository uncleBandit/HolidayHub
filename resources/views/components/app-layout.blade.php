{{--
    Breeze compatibility shim.

    The Breeze-generated test suite (and any third-party code) renders
    <x-app-layout>. The application itself uses <x-layouts.app>, which is the
    real layout. This component simply forwards to it so both spellings work.
--}}
<x-layouts.app :title="$title ?? null">
    {{ $slot }}
</x-layouts.app>
