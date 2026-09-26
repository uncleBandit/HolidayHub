<x-layouts.app.header>
<div class="relative bg-gradient-to-br from-blue-50 via-white to-blue-100 min-h-screen">
    <!-- Hero Section -->
    <section class="relative py-20 text-center">
        <h1 class="text-4xl md:text-6xl font-bold text-gray-900">
            Explore <span class="text-blue-600">Top Destinations</span>
        </h1>
        <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
            Discover breathtaking places, curated experiences, and the perfect holiday stays tailored just for you.
        </p>

        <!-- Search Bar -->
        <div class="mt-8 max-w-3xl mx-auto">
            <form method="GET" action="{{ route('destinations.index') }}" class="flex items-center bg-white shadow-lg rounded-full px-4 py-2 border border-gray-200">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search destinations (e.g. Paris, Dubai...)"
                    class="flex-grow px-4 py-2 outline-none rounded-full"
                >
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">
                    Search
                </button>
            </form>
        </div>
    </section>

    <!-- Destinations Grid -->
    <section class="max-w-7xl mx-auto px-6 lg:px-12 py-12">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse($destinations as $destination)
                <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition overflow-hidden group">
                    <div class="relative">
                        <img
                            src="{{ $destination->cover_image ?? 'https://source.unsplash.com/600x400/?travel,destination,' . $destination->name }}"
                            alt="{{ $destination->name }}"
                            class="w-full h-52 object-cover group-hover:scale-105 transition duration-500"
                        >
                        <div class="absolute top-3 right-3 bg-white px-3 py-1 rounded-full shadow text-sm font-medium text-gray-700">
                            {{ $destination->country }}
                        </div>
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-bold text-gray-800 truncate">
                            {{ $destination->name }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                            {{ $destination->description }}
                        </p>
                        <div class="mt-4 flex items-center justify-between">
                            <a
                                href="{{ route('destinations.show', $destination->id) }}"
                                class="text-blue-600 font-semibold hover:underline"
                            >
                                Explore →
                            </a>
                            <div class="flex items-center space-x-1 text-yellow-500">
                                @for ($i = 0; $i < 5; $i++)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $i < round($destination->rating) ? 'fill-yellow-500' : 'fill-gray-300' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path d="M12 .587l3.668 7.431L24 9.748l-6 5.846L19.335 24 12 19.897 4.665 24 6 15.594 0 9.748l8.332-1.73z"/>
                                    </svg>
                                @endfor
                                <span class="ml-1 text-sm text-gray-600">({{ number_format($destination->rating, 1) }})</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-600">
                    <p class="text-lg">No destinations found. Try adjusting your search.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $destinations->links('vendor.pagination.tailwind') }}
        </div>
    </section>
</div>
</x-layouts.app.header>
