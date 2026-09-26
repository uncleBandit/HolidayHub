<div>
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen text-gray-800 dark:text-gray-200 font-sans antialiased p-4 sm:p-6 lg:p-8"
x-data="{ showRoomModal: @entangle('showRoomModal'), showServiceModal: @entangle('showServiceModal') }"
wire:poll.10s>

{{-- Dashboard Header --}}
<div class="max-w-7xl mx-auto mb-10">
    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white mb-2">Provider Dashboard</h1>
    <p class="text-lg text-gray-600 dark:text-gray-400">
        Manage your rooms, services, and bookings with ease.
    </p>
</div>

{{-- Stats Panel --}}
<div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex flex-col items-center justify-center text-center transition-transform duration-300 hover:scale-[1.03]">
        <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Total Rooms</span>
        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['totalRooms'] }}</span>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex flex-col items-center justify-center text-center transition-transform duration-300 hover:scale-[1.03]">
        <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Total Services</span>
        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['totalServices'] }}</span>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex flex-col items-center justify-center text-center transition-transform duration-300 hover:scale-[1.03]">
        <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Confirmed</span>
        <span class="text-3xl font-bold text-green-500">{{ $stats['confirmedBookings'] }}</span>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex flex-col items-center justify-center text-center transition-transform duration-300 hover:scale-[1.03]">
        <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Pending</span>
        <span class="text-3xl font-bold text-yellow-500">{{ $stats['pendingBookings'] }}</span>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex flex-col items-center justify-center text-center transition-transform duration-300 hover:scale-[1.03]">
        <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Cancelled</span>
        <span class="text-3xl font-bold text-red-500">{{ $stats['cancelledBookings'] }}</span>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex flex-col items-center justify-center text-center transition-transform duration-300 hover:scale-[1.03]">
        <span class="text-sm text-gray-500 dark:text-gray-400 font-semibold mb-1">Today Revenue</span>
        <span class="text-3xl font-bold text-indigo-500">${{ number_format($stats['todayRevenue']) }}</span>
    </div>
</div>

<div class="max-w-7xl mx-auto">
    {{-- Rooms & Services Management --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

    {{-- Hotels Section --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-2xl">Hotels</h3>
            <a href="{{ route('provider.hotel-create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-full font-semibold transition-colors duration-200 shadow-md">
                <i class="fas fa-plus mr-1"></i> Add Hotel
            </a>
        </div>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($provider->accommodations()->hotels()->with('bookable')->get() as $accommodation)
                <li class="py-4 flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold">{{ $accommodation->bookable->name }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $accommodation->reviews_count }} reviews
                            @if($accommodation->avg_rating)
                                · {{ $accommodation->avg_rating }} stars
                            @endif
                        </p>
                    </div>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                        ${{ number_format($accommodation->avg_price_per_night, 2) }}
                        <span class="text-xs text-gray-400 dark:text-gray-500">/ night</span>
                    </p>
                </li>
            @empty
                <li class="py-4 text-center text-gray-500">No hotels found.</li>
            @endforelse
        </ul>
    </div>

     {{-- Villas Section --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-2xl">Villas</h3>
            <a href="{{ route('provider.villa-create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-full font-semibold transition-colors duration-200 shadow-md">
                <i class="fas fa-plus mr-1"></i> Add Villa
            </a>
        </div>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($provider->accommodations()->villas()->with('bookable')->get() as $accommodation)
                <li class="py-4 flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold">{{ $accommodation->bookable->name }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $accommodation->reviews_count }} reviews
                            @if($accommodation->avg_rating)
                                · {{ $accommodation->avg_rating }} stars
                            @endif
                        </p>
                    </div>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                        ${{ number_format($accommodation->avg_price_per_night, 2) }}
                        <span class="text-xs text-gray-400 dark:text-gray-500">/ night</span>
                    </p>
                </li>
            @empty
                <li class="py-4 text-center text-gray-500">No villas found.</li>
            @endforelse
        </ul>
    </div>

    {{-- Bed & Breakfast Section --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-2xl">Bed & Breakfasts</h3>
            <a href="{{ route('provider.bedandbreakfast-create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-full font-semibold transition-colors duration-200 shadow-md">
                <i class="fas fa-plus mr-1"></i> Add B&B
            </a>
        </div>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($provider->accommodations()->bedAndBreakfasts()->with('bookable')->get() as $accommodation)
                <li class="py-4 flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold">{{ $accommodation->bookable->name }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                             {{ $accommodation->reviews_count }} reviews
                            @if($accommodation->avg_rating)
                                · {{ $accommodation->avg_rating }} stars
                            @endif
                        </p>
                    </div>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                        ${{ number_format($accommodation->avg_price_per_night, 2) }}
                        <span class="text-xs text-gray-400 dark:text-gray-500">/ night</span>
                    </p>
                </li>
            @empty
                <li class="py-4 text-center text-gray-500">No Bed & Breakfasts found.</li>
            @endforelse
        </ul>
    </div>

    {{-- Services Section (retained from original) --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-2xl">Services</h3>
            <button @click="showServiceModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-full font-semibold transition-colors duration-200 shadow-md">
                <i class="fas fa-plus mr-1"></i> Add Service
            </button>
        </div>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($services as $service)
                <li class="py-4 flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold">{{ $service->name }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">${{ number_format($service->price) }}</p>
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ Str::limit($service->description, 30) }}</span>
                </li>
            @empty
                <li class="py-4 text-center text-gray-500">No services found.</li>
            @endforelse
        </ul>
    </div>
</div>

    {{-- Bookings Panel with Filters and Table --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl">
        <h2 class="text-2xl font-bold mb-4">Recent Bookings</h2>

        {{-- Filters --}}
        <div class="flex flex-col md:flex-row items-center gap-4 mb-6">
            <div class="relative w-full md:w-1/3">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" placeholder="Search customer..." wire:model.debounce.500ms="search"
                       class="w-full pl-12 pr-4 py-3 rounded-full border-none bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-colors duration-200">
            </div>
            <select wire:model="status" class="w-full md:w-1/6 rounded-full border-none py-3 px-4 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition-colors duration-200">
                <option value="">All Statuses</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input type="date" wire:model="dateFrom" class="w-full md:w-1/6 rounded-full border-none py-3 px-4 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition-colors duration-200" />
            <input type="date" wire:model="dateTo" class="w-full md:w-1/6 rounded-full border-none py-3 px-4 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 transition-colors duration-200" />
        </div>

        {{-- Loading Indicator --}}
        <div wire:loading class="text-center mb-6 text-indigo-600 dark:text-indigo-400 font-semibold text-lg animate-pulse">
            Loading...
        </div>

        {{-- Bookings Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto rounded-xl overflow-hidden">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr class="border-b border-gray-100 dark:border-gray-700 transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-4 whitespace-nowrap">{{ $booking->customer->name }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">{{ $booking->room->name ?? $booking->service->name }}</td>
                            <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">${{ number_format($booking->total_price) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs rounded-full font-semibold
                                    {{ $booking->status === 'confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100' : '' }}
                                    {{ $booking->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100' : '' }}
                                    {{ $booking->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100' : '' }}">
                                    {{ ucfirst($booking->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">{{ $booking->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-4 whitespace-nowrap space-x-2">
                                @if($booking->status !== 'cancelled')
                                    <button wire:click="updateBookingStatus({{ $booking->id }}, 'confirmed')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full text-sm transition-colors duration-200">Confirm</button>
                                    <button wire:click="updateBookingStatus({{ $booking->id }}, 'cancelled')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full text-sm transition-colors duration-200">Cancel</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <p class="text-xl">No bookings found matching your criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-8">
            {{ $bookings->links() }}
        </div>
    </div>
</div>

{{-- Modals --}}
{{-- Room Modal --}}
<div x-show="showRoomModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 w-full max-w-md shadow-2xl" @click.away="showRoomModal = false">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Add New Room</h2>
            <button @click="showRoomModal = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form wire:submit.prevent="saveRoom" class="space-y-4">
            <div>
                <input type="text" placeholder="Room Name" wire:model="newRoom.name" required class="w-full border-none rounded-full px-4 py-3 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
            </div>
            <div>
                <input type="number" placeholder="Capacity" wire:model="newRoom.capacity" required class="w-full border-none rounded-full px-4 py-3 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
            </div>
            <div>
                <input type="number" placeholder="Price" wire:model="newRoom.price" required class="w-full border-none rounded-full px-4 py-3 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
            </div>
            <div>
                <input type="text" placeholder="Facilities (comma-separated)" wire:model="newRoom.facilities" class="w-full border-none rounded-full px-4 py-3 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" @click="showRoomModal = false" class="px-6 py-3 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 font-semibold transition-colors duration-200">Cancel</button>
                <button type="submit" class="px-6 py-3 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition-colors duration-200">Save Room</button>
            </div>
        </form>
    </div>
</div>

{{-- Service Modal --}}
<div x-show="showServiceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 w-full max-w-md shadow-2xl" @click.away="showServiceModal = false">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Add New Service</h2>
            <button @click="showServiceModal = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form wire:submit.prevent="saveService" class="space-y-4">
            <div>
                <input type="text" placeholder="Service Name" wire:model="newService.name" required class="w-full border-none rounded-full px-4 py-3 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
            </div>
            <div>
                <textarea placeholder="Description" wire:model="newService.description" class="w-full border-none rounded-xl px-4 py-3 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"></textarea>
            </div>
            <div>
                <input type="number" placeholder="Price" wire:model="newService.price" required class="w-full border-none rounded-full px-4 py-3 bg-gray-100 dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" @click="showServiceModal = false" class="px-6 py-3 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 font-semibold transition-colors duration-200">Cancel</button>
                <button type="submit" class="px-6 py-3 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition-colors duration-200">Save Service</button>
            </div>
        </form>
    </div>
</div>

</div>
</div>
