<div>
<div class="container mx-auto px-4 py-8">

    <!-- Hero Section -->
    <div class="relative rounded-2xl overflow-hidden shadow-lg mb-8">
        <img
            src="{{ $destination->cover_image ?? 'https://via.placeholder.com/1200x500' }}"
            alt="{{ $destination->name }}"
            class="w-full h-80 object-cover"
        >
        <div class="absolute inset-0 bg-black bg-opacity-30 flex items-end">
            <div class="p-6 text-white">
                <h1 class="text-4xl font-bold">{{ $destination->name }}</h1>
                <p class="text-lg mt-2">{{ $destination->city }}, {{ $destination->country }}</p>
            </div>
        </div>
    </div>

    <!-- Highlights + Quick Stats -->
    <div class="mb-8 grid gap-6 md:grid-cols-3">
        <div class="p-6 bg-white rounded-2xl shadow">
            <h2 class="text-lg font-semibold mb-2">Best Season</h2>
            <p class="text-gray-600">{{ $destination->best_season ?? 'Year-round' }}</p>
        </div>
        <div class="p-6 bg-white rounded-2xl shadow">
            <h2 class="text-lg font-semibold mb-2">Average Rating</h2>
            <p class="text-yellow-600 font-bold text-xl">
                ⭐ {{ number_format($destination->average_rating, 1) ?? 'N/A' }}
            </p>
        </div>
        <div class="p-6 bg-white rounded-2xl shadow">
            <h2 class="text-lg font-semibold mb-2">Quick Facts</h2>
            <ul class="text-gray-600 text-sm space-y-1">
                <li>{{ $destination->hotels_count }} Hotels</li>
                <li>{{ $destination->activities_count }} Activities</li>
                <li>{{ $destination->reviews_count }} Reviews</li>
            </ul>
        </div>
    </div>

    <!-- Tabs -->
    <div x-data="{ tab: 'overview' }">
        <div class="border-b mb-6 flex space-x-6">
            <button @click="tab = 'overview'"
                :class="tab === 'overview' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                class="pb-2 border-b-2 font-medium">
                Overview
            </button>
            <button @click="tab = 'hotels'"
                :class="tab === 'hotels' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                class="pb-2 border-b-2 font-medium">
                Hotels
            </button>
            <button @click="tab = 'bnb'"
                :class="tab === 'bnb' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                class="pb-2 border-b-2 font-medium">
                Bed & Breakfast
            </button>

            <button @click="tab = 'activities'"
                :class="tab === 'activities' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                class="pb-2 border-b-2 font-medium">
                Activities
            </button>

            <button @click="tab = 'packages'"
                :class="tab === 'packages' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                class="pb-2 border-b-2 font-medium">
                    Packages
            </button>
            <button @click="tab = 'reviews'"
                :class="tab === 'reviews' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                class="pb-2 border-b-2 font-medium">
                Reviews
            </button>
        </div>

        <!-- Overview -->
        <div x-show="tab === 'overview'" class="space-y-6">
            <h2 class="text-2xl font-bold">About {{ $destination->name }}</h2>
            <p class="text-gray-700">{{ $destination->description }}</p>

            @if(!empty($destination->highlights))
                <div>
                    <h3 class="text-lg font-semibold mt-4">Highlights</h3>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach($destination->highlights as $highlight)
                            <span class="bg-indigo-50 text-indigo-600 text-sm px-3 py-1 rounded-lg">
                                {{ $highlight }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Hotels -->
       <div x-show="tab === 'hotels'" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
         @forelse($destination->hotels as $accommodation)
          @php $hotel = $accommodation->bookable; @endphp
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <img src="{{ $hotel->cover_image ?? 'https://via.placeholder.com/600x400' }}"
                 alt="{{ $hotel->name }}"
                 class="w-full h-40 object-cover">
            <div class="p-4">
                <h3 class="text-lg font-semibold">{{ $hotel->name }}</h3>
                <p class="text-gray-600 text-sm">
                    ⭐ {{ $hotel->rating ?? 'N/A' }} · {{ $hotel->stars }}★
                </p>
            </div>
        </div>
      @empty
        <p class="text-gray-500">No hotels available yet.</p>
      @endforelse
   </div>

        <!-- Bed & Breakfast -->
<div x-show="tab === 'bnb'" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
    @forelse($destination->bedAndBreakfasts as $accommodation)
        @php $bnb = $accommodation->bookable; @endphp
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <img src="{{ $bnb->main_image ?? 'https://via.placeholder.com/600x400' }}"
                 alt="{{ $bnb->name }}"
                 class="w-full h-40 object-cover">
            <div class="p-4">
                <h3 class="text-lg font-semibold">{{ $bnb->name }}</h3>
                <p class="text-gray-600 text-sm">
                    From ${{ $bnb->price_per_night }} / night · Max {{ $bnb->max_guests }} guests
                </p>
            </div>
        </div>
    @empty
        <p class="text-gray-500">No B&Bs available yet.</p>
    @endforelse
</div>


        <!-- Activities -->
        <div x-show="tab === 'activities'" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($destination->activities as $activity)
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <img src="{{ $activity->cover_image ?? 'https://via.placeholder.com/600x400' }}"
                         alt="{{ $activity->title }}"
                         class="w-full h-40 object-cover">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold">{{ $activity->title }}</h3>
                        <p class="text-gray-600 text-sm">
                            {{ $activity->duration ?? 'Flexible' }} · {{ $activity->price ?? 'N/A' }} {{ $activity->currency ?? '' }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500">No activities listed yet.</p>
            @endforelse
        </div>

        <!--Packages-->
             <div x-show="tab === 'packages'" x-cloak>
            <livewire:package.package-search-list :destinationId="$destination->id" />
            </div>



        <!-- Reviews -->
        <div x-show="tab === 'reviews'" class="space-y-6">
            @forelse($destination->reviews as $review)
                <div class="bg-white p-6 rounded-xl shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold">{{ $review->user->name }}</h3>
                        <span class="text-yellow-600">⭐ {{ $review->rating }}</span>
                    </div>
                    <p class="mt-2 text-gray-700">{{ $review->comment }}</p>
                    @if(!empty($review->photos))
                        <div class="flex gap-2 mt-3">
                            @foreach($review->photos as $photo)
                                <img src="{{ $photo }}" class="w-20 h-20 object-cover rounded-lg">
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-gray-500">No reviews yet. Be the first to share your experience!</p>
            @endforelse
        </div>
    </div>
</div>
</div>
