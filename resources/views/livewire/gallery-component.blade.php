<div>
<div class="relative w-full">
    {{-- Main Image --}}
    <div class="relative aspect-video rounded-2xl overflow-hidden shadow-lg group">
        <img
            src="{{ $images[$this->activeIndex]['url'] ?? '' }}"
            alt="Gallery Image"
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end justify-between p-4">
            <button
                wire:click="prev"
                class="px-4 py-2 bg-black/50 text-white rounded-full hover:bg-black/70 transition">
                ‹
            </button>
            <button
                wire:click="next"
                class="px-4 py-2 bg-black/50 text-white rounded-full hover:bg-black/70 transition">
                ›
            </button>
        </div>
    </div>

    {{-- Thumbnails --}}
    <div class="flex space-x-2 mt-4 overflow-x-auto pb-2 scrollbar-hide">
        @foreach ($images as $index => $image)
            <button
                wire:click="setActive({{ $index }})"
                class="relative w-24 h-16 rounded-lg overflow-hidden border-2 {{ $activeIndex === $index ? 'border-lime-400' : 'border-transparent' }}">
                <img
                    src="{{ $image['url'] }}"
                    alt="Thumbnail {{ $index }}"
                    class="w-full h-full object-cover"
                />
            </button>
        @endforeach
    </div>

    {{-- Optional Fullscreen Modal --}}
    <div
        x-data="{ open: false }"
        x-show="open"
        x-cloak
        class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center">
        <img
            src="{{ $images[$activeIndex]['url'] ?? '' }}"
            class="max-w-full max-h-full rounded-lg shadow-2xl"
        />
        <button @click="open = false" class="absolute top-6 right-6 text-white text-3xl font-bold">×</button>
    </div>
</div>
</div>
