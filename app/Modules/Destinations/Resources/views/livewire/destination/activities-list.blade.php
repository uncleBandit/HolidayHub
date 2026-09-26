<div>
@if($activities->isNotEmpty())
    <section class="bg-white rounded-3xl shadow-2xl p-6 md:p-10">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-900">Unforgettable Experiences</h2>
            <a href="#" class="text-blue-600 hover:text-blue-800 font-semibold transition-colors duration-300">View All Activities &rarr;</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($activities->take(8) as $activity)
                <div class="bg-gray-100 rounded-2xl overflow-hidden shadow-lg group transform transition-transform duration-300 hover:scale-105">
                    <div class="relative">
                        <img src="{{ $activity->cover_image }}" alt="{{ $activity->title }}" class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-110">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                            <h3 class="text-white font-semibold text-lg">{{ $activity->title }}</h3>
                            <p class="text-gray-300 text-sm">{{ $activity->duration }} hours</p>
                        </div>
                    </div>
                    <div class="p-4 flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-800">${{ $activity->price }}</span>
                        <button class="bg-blue-500 text-white px-4 py-2 rounded-full text-sm font-semibold hover:bg-blue-600 transition-colors duration-300">Add to Plan</button>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
</div>
