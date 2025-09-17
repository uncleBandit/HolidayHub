<div>
<div class="relative min-h-screen bg-gray-50 font-sans antialiased text-gray-800 overflow-x-hidden">

    {{-- Background Image with Overlay --}}
    <div class="absolute inset-0 z-0 opacity-40">
        <img src="https://images.unsplash.com/photo-1555513220-db98a6a68759?q=80&w=2940&auto=format&fit=crop"
             class="w-full h-full object-cover"
             alt="Minimalist White Lobby Background">
        <div class="absolute inset-0 bg-white/70"></div>
    </div>


    {{-- Header Section: Centered and Elevated --}}
    <header class="relative z-20 pt-24 pb-48">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-5xl md:text-7xl font-extrabold text-center text-gray-900 tracking-tighter mb-6 drop-shadow-md animate-fade-in-down">
                Find Your Escape.
            </h1>
            <p class="text-center text-gray-700 max-w-3xl mx-auto text-lg md:text-xl mb-16 drop-shadow-sm animate-fade-in-down delay-200">
                Browse our curated collection of extraordinary hotels and resorts, meticulously selected for the discerning traveler.
            </p>

            {{-- Main Search Card with Glassmorphism Effect --}}
            <div class="relative w-full max-w-5xl mx-auto animate-fade-in-up delay-500" x-data="{ open: false }" @click.away="open = false">
                <form action="{{ route('hotels.index') }}" method="GET">
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-6 bg-white/70 backdrop-blur-xl rounded-4xl p-8 border border-gray-100 shadow-2xl shadow-indigo-100/50 transform transition-all duration-300 hover:scale-[1.01] hover:border-indigo-200">
                        <div class="relative flex-1 w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-6 pointer-events-none">
                                <i class="fas fa-search text-xl text-indigo-500 animate-pulse-fast"></i>
                            </div>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Where will your next adventure be?"
                                class="w-full pl-14 pr-6 py-5 rounded-full border-2 border-transparent bg-gray-100/70 text-lg font-medium placeholder-gray-500 focus:ring-4 focus:ring-indigo-200 focus:border-indigo-300 focus:outline-none transition-all duration-300"
                            >
                        </div>

                        <div class="relative flex-shrink-0 w-full lg:w-auto">
                            <select wire:model="sortBy" class="w-full lg:min-w-[150px] rounded-full border-2 border-transparent bg-gray-100/70 px-8 py-5 text-lg text-gray-700 focus:ring-4 focus:ring-indigo-200 focus:border-indigo-300 focus:outline-none transition-all duration-200 cursor-pointer">
                                <option value="rating" class="bg-white text-gray-800">Top Rated</option>
                                <option value="stars" class="bg-white text-gray-800">Star Ranking</option>
                                <option value="created_at" class="bg-white text-gray-800">Newest</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </header>
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 relative z-10">
        <h2 class="text-3xl md:text-5xl font-extrabold text-gray-900 text-center mb-16 tracking-tight animate-fade-in">
            Featured Hotels
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
            @forelse($hotels as $hotel)
                <a href="{{ route('hotel.show', ['slug' => $hotel->slug]) }}"
                   class="group relative bg-white rounded-3xl shadow-xl shadow-gray-200/50 border-2 border-transparent transition-all duration-500 transform hover:scale-[1.03] hover:shadow-indigo-100/50 hover:border-indigo-200 animate-card-fade-in">

                    {{-- Image and Overlay --}}
                    <div class="relative h-72 overflow-hidden">
                        <img src="{{ $hotel->cover_image }}"
                             onerror="this.onerror=null;this.src='https://placehold.co/800x600/E5E7EB/6B7280?text=Hotel+Image';"
                             class="w-full h-full object-cover rounded-t-3xl transition-transform duration-500 group-hover:scale-115"
                             alt="{{ $hotel->name }}">

                        {{-- Price Tag & Discount --}}
                        <div class="absolute bottom-6 right-6 p-4 bg-gray-800/10 backdrop-blur-lg rounded-2xl border border-gray-800/20 transition-all duration-500 transform translate-x-full group-hover:translate-x-0">
                            <p class="text-gray-900 text-2xl font-bold">${{ number_format($hotel->price, 0) }}<span class="text-sm font-normal text-gray-600 ml-1">/night</span></p>
                        </div>

                        @if($hotel->discount_percentage > 0)
                            <div class="absolute top-6 left-6 bg-red-600 text-white text-sm font-bold px-4 py-1 rounded-full shadow-lg z-10 animate-pulse-fast">
                                {{ $hotel->discount_percentage }}% OFF
                            </div>
                        @endif
                    </div>

                    {{-- Hotel Details --}}
                    <div class="p-8 space-y-4">
                        <h3 class="text-3xl font-extrabold text-gray-900 group-hover:text-indigo-600 transition-colors duration-300">{{ $hotel->name }}</h3>
                        <div class="flex items-center gap-2 text-sm text-gray-500 font-light">
                            <i class="fas fa-map-marker-alt text-indigo-500"></i>
                            <span>{{ $hotel->city }}, {{ $hotel->country }}</span>
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <div class="flex items-center gap-2 text-lg text-yellow-500">
                                <span class="font-bold">{{ number_format($hotel->rating, 1) }}</span>
                                <span class="text-sm text-gray-500">({{ $hotel->reviews_count }} reviews)</span>
                            </div>

                            <div class="flex items-center gap-1 text-gray-500 font-semibold text-sm">
                                <i class="fas fa-award text-indigo-500"></i>
                                {{ $hotel->stars }}-Star
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-20 bg-white rounded-3xl shadow-lg border border-gray-100">
                    <i class="fas fa-info-circle text-6xl text-gray-300 mb-4"></i>
                    <p class="font-bold text-xl text-gray-500">No hotels match your search criteria.</p>
                    <p class="text-gray-400 mt-2">Please try a different search!</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-20 flex justify-center">
            {{ $hotels->links('pagination::tailwind') }}
        </div>
    </main>
</div>

<style>
    .rounded-4xl { border-radius: 2rem; }

    @keyframes fade-in-down {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-fade-in-down {
        animation: fade-in-down 0.8s ease-out forwards;
    }
    .animate-fade-in-down.delay-200 { animation-delay: 0.2s; }

    @keyframes fade-in-up {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.8s ease-out forwards;
    }
    .animate-fade-in-up.delay-500 { animation-delay: 0.5s; }

    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .animate-fade-in {
        animation: fade-in 1s ease-in forwards;
    }

    @keyframes card-fade-in {
        from { opacity: 0; transform: translateY(20px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .animate-card-fade-in {
        animation: card-fade-in 0.6s ease-out forwards;
        animation-delay: calc(var(--index) * 0.1s);
        opacity: 0;
    }

    @keyframes pulse-fast {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    .animate-pulse-fast {
        animation: pulse-fast 1s ease-in-out infinite;
    }
</style>

{{-- The following script is for the `animate-card-fade-in` to work correctly --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.animate-card-fade-in');
        cards.forEach((card, index) => {
            card.style.setProperty('--index', index);
        });
    });
</script>

</div>
