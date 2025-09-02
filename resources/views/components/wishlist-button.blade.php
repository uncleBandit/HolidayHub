@props(['isWishlisted'])

<button wire:click.stop="toggleWishlist"
        {{ $attributes->merge(['class' => 'absolute top-4 right-4 p-3 bg-white rounded-full shadow-md transition-all duration-300 hover:scale-110 hover:bg-red-100 z-10']) }}>
    @if($isWishlisted)
        <x-heroicon-s-heart class="w-6 h-6 text-red-500" />
    @else
        <x-heroicon-o-heart class="w-6 h-6 text-gray-700" />
    @endif
</button>
