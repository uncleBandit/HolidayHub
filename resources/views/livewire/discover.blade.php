<div>
   <div class="space-y-12 py-8">

    <header class="relative bg-cover bg-center rounded-3xl overflow-hidden shadow-2xl"
            style="background-image: url('https://images.unsplash.com/photo-1542455079-63a2a3e0f9b6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=MnwzNTc5fDB8MXxhbGx8fGxlbnN8fHx8fHwxNjcwMzg3MDI2&ixlib=rb-4.0.3&q=80&w=1920');">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent opacity-90"></div>
        <div class="relative z-10 p-10 md:p-20 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-extrabold leading-tight tracking-tight mb-4">
                Discover Your Next Adventure
            </h1>
            <p class="text-lg md:text-xl font-light max-w-2xl mx-auto mb-8 opacity-90">
                Explore a world of hand-picked villas, curated experiences, and unforgettable destinations.
            </p>
        </div>
    </header>

    @if(Auth::check() && $recommendedVillas->isNotEmpty())
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-6">
                Recommended For You 🌟
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($recommendedVillas as $villa)
                    <x-villa-card :villa="$villa" />
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <h2 class="text-3xl font-bold text-gray-800 mb-6">
            Trending Destinations 🔥
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($featuredDestinations as $destination)
                <x-destination-card :destination="$destination" />
            @endforeach
        </div>
    </div>

    @foreach ($collections as $title => $items)
        @if ($items->isNotEmpty())
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-6">
                    {{ $title }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($items as $item)
                        <x-item-card :item="$item" />
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    <div>
        <h2 class="text-3xl font-bold text-gray-800 mb-6">
            Top Experiences
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($trendingExperiences as $experience)
                <x-experience-card :experience="$experience" />
            @endforeach
        </div>
    </div>

</div>
</div>
