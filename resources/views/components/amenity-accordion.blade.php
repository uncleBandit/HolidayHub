@props(['category', 'amenities'])

<div class="border rounded-2xl shadow-sm mb-4">
    <button @click="openCategory = (openCategory === '{{ Str::slug($category) }}' ? null : '{{ Str::slug($category) }}')"
            class="flex justify-between items-center w-full p-6 text-xl font-semibold text-gray-800 transition-colors duration-300 hover:bg-gray-50">
        <span>{{ $category }}</span>
        <x-heroicon-o-chevron-down class="w-6 h-6 transform transition-transform"
                                   :class="{ 'rotate-180': openCategory === '{{ Str::slug($category) }}' }" />
    </button>

    <div x-show="openCategory === '{{ Str::slug($category) }}'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="p-6 grid sm:grid-cols-2 md:grid-cols-3 gap-6 border-t border-gray-200">
        @foreach($amenities as $amenity)
            <div class="flex items-start gap-3">
                <x-icon name="{{ $amenity->icon }}" class="w-6 h-6 text-blue-500 flex-shrink-0 mt-1" />
                <div>
                    <h4 class="font-medium text-gray-800">{{ $amenity->name }}</h4>
                    <p class="text-sm text-gray-500 mt-1">{{ $amenity->description ?? 'No description provided.' }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
