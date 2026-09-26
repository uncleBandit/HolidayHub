<div>
<div class="min-h-screen bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 p-6 sm:p-12 font-sans antialiased">
    <div class="max-w-7xl mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all duration-500">

        {{-- Header Section --}}
        <div class="relative text-center py-16 px-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white overflow-hidden">
            <div class="absolute inset-0 opacity-20" style="background-image: url('https://images.unsplash.com/photo-1616782298285-b9f1d07c42f0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcwODZ8MHwxfHNlYXJjaHwxMHx8YmVkJTIwYW5kJTIwYnJlYWtmYXN0fGVufDB8fHx8MTY5OTU4MjYwNnww&ixlib=rb-4.0.3&q=80&w=1080'); background-size: cover; background-position: center;"></div>
            <div class="relative z-10">
                <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight leading-tight mb-2 drop-shadow-lg">
                    Create Your Listing
                </h1>
                <p class="text-xl md:text-2xl font-light opacity-90 drop-shadow-sm max-w-2xl mx-auto">
                    A few simple steps to bring your charming Bed & Breakfast to life.
                </p>
            </div>
        </div>

        {{-- Main Form Container --}}
        <div class="p-6 md:p-12 lg:p-16">
            {{-- Progress Stepper --}}
            <div class="flex items-center justify-between space-x-2 sm:space-x-4 mb-16 relative after:absolute after:bottom-1/2 after:left-0 after:right-0 after:h-0.5 after:bg-gray-200 after:z-0">
                @php
                    $stepNames = ['Basic Info', 'Property Details', 'Location', 'Media', 'Policies'];
                    $stepIcons = ['📝', '🏠', '🗺️', '📸', '📄'];
                @endphp
                @foreach(range(1, 5) as $s)
                    <div class="flex-1 text-center z-10 transition-transform duration-300 {{ $step === $s ? 'scale-110' : '' }}">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto rounded-full flex items-center justify-center font-bold text-2xl
                            {{ $step >= $s ? 'bg-indigo-600 text-white shadow-lg ring-4 ring-indigo-200' : 'bg-gray-200 text-gray-500 border-2 border-gray-300' }} transition-all duration-500">
                            @if ($step > $s)
                                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                <span class="text-3xl">{{ $stepIcons[$s-1] }}</span>
                            @endif
                        </div>
                        <div class="mt-3 text-sm font-semibold transition-colors duration-300 {{ $step >= $s ? 'text-indigo-700' : 'text-gray-500' }}">
                            {{ $stepNames[$s-1] }}
                        </div>
                    </div>
                    @if ($s < 5)
                        <div class="flex-1 h-1 bg-gray-300 rounded-full transition-colors duration-300 ease-in-out {{ $step > $s ? 'bg-indigo-600' : '' }}"></div>
                    @endif
                @endforeach
            </div>

            <form wire:submit.prevent="save">
                {{-- Step 1: Basic Info --}}
                @if($step === 1)
                    <div class="space-y-8 p-8 bg-gray-50 rounded-2xl shadow-inner">
                        <h2 class="text-3xl font-bold text-gray-800">1. Tell Us About Your Place</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name of Your Bed & Breakfast</label>
                                <input id="name" type="text" wire:model.defer="name" placeholder="E.g., The Rustic Charm Inn" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Unique Web Address (Slug)</label>
                                <input id="slug" type="text" wire:model.defer="slug" placeholder="rustic-charm-inn" class="w-full rounded-xl border-gray-300 shadow-sm p-3 bg-gray-100 cursor-not-allowed">
                                @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="description" wire:model.defer="description" rows="6" placeholder="Describe the atmosphere, unique features, and what makes your place special." class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                {{-- Step 2: Property Details --}}
                @if($step === 2)
                    <div class="space-y-8 p-8 bg-gray-50 rounded-2xl shadow-inner">
                        <h2 class="text-3xl font-bold text-gray-800">2. Property Details</h2>
                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <label for="rooms" class="block text-sm font-medium text-gray-700 mb-1">Number of Rooms</label>
                                <input id="rooms" type="number" wire:model.defer="rooms" placeholder="e.g., 5" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('rooms') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="max_guests" class="block text-sm font-medium text-gray-700 mb-1">Maximum Guests</label>
                                <input id="max_guests" type="number" wire:model.defer="max_guests" placeholder="e.g., 10" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('max_guests') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="price_per_night" class="block text-sm font-medium text-gray-700 mb-1">Price per Night (USD)</label>
                                <input id="price_per_night" type="number" step="0.01" wire:model.defer="price_per_night" placeholder="e.g., 125.00" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('price_per_night') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex items-center justify-start gap-4">
                            <input type="checkbox" wire:model.defer="has_breakfast" id="has_breakfast" class="h-6 w-6 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300">
                            <label for="has_breakfast" class="text-base font-medium text-gray-700 cursor-pointer">Includes Breakfast?</label>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Location --}}
                @if($step === 3)
                    <div class="space-y-8 p-8 bg-gray-50 rounded-2xl shadow-inner">
                        <h2 class="text-3xl font-bold text-gray-800">3. Where to Find You</h2>
                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                                <input id="address" type="text" wire:model.defer="address" placeholder="123 Main Street" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('address') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                <input id="city" type="text" wire:model.defer="city" placeholder="Springfield" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('city') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                <input id="country" type="text" wire:model.defer="country" placeholder="United States" class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('country') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                                        <input id="latitude" type="text" wire:model.defer="latitude" placeholder="34.0522" class="w-full rounded-xl border-gray-300 shadow-sm p-3 bg-gray-100 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label for="longitude" class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                                        <input id="longitude" type="text" wire:model.defer="longitude" placeholder="-118.2437" class="w-full rounded-xl border-gray-300 shadow-sm p-3 bg-gray-100 cursor-not-allowed">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl overflow-hidden shadow-lg border-2 border-indigo-200" style="height: 400px;">
                            <div id="map" wire:ignore class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500 text-lg">
                                Interactive Map Loading...
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 4: Media --}}
                @if($step === 4)
                    <div class="space-y-8 p-8 bg-gray-50 rounded-2xl shadow-inner">
                        <h2 class="text-3xl font-bold text-gray-800">4. Showcase Your Property</h2>
                        <div class="space-y-6">
                            <div>
                                <label for="cover_image" class="block text-sm font-medium text-gray-700 mb-1">Cover Image</label>
                                <p class="text-xs text-gray-500 mb-2">This is the first photo travelers will see. Make it count!</p>
                                <input id="cover_image" type="file" wire:model="cover_image" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-colors">
                                @error('cover_image') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                @if ($cover_image)
                                    <img src="{{ $cover_image->temporaryUrl() }}" class="mt-4 w-full h-64 object-cover rounded-xl shadow-md border-4 border-indigo-200">
                                @endif
                            </div>

                            <hr class="border-gray-300">

                            <div>
                                <label for="gallery" class="block text-sm font-medium text-gray-700 mb-1">Gallery Images</label>
                                <p class="text-xs text-gray-500 mb-2">Upload multiple photos to show off every angle of your beautiful property.</p>
                                <input id="gallery" type="file" wire:model="gallery" multiple class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-colors">
                                @error('gallery') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                    @foreach ($gallery as $img)
                                        <div class="relative group">
                                            <img src="{{ $img->temporaryUrl() }}" class="w-full h-32 object-cover rounded-lg shadow-sm border-2 border-gray-200">
                                            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 5: Amenities & Policies --}}
                @if($step === 5)
                    <div class="space-y-8 p-8 bg-gray-50 rounded-2xl shadow-inner">
                        <h2 class="text-3xl font-bold text-gray-800">5. Amenities & Policies</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="font-bold text-xl text-gray-700 mb-4">Amenities</h3>
                                <p class="text-sm text-gray-500 mb-4">Select all the features and comforts you offer.</p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                    @foreach($amenities as $amenity)
                                        <label class="flex items-center space-x-3 p-4 bg-white rounded-xl shadow-sm cursor-pointer hover:bg-indigo-50 transition-colors border border-gray-200">
                                            <input type="checkbox" wire:model="selectedAmenities" value="{{ $amenity->id }}" class="form-checkbox h-6 w-6 text-indigo-600 rounded-md border-gray-300 focus:ring-indigo-500">
                                            <span class="text-sm font-medium text-gray-700">{{ $amenity->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="space-y-6">
                                <div>
                                    <h3 class="font-bold text-xl text-gray-700 mb-4">Policies</h3>
                                    <p class="text-sm text-gray-500 mb-2">Specify your check-in, check-out, and cancellation policies.</p>
                                    <textarea wire:model.defer="policies" rows="5" placeholder='e.g., {"check_in":"3:00 PM","check_out":"11:00 AM","cancellation":"Free cancellation up to 48 hours before check-in."}' class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                </div>

                                <div>
                                    <h3 class="font-bold text-xl text-gray-700 mb-4">Seasonal Pricing</h3>
                                    <p class="text-sm text-gray-500 mb-2">Set different prices for specific date ranges (e.g., holidays, peak season).</p>
                                    <textarea wire:model.defer="seasonal_pricing" rows="5" placeholder='e.g., [{"start_date":"2025-06-01","end_date":"2025-08-31","price":120},{"start_date":"2025-12-20","end_date":"2025-12-31","price":150}]' class="w-full rounded-xl border-gray-300 shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Navigation Buttons --}}
                <div class="flex justify-between items-center mt-12 pt-8 border-t border-gray-200">
                    @if($step > 1)
                        <button type="button" wire:click="prevStep" class="px-6 py-3 rounded-full shadow-md text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none transition-all flex items-center">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg> Back
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if($step < 5)
                        <button type="button" wire:click="nextStep" class="px-8 py-3 rounded-full shadow-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none transform transition-transform duration-300 hover:scale-105 flex items-center ml-auto">
                            Next <svg class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    @else
                        <button type="submit" class="px-8 py-3 rounded-full shadow-lg text-sm font-medium text-white bg-green-500 hover:bg-green-600 focus:outline-none transform transition-transform duration-300 hover:scale-105 flex items-center ml-auto">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Publish Listing
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Leaflet Map Script --}}
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script>
    document.addEventListener('livewire:load', function () {
        const mapElement = document.getElementById('map');
        if (mapElement) {
            const map = L.map('map').setView([0, 0], 2);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const marker = L.marker([0, 0], { draggable: true }).addTo(map);

            marker.on('dragend', function(e) {
                const latlng = marker.getLatLng();
                @this.set('latitude', latlng.lat);
                @this.set('longitude', latlng.lng);
            });

            Livewire.on('updateMap', (lat, lng) => {
                const latlng = [lat, lng];
                marker.setLatLng(latlng);
                map.setView(latlng, 12);
            });

            @if ($latitude && $longitude)
                Livewire.emit('updateMap', @js($latitude), @js($longitude));
            @endif
        }
    });
</script>
@endpush
</div>
