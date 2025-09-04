<div>

<div
    x-data="packageForm()"
    x-init="init()"
    class="bg-white shadow-xl rounded-2xl p-6 space-y-8"
>
    <h2 class="text-2xl font-bold text-gray-800">Create New Package</h2>

    {{-- Step Wizard --}}
    <div class="flex items-center justify-between mb-6">
        <template x-for="(step, index) in steps" :key="index">
            <div class="flex-1 flex items-center">
                <div
                    class="w-8 h-8 flex items-center justify-center rounded-full"
                    :class="currentStep === index ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                >
                    <span x-text="index+1"></span>
                </div>
                <div class="flex-1 h-1 mx-2"
                     :class="index < steps.length-1 ? 'bg-gray-300' : ''">
                </div>
            </div>
        </template>
    </div>

    <form wire:submit.prevent="store" class="space-y-6">

        {{-- Step 1: Basic Info --}}
        <div x-show="currentStep === 0" x-transition>
            <div>
                <label class="block font-semibold">Title</label>
                <input type="text" wire:model="title"
                    class="w-full rounded-lg border-gray-300" />
                @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-semibold">Destination</label>
                <input type="text" wire:model="destination"
                    class="w-full rounded-lg border-gray-300" />
                @error('destination') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-semibold">Country</label>
                <input type="text" wire:model="country"
                    class="w-full rounded-lg border-gray-300" />
            </div>
        </div>

        {{-- Step 2: Pricing --}}
        <div x-show="currentStep === 1" x-transition>
            <div>
                <label class="block font-semibold">Base Price (USD)</label>
                <input type="number" wire:model="base_price" x-model="price"
                    class="w-full rounded-lg border-gray-300" />
                @error('base_price') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-semibold">Discount (%)</label>
                <input type="number" wire:model="discount" x-model="discount"
                    class="w-full rounded-lg border-gray-300" />
                @error('discount') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>

            <p class="mt-2 text-sm text-gray-700">
                Final Price:
                <span class="font-bold text-green-600"
                      x-text="discountedPrice()"></span> USD
            </p>
        </div>

        {{-- Step 3: Media --}}
        <div x-show="currentStep === 2" x-transition>
            <div>
                <label class="block font-semibold">Cover Image</label>
                <input type="file" wire:model="cover_image" />
                <div wire:loading wire:target="cover_image" class="text-blue-600 mt-1">Uploading...</div>

                @if ($cover_image)
                    <img src="{{ $cover_image->temporaryUrl() }}"
                         class="mt-2 w-40 h-28 object-cover rounded-lg shadow" />
                @endif
            </div>

            <div>
                <label class="block font-semibold">Gallery Images</label>
                <input type="file" wire:model="gallery" multiple />

                <div wire:loading wire:target="gallery" class="text-blue-600 mt-1">Uploading...</div>

                <div class="mt-2 flex gap-2 flex-wrap">
                    @foreach ($gallery as $image)
                        <img src="{{ $image->temporaryUrl() }}"
                             class="w-24 h-20 object-cover rounded-md" />
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Step 4: Schedule --}}
        <div x-show="currentStep === 3" x-transition>
            <div>
                <label class="block font-semibold">Available From</label>
                <input type="date" wire:model="available_from"
                    class="w-full rounded-lg border-gray-300" />
            </div>
            <div>
                <label class="block font-semibold">Available To</label>
                <input type="date" wire:model="available_to"
                    class="w-full rounded-lg border-gray-300" />
            </div>
        </div>

        {{-- Navigation Buttons --}}
        <div class="flex justify-between">
            <button type="button"
                @click="prevStep"
                x-show="currentStep > 0"
                class="px-4 py-2 bg-gray-300 rounded-lg">Back</button>

            <button type="button"
                @click="nextStep"
                x-show="currentStep < steps.length - 1"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg">Next</button>

            <button type="submit"
                x-show="currentStep === steps.length - 1"
                class="px-4 py-2 bg-green-600 text-white rounded-lg">Create Package</button>
        </div>
    </form>
</div>

<script>
function packageForm() {
    return {
        currentStep: 0,
        steps: ['Basic Info', 'Pricing', 'Media', 'Schedule'],
        price: 0,
        discount: 0,
        init() {
            // Hook up watchers if needed
        },
        nextStep() {
            if (this.currentStep < this.steps.length - 1) this.currentStep++;
        },
        prevStep() {
            if (this.currentStep > 0) this.currentStep--;
        },
        discountedPrice() {
            let p = parseFloat(this.price) || 0;
            let d = parseFloat(this.discount) || 0;
            return (p - (p * d / 100)).toFixed(2);
        }
    }
}
</script>

</div>
