<div>
{{-- resources/views/livewire/villa/villa-create.blade.php --}}
<div class="max-w-7xl mx-auto py-10 px-4 space-y-8">

    {{-- Wizard Stepper --}}
    <div class="flex justify-between mb-8">
        @foreach(range(1,5) as $i)
            <div class="flex-1 text-center relative">
                <div class="w-10 h-10 mx-auto rounded-full flex items-center justify-center
                    {{ $step >= $i ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                    {{ $i }}
                </div>
                <span class="absolute top-12 left-1/2 transform -translate-x-1/2 text-xs font-semibold {{ $step >= $i ? 'text-green-600' : 'text-gray-500' }}">
                    @switch($i)
                        @case(1) Core Info @break
                        @case(2) Location @break
                        @case(3) Features @break
                        @case(4) Media @break
                        @case(5) Amenities & Policies @break
                    @endswitch
                </span>
            </div>
        @endforeach
    </div>

    {{-- Wizard Step Cards --}}
    <div class="space-y-6">

        {{-- Step 1: Core Info --}}
        @if($step === 1)
            <div class="bg-white shadow-lg rounded-lg p-6 space-y-4">
                <h2 class="text-xl font-bold mb-4">Villa Core Information</h2>
                <input type="text" wire:model="name" placeholder="Villa Name" class="w-full border rounded p-3 focus:ring-2 focus:ring-green-500">
                @error('name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                <input type="text" wire:model="slug" placeholder="Slug (auto-generated)" class="w-full border rounded p-3 focus:ring-2 focus:ring-green-500">
                @error('slug') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                <textarea wire:model="description" rows="4" placeholder="Describe your villa..." class="w-full border rounded p-3 focus:ring-2 focus:ring-green-500"></textarea>
                @error('description') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- Step 2: Location with Map --}}
        @if($step === 2)
            <div class="bg-white shadow-lg rounded-lg p-6 space-y-4">
                <h2 class="text-xl font-bold mb-4">Villa Location</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" wire:model="address" placeholder="Address" class="border rounded p-3">
                    <input type="text" wire:model="city" placeholder="City" class="border rounded p-3">
                    <input type="text" wire:model="country" placeholder="Country" class="border rounded p-3">
                    <input type="text" wire:model="latitude" placeholder="Latitude" class="border rounded p-3">
                    <input type="text" wire:model="longitude" placeholder="Longitude" class="border rounded p-3">
                </div>

                {{-- Interactive Map --}}
                <div id="map" class="w-full h-64 rounded-lg border mt-4"></div>
                <p class="text-gray-500 text-sm mt-2">Drag the marker to set the villa location.</p>
            </div>
        @endif

        {{-- Step 3: Features & Pricing --}}
        @if($step === 3)
            <div class="bg-white shadow-lg rounded-lg p-6 space-y-4">
                <h2 class="text-xl font-bold mb-4">Villa Features</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <input type="number" wire:model="bedrooms" min="1" placeholder="Bedrooms" class="border rounded p-3">
                    <input type="number" wire:model="bathrooms" min="1" placeholder="Bathrooms" class="border rounded p-3">
                    <input type="number" wire:model="max_guests" min="1" placeholder="Max Guests" class="border rounded p-3">
                </div>
                <input type="number" wire:model="avg_price_per_night" placeholder="Average Price per Night (USD)" class="w-full border rounded p-3">
                <div class="flex items-center space-x-6 mt-4">
                    <label class="flex items-center space-x-2"><input type="checkbox" wire:model="has_private_pool" class="form-checkbox"><span>Private Pool</span></label>
                    <label class="flex items-center space-x-2"><input type="checkbox" wire:model="is_featured" class="form-checkbox"><span>Featured Villa</span></label>
                </div>
            </div>
        @endif

        {{-- Step 4: Media --}}
        @if($step === 4)
            <div class="bg-white shadow-lg rounded-lg p-6 space-y-4">
                <h2 class="text-xl font-bold mb-4">Villa Media</h2>
                <div>
                    <label class="font-medium">Cover Image</label>
                    <input type="file" wire:model="cover_image" class="mt-2">
                    @if ($cover_image)
                        <img src="{{ $cover_image->temporaryUrl() }}" class="mt-4 w-64 rounded shadow-lg">
                    @endif
                </div>

                <div>
                    <label class="font-medium">Gallery (Drag & Drop)</label>
                    <input type="file" wire:model="gallery" multiple class="mt-2">
                    <div class="grid grid-cols-4 gap-2 mt-2">
                        @foreach($gallery as $img)
                            <img src="{{ $img->temporaryUrl() }}" class="w-full h-32 object-cover rounded shadow">
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 5: Amenities & Policies --}}
        @if($step === 5)
            <div class="bg-white shadow-lg rounded-lg p-6 space-y-4">
                <h2 class="text-xl font-bold mb-4">Amenities & Policies</h2>
                <div class="grid grid-cols-3 gap-4">
                    @foreach($amenities as $amenity)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="selectedAmenities" value="{{ $amenity->id }}">
                            <span>{{ $amenity->name }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="grid grid-cols-3 gap-4 mt-4">
                    <input type="time" wire:model="policies.check_in" class="border rounded p-2">
                    <input type="time" wire:model="policies.check_out" class="border rounded p-2">
                    <input type="text" wire:model="policies.cancellation" placeholder="Cancellation Policy" class="border rounded p-2">
                </div>
            </div>
        @endif

        {{-- Navigation --}}
        <div class="flex justify-between mt-6">
            @if($step > 1)
                <button type="button" wire:click="previousStep" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Previous</button>
            @endif

            @if($step < 5)
                <button type="button" wire:click="nextStep" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-auto">Next</button>
            @else
                <button type="button" wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 ml-auto">Save Villa</button>
            @endif
        </div>
    </div>
</div>

{{-- Include Leaflet Map --}}
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script>
    document.addEventListener('livewire:load', function () {
        var map = L.map('map').setView([0,0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var marker = L.marker([0,0], {draggable:true}).addTo(map);

        marker.on('dragend', function(e){
            var latlng = marker.getLatLng();
            @this.set('latitude', latlng.lat);
            @this.set('longitude', latlng.lng);
        });

        Livewire.on('updateMap', latlng => {
            marker.setLatLng(latlng);
            map.setView(latlng, 12);
        });
    });
</script>
@endpush
</div>
