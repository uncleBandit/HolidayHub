@props(['icon', 'title', 'value'])

<div class="p-6 bg-white rounded-2xl shadow-lg border border-gray-100 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
    <div class="flex flex-col items-center text-center">
        <i class="{{ $icon }} fa-3x text-indigo-500 mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-800">{{ $title }}</h3>
        <p class="text-gray-600 mt-1">{{ $value }}</p>
    </div>
</div>
