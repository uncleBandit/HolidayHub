<div>
<div x-data="{ open: false }" class="max-w-6xl mx-auto py-12 px-4 sm:px-6 lg:px-8">

    {{-- Header Section with Title and Description --}}
    <div class="mb-10 text-center">
        <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight sm:text-5xl">
            Create Your Bed & Breakfast Listing
        </h2>
        <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-500">
            Showcase your unique property to travelers. Fill out the details below to get started.
        </p>
    </div>

    {{-- Progress Stepper --}}
    <div class="flex items-center justify-between space-x-4 mb-12">
        @foreach(range(1, $totalSteps) as $s)
            <div class="flex-1 text-center">
                <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center font-bold text-lg
                    transition-all duration-300 ease-in-out
                    {{ $step >= $s ? 'bg-indigo-600 text-white shadow-lg' : 'bg-gray-200 text-gray-600' }}">
                    @if ($step > $s)
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    @else
                        {{ $s }}
                    @endif
                </div>
                <div class="mt-3 text-sm font-medium
                    {{ $step >= $s ? 'text-indigo-600' : 'text-gray-600' }}">
                    {{ ['Basic Info','Property Details','Location','Media','Amenities & Policies'][$s-1] }}
                </div>
            </div>
            @if ($s < $totalSteps)
                <div class="flex-1 h-1 bg-gray-300 rounded-full transition-colors duration-300 ease-in-out
                    {{ $step > $s ? 'bg-indigo-600' : '' }}"></div>
            @endif
        @endforeach
    </div>

    <form wire:submit.prevent="save">
        {{-- Step 1: Basic Info --}}
        @if($step === 1)
            <div class="space-y-6">
                <x-input label="Name" type="text" wire:model.defer="name" placeholder="Cozy Creek Bed & Breakfast" />
                <x-input label="Slug" type="text" wire:model.defer="slug" placeholder="cozy-creek-bb" />
                <x-textarea label="Description" wire:model.defer="description" rows="6" placeholder="A charming, rustic retreat nestled in the hills..." />
            </div>
        @endif

        {{-- Step 2: Property Details --}}
        @if($step === 2)
            <div class="grid md:grid-cols-2 gap-6">
                <x-input label="Number of Rooms" type="number" wire:model.defer="rooms" placeholder="e.g., 5" />
                <x-input label="Maximum Guests" type="number" wire:model.defer="max_guests" placeholder="e.g., 10" />
                <div class="md:col-span-2">
                    <x-input label="Price per Night (USD)" type="number" step="0.01" wire:model.defer="price_per_night" placeholder="e.g., 125.00" />
                </div>
                <div class="md:col-span-2 flex items-center justify-start gap-3">
                    <input type="checkbox" wire:model.defer="has_breakfast" id="has_breakfast" class="h-5 w-5 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300" />
                    <label for="has_breakfast" class="text-sm font-medium text-gray-700">Includes Breakfast?</label>
                </div>
            </div>
        @endif

        {{-- Step 3: Location --}}
        @if($step === 3)
            <div class="grid md:grid-cols-2 gap-6">
                <x-input label="Address" type="text" wire:model.defer="address" placeholder="123 Main Street" />
                <x-input label="City" type="text" wire:model.defer="city" placeholder="Springfield" />
                <x-input label="Country" type="text" wire:model.defer="country" placeholder="United States" />
                <div class="md:col-span-2 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-input label="Latitude" type="text" wire:model.defer="latitude" placeholder="34.0522" />
                        <x-input label="Longitude" type="text" wire:model.defer="longitude" placeholder="-118.2437" />
                    </div>
                    <div class="rounded-lg overflow-hidden shadow-md" style="height: 300px;">
                        {{-- Placeholder for a map component like Leaflet.js --}}
                        <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500">
                            Interactive Map Placeholder
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 4: Media --}}
        @if($step === 4)
            <div class="space-y-6">
                <div>
                    <x-input type="file" label="Upload Cover Image" wire:model="cover_image" />
                    <p class="mt-2 text-xs text-gray-500">PNG, JPG up to 5MB.</p>
                    @if ($cover_image)
                        <img src="{{ $cover_image->temporaryUrl() }}" class="mt-4 w-full h-64 object-cover rounded-lg shadow-md border-2 border-indigo-200">
                    @endif
                </div>

                <div>
                    <x-input type="file" label="Upload Gallery Images" multiple wire:model="gallery" />
                    <p class="mt-2 text-xs text-gray-500">Add multiple photos of your property. Max 10 images.</p>
                    <div class="mt-4 flex flex-wrap gap-4">
                        @foreach ($gallery as $img)
                            <img src="{{ $img->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-lg shadow-md border-2 border-gray-200">
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 5: Amenities & Policies --}}
        @if($step === 5)
            <div class="space-y-6">
                <div>
                    <h3 class="font-bold text-xl text-gray-700 mb-4">Amenities</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($amenities as $amenity)
                            <label class="flex items-center space-x-3 p-4 bg-gray-50 rounded-lg shadow-sm cursor-pointer hover:bg-gray-100 transition-colors">
                                <input type="checkbox" wire:model="selectedAmenities" value="{{ $amenity->id }}" class="form-checkbox h-5 w-5 text-indigo-600 rounded-md border-gray-300 focus:ring-indigo-500" />
                                <span class="text-sm font-medium text-gray-700">{{ $amenity->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-xl text-gray-700 mb-4">Policies</h3>
                    <x-textarea wire:model.defer="policies" rows="5" placeholder='e.g., {"check_in":"3:00 PM","check_out":"11:00 AM","cancellation":"Free cancellation up to 48 hours before check-in."}' />
                </div>

                <div>
                    <h3 class="font-bold text-xl text-gray-700 mb-4">Seasonal Pricing</h3>
                    <p class="text-sm text-gray-500 mb-2">Define different prices for specific date ranges.</p>
                    <x-textarea wire:model.defer="seasonal_pricing" rows="5" placeholder='e.g., [{"start_date":"2025-06-01","end_date":"2025-08-31","price":120},{"start_date":"2025-12-20","end_date":"2025-12-31","price":150}]' />
                </div>
            </div>
        @endif

        {{-- Navigation Buttons --}}
        <div class="flex justify-between mt-10 border-t pt-6">
            @if($step > 1)
                <button type="button" wire:click="prevStep" class="px-6 py-3 border border-gray-300 rounded-full shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    <span class="flex items-center"><svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg> Back</span>
                </button>
            @else
                <div></div>
            @endif

            @if($step < $totalSteps)
                <button type="button" wire:click="nextStep" class="px-6 py-3 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    <span class="flex items-center">Next <svg class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></span>
                </button>
            @else
                <button type="submit" class="px-6 py-3 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all">
                    <span class="flex items-center"><svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Save Listing</span>
                </button>
            @endif
        </div>
    </form>
</div>
</div>
