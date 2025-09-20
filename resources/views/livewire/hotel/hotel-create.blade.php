<div>
{{-- resources/views/livewire/hotel/hotel-create.blade.php --}}
<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Create a New Hotel</h1>

    {{-- Progress Indicator --}}
    <div class="flex items-center mb-8 space-x-4">
        @foreach(range(1,6) as $s)
            <div class="flex-1 text-center">
                <div class="w-10 h-10 mx-auto rounded-full flex items-center justify-center
                    {{ $step >= $s ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                    {{ $s }}
                </div>
                <span class="block mt-1 text-sm font-medium">{{ match($s) {
                    1 => 'Core Info',
                    2 => 'Location',
                    3 => 'Pricing',
                    4 => 'Media',
                    5 => 'Policies & Amenities',
                    6 => 'Room Types',
                } }}</span>
            </div>
        @endforeach
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{-- ================= Step 1: Core Info ================= --}}
        @if($step === 1)
            <div class="space-y-4">
                <label class="block">
                    <span class="text-gray-700 font-semibold">Hotel Name</span>
                    <input type="text" wire:model.lazy="name"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-gray-700 font-semibold">Slug</span>
                    <input type="text" wire:model="slug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500" readonly>
                    @error('slug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-gray-700 font-semibold">Description</span>
                    <textarea wire:model.lazy="description" rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500"></textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </label>
            </div>
        @endif

        {{-- ================= Step 2: Location ================= --}}
        @if($step === 2)
            <div class="space-y-4">
                <label class="block">
                    <span class="text-gray-700 font-semibold">Address</span>
                    <input type="text" wire:model.lazy="address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="block">
                        <span class="text-gray-700 font-semibold">City</span>
                        <input type="text" wire:model.lazy="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="text-gray-700 font-semibold">Country</span>
                        <input type="text" wire:model.lazy="country" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('country') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="text-gray-700 font-semibold">Latitude / Longitude</span>
                        <div class="flex gap-2 mt-1">
                            <input type="text" wire:model.lazy="latitude" placeholder="Lat" class="block w-1/2 rounded-md border-gray-300 shadow-sm">
                            <input type="text" wire:model.lazy="longitude" placeholder="Lng" class="block w-1/2 rounded-md border-gray-300 shadow-sm">
                        </div>
                        @error('latitude') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @error('longitude') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </label>
                </div>
            </div>
        @endif

        {{-- ================= Step 3: Pricing ================= --}}
        @if($step === 3)
            <div class="space-y-4">
                <label class="block">
                    <span class="text-gray-700 font-semibold">Stars</span>
                    <select wire:model="stars" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @for($i=1; $i<=5; $i++)
                            <option value="{{ $i }}">{{ $i }} Star{{ $i>1?'s':'' }}</option>
                        @endfor
                    </select>
                    @error('stars') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-gray-700 font-semibold">Average Price Per Night (USD)</span>
                    <input type="number" wire:model.lazy="avg_price_per_night" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('avg_price_per_night') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </label>
            </div>
        @endif

        {{-- ================= Step 4: Media ================= --}}
        @if($step === 4)
            <div class="space-y-4">
                <label class="block">
                    <span class="text-gray-700 font-semibold">Cover Image</span>
                    <input type="file" wire:model="cover_image" class="mt-1">
                    @error('cover_image') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    @if ($cover_image)
                        <img src="{{ $cover_image->temporaryUrl() }}" class="mt-2 w-64 h-40 object-cover rounded-md border">
                    @endif
                </label>

                <label class="block">
                    <span class="text-gray-700 font-semibold">Gallery Images</span>
                    <input type="file" wire:model="gallery" multiple class="mt-1">
                    <div class="flex flex-wrap mt-2 gap-2">
                        @foreach($gallery as $img)
                            <img src="{{ $img->temporaryUrl() }}" class="w-32 h-24 object-cover rounded-md border">
                        @endforeach
                    </div>
                </label>
            </div>
        @endif

        {{-- ================= Step 5: Policies & Amenities ================= --}}
        @if($step === 5)
            <div class="space-y-4">
                <label class="block">
                    <span class="text-gray-700 font-semibold">Check-in Time</span>
                    <input type="text" wire:model.lazy="policies.check_in" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('policies.check_in') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-gray-700 font-semibold">Check-out Time</span>
                    <input type="text" wire:model.lazy="policies.check_out" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @error('policies.check_out') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-gray-700 font-semibold">Cancellation Policy</span>
                    <textarea wire:model.lazy="policies.cancellation" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    @error('policies.cancellation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-gray-700 font-semibold">Amenities</span>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                        @foreach($amenities as $amenity)
                            <label class="flex items-center gap-2 border p-2 rounded hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" wire:model="selectedAmenities" value="{{ $amenity->id }}">
                                <span>{{ $amenity->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </label>
            </div>
        @endif

        {{-- ================= Step 6: Room Types ================= --}}
        @if($step === 6)
            <div class="space-y-4">
                <button type="button" wire:click="addRoomType" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Add Room Type</button>

                @foreach($roomTypes as $index => $room)
                    <div class="border p-4 rounded relative bg-gray-50">
                        <button type="button" wire:click="removeRoomType({{ $index }})"
                            class="absolute top-2 right-2 text-red-500 hover:text-red-700 font-bold">&times;</button>
                        <label class="block mt-2">
                            <span class="font-semibold">Name</span>
                            <input type="text" wire:model.lazy="roomTypes.{{ $index }}.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </label>
                        <label class="block mt-2">
                            <span class="font-semibold">Price per Night</span>
                            <input type="number" wire:model.lazy="roomTypes.{{ $index }}.price_per_night" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </label>
                        <label class="block mt-2">
                            <span class="font-semibold">Capacity</span>
                            <input type="number" wire:model.lazy="roomTypes.{{ $index }}.capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </label>
                        <label class="block mt-2">
                            <span class="font-semibold">Beds</span>
                            <input type="number" wire:model.lazy="roomTypes.{{ $index }}.beds" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </label>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ================= Navigation Buttons ================= --}}
        <div class="flex justify-between mt-6">
            @if($step > 1)
                <button type="button" wire:click="previousStep" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Back</button>
            @endif

            @if($step < 6)
                <button type="button" wire:click="nextStep" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Next</button>
            @else
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Hotel</button>
            @endif
        </div>
    </form>
</div>
</div>
