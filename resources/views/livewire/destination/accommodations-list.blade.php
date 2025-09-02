<div>
@if($accommodations->isNotEmpty())
    <section class="bg-white rounded-3xl shadow-2xl p-6 md:p-10">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-900">Stay in Comfort</h2>
            <a href="#" class="text-blue-600 hover:text-blue-800 font-semibold transition-colors duration-300">View All Accommodations &rarr;</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($accommodations->take(6) as $accommodation)
                <div class="bg-gray-100 rounded-2xl overflow-hidden shadow-lg transform transition-transform duration-300 hover:scale-[1.02]">
                    <img src="{{ $accommodation->cover_image }}" alt="{{ $accommodation->name }}" class="w-full h-52 object-cover">
                    <div class="p-5">
                        <div class="flex justify-between items-start">
                            <h3 class="text-xl font-bold text-gray-900">{{ $accommodation->name }}</h3>
                            <div class="flex items-center text-yellow-500">
                                {{-- Star rating component here --}}
                                <span class="font-semibold ml-1 text-gray-800">{{ $accommodation->rating }}</span>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm mt-1">{{ $accommodation->location }}</p>
                        <p class="mt-3 text-lg font-bold text-gray-800">${{ $accommodation->price_per_night }}<span class="text-base text-gray-500 font-normal"> / night</span></p>
                        <a href="#" class="mt-4 inline-block w-full text-center bg-blue-600 text-white font-semibold py-2 rounded-xl hover:bg-blue-700 transition-colors duration-300">Book Now</a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
</div>
