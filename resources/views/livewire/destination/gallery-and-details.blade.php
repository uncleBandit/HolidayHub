<div>
<section class="bg-white rounded-3xl shadow-2xl p-6 md:p-10 flex flex-col lg:flex-row gap-8">
    <div class="flex-1">
        <h2 class="text-3xl font-bold mb-4 text-gray-900">Discover the Magic</h2>
        <p class="text-gray-700 leading-relaxed">{{ $destination->short_description ?? 'Explore the wonders of this incredible location.' }}</p>
        <div class="mt-6 flex flex-wrap gap-4 text-sm font-medium">
            <span class="bg-blue-100 text-blue-800 rounded-full px-4 py-1">{{ $destination->best_season }}</span>
            <span class="bg-green-100 text-green-800 rounded-full px-4 py-1">{{ $destination->currency }}</span>
            <span class="bg-purple-100 text-purple-800 rounded-full px-4 py-1">{{ $destination->language }}</span>
        </div>
    </div>
    <div class="flex-1 grid grid-cols-2 gap-4">
        @foreach(json_decode($destination->gallery, true) ?? [] as $image)
            <img src="{{ $image }}" class="w-full h-40 object-cover rounded-xl shadow-lg transform transition-transform duration-300 hover:scale-105" alt="Gallery image of {{ $destination->name }}">
        @endforeach
    </div>
</section>
</div>
