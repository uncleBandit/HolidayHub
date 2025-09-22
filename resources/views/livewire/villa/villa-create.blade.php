<div>
{{-- resources/views/livewire/villa/villa-create.blade.php --}}
<div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center p-4 sm:p-8">
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-6xl w-full transform transition-all duration-500">
        <div class="lg:flex">
            {{-- Visual Sidebar --}}
            <div class="hidden lg:block lg:w-1/3 bg-cover bg-center relative p-8 flex flex-col justify-end" style="background-image: url('https://images.unsplash.com/photo-1628126786835-263a0a382c73?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcwODZ8MHwxfHNlYXJjaHw0fHx2aWxsYSUyMHBvb2wlMjBsaWZlc3R5bGV8ZW58MHx8fHwxNjk5NTgyNzU3fDA&ixlib=rb-4.0.3&q=80&w=1080');">
                <div class="relative z-10 text-white">
                    <h2 class="text-4xl font-extrabold leading-tight tracking-wide mb-4 drop-shadow-md">List Your Dream Villa</h2>
                    <p class="text-lg font-light opacity-90 drop-shadow-sm">A seamless experience for a seamless booking. Follow these steps to get your property live.</p>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
            </div>

            {{-- Main Form Content --}}
            <div class="lg:w-2/3 p-8 sm:p-12">
                <div class="mb-12">
                    <h1 class="text-4xl font-extrabold text-gray-900 leading-tight">Create a New Villa Listing</h1>
                    <p class="text-gray-600 mt-2">Let's get your beautiful villa ready for guests!</p>
                </div>

                {{-- Progress Indicator with Icons --}}
                <div class="flex items-center justify-between mb-12 relative after:absolute after:bottom-1/2 after:left-0 after:right-0 after:h-0.5 after:bg-gray-200 after:z-0">
                    @php
                        $steps = [
                            1 => ['name' => 'Core Info', 'icon' => '🏡'],
                            2 => ['name' => 'Location', 'icon' => '🗺️'],
                            3 => ['name' => 'Features', 'icon' => '✨'],
                            4 => ['name' => 'Media', 'icon' => '🖼️'],
                            5 => ['name' => 'Policies', 'icon' => '📜']
                        ];
                    @endphp
                    @foreach($steps as $s => $data)
                        <div class="flex-1 text-center z-10 transition-transform duration-300 {{ $step === $s ? 'scale-110' : '' }}">
                            <div class="w-14 h-14 mx-auto rounded-full flex items-center justify-center font-bold text-2xl
                                {{ $step >= $s ? 'bg-indigo-600 text-white shadow-lg ring-4 ring-indigo-200' : 'bg-white text-gray-500 border-2 border-gray-200' }}">
                                {{ $data['icon'] }}
                            </div>
                            <span class="block mt-2 text-sm font-semibold transition-colors duration-300 {{ $step >= $s ? 'text-indigo-700' : 'text-gray-500' }}">
                                {{ $data['name'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Form Cards --}}
                <form wire:submit.prevent="save" class="space-y-8">
                    {{-- Step 1: Core Info --}}
                    @if($step === 1)
                        <div class="bg-gray-50 rounded-2xl p-8 space-y-6 shadow-inner">
                            <h2 class="text-2xl font-bold text-gray-800">Villa Core Information</h2>
                            <p class="text-gray-500">Start with the basics. Every great villa has a great story.</p>

                            <div>
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Villa Name</label>
                                <input id="name" type="text" wire:model.lazy="name" placeholder="E.g., The Ocean View Retreat" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors p-3">
                                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="slug" class="block text-sm font-semibold text-gray-700 mb-1">Slug</label>
                                <input id="slug" type="text" wire:model="slug" placeholder="slug-auto-generated" readonly class="w-full rounded-xl border-gray-300 bg-gray-100 shadow-sm p-3 cursor-not-allowed">
                                @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                                <textarea id="description" wire:model.lazy="description" rows="5" placeholder="Highlight the unique features and charm of your villa." class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors p-3"></textarea>
                                @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif

                    {{-- Step 2: Location with Map --}}
                    @if($step === 2)
                        <div class="bg-gray-50 rounded-2xl p-8 space-y-6 shadow-inner">
                            <h2 class="text-2xl font-bold text-gray-800">Villa Location</h2>
                            <p class="text-gray-500">Where can guests find your stunning property?</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <input type="text" wire:model.lazy="address" placeholder="Address" class="rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                <input type="text" wire:model.lazy="city" placeholder="City" class="rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                <input type="text" wire:model.lazy="country" placeholder="Country" class="rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                <div class="flex gap-4">
                                    <input type="text" wire:model.lazy="latitude" placeholder="Latitude" class="w-1/2 rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                    <input type="text" wire:model.lazy="longitude" placeholder="Longitude" class="w-1/2 rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            {{-- Interactive Map --}}
                            <div class="rounded-2xl overflow-hidden shadow-lg mt-4">
                                <div id="map" wire:ignore class="w-full h-80"></div>
                            </div>
                            <p class="text-gray-500 text-sm mt-2 text-center">Drag the marker on the map to automatically set the coordinates.</p>
                        </div>
                    @endif

                    {{-- Step 3: Features & Pricing --}}
                    @if($step === 3)
                        <div class="bg-gray-50 rounded-2xl p-8 space-y-6 shadow-inner">
                            <h2 class="text-2xl font-bold text-gray-800">Features & Pricing</h2>
                            <p class="text-gray-500">Details that make your villa unique and determine its value.</p>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                                <input type="number" wire:model.lazy="bedrooms" min="1" placeholder="Bedrooms" class="rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                <input type="number" wire:model.lazy="bathrooms" min="1" placeholder="Bathrooms" class="rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                <input type="number" wire:model.lazy="max_guests" min="1" placeholder="Max Guests" class="rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="price" class="block text-sm font-semibold text-gray-700 mb-1">Average Price per Night (USD)</label>
                                <input id="price" type="number" wire:model.lazy="avg_price_per_night" placeholder="E.g., 250.00" class="w-full rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('avg_price_per_night') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center space-x-6 mt-4">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" wire:model="has_private_pool" class="form-checkbox h-5 w-5 text-indigo-600 rounded-md">
                                    <span class="text-gray-700 font-medium">Private Pool</span>
                                </label>
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" wire:model="is_featured" class="form-checkbox h-5 w-5 text-indigo-600 rounded-md">
                                    <span class="text-gray-700 font-medium">Featured Villa</span>
                                </label>
                            </div>
                        </div>
                    @endif

                    {{-- Step 4: Media --}}
                    @if($step === 4)
                        <div class="bg-gray-50 rounded-2xl p-8 space-y-6 shadow-inner">
                            <h2 class="text-2xl font-bold text-gray-800">Villa Media</h2>
                            <p class="text-gray-500">High-quality photos are crucial for a great listing. Show off your villa!</p>

                            <div>
                                <label for="cover_image" class="block text-sm font-semibold text-gray-700 mb-1">Cover Image</label>
                                <input id="cover_image" type="file" wire:model="cover_image" class="w-full">
                                @error('cover_image') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                @if ($cover_image)
                                    <img src="{{ $cover_image->temporaryUrl() }}" class="mt-4 w-64 rounded-xl shadow-lg border-4 border-indigo-200 object-cover">
                                @endif
                            </div>

                            <div>
                                <label for="gallery" class="block text-sm font-semibold text-gray-700 mb-1">Gallery Images</label>
                                <input id="gallery" type="file" wire:model="gallery" multiple class="w-full">
                                @error('gallery') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mt-4">
                                    @foreach($gallery as $img)
                                        <img src="{{ $img->temporaryUrl() }}" class="w-full h-32 object-cover rounded-lg shadow-sm">
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Step 5: Amenities & Policies --}}
                    @if($step === 5)
                        <div class="bg-gray-50 rounded-2xl p-8 space-y-6 shadow-inner">
                            <h2 class="text-2xl font-bold text-gray-800">Amenities & Policies</h2>
                            <p class="text-gray-500">Define the rules and comforts that guests can expect.</p>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Amenities</label>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    @foreach($amenities as $amenity)
                                        <label class="flex items-center space-x-3 border rounded-xl p-4 cursor-pointer hover:bg-white transition-colors">
                                            <input type="checkbox" wire:model="selectedAmenities" value="{{ $amenity->id }}" class="form-checkbox h-5 w-5 text-indigo-600 rounded-md">
                                            <span class="text-gray-800 font-medium">{{ $amenity->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Check-in Time</label>
                                    <input type="time" wire:model.lazy="policies.check_in" class="w-full rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Check-out Time</label>
                                    <input type="time" wire:model.lazy="policies.check_out" class="w-full rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Cancellation Policy</label>
                                    <input type="text" wire:model.lazy="policies.cancellation" placeholder="E.g., Flexible" class="w-full rounded-xl border-gray-300 p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Navigation Buttons --}}
                    <div class="flex justify-between items-center pt-8">
                        @if($step > 1)
                            <button type="button" wire:click="previousStep" class="px-6 py-3 text-lg font-semibold text-gray-700 rounded-full transition-colors duration-300 hover:bg-gray-200">
                                ← Previous
                            </button>
                        @endif

                        @if($step < 5)
                            <button type="button" wire:click="nextStep" class="ml-auto px-8 py-3 bg-indigo-600 text-white text-lg font-semibold rounded-full shadow-lg transform transition-transform duration-300 hover:scale-105 hover:bg-indigo-700">
                                Next Step →
                            </button>
                        @else
                            <button type="submit" class="ml-auto px-8 py-3 bg-green-500 text-white text-lg font-semibold rounded-full shadow-lg transform transition-transform duration-300 hover:scale-105 hover:bg-green-600">
                                Publish Villa 🎉
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Leaflet Map Script --}}
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script>
    document.addEventListener('livewire:load', function () {
        // Initialize map with a default view
        const map = L.map('map').setView([0, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Add a draggable marker
        const marker = L.marker([0, 0], { draggable: true }).addTo(map);

        // Update Livewire properties on drag end
        marker.on('dragend', function(e) {
            const latlng = marker.getLatLng();
            @this.set('latitude', latlng.lat);
            @this.set('longitude', latlng.lng);
        });

        // Listen for Livewire event to update map view
        Livewire.on('updateMap', (lat, lng) => {
            const latlng = [lat, lng];
            marker.setLatLng(latlng);
            map.setView(latlng, 12); // Zoom to the new location
        });

        // Initial map setup if coordinates already exist
        @if ($latitude && $longitude)
            Livewire.emit('updateMap', @js($latitude), @js($longitude));
        @endif
    });
</script>
@endpush
</div>
