<x-layouts.app.header>
    <div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <img src="{{ $package->cover_image ?? 'https://via.placeholder.com/1200x600' }}"
                    alt="{{ $package->title }}"
                    class="w-full h-80 object-cover rounded-2xl shadow" />

                <div class="grid grid-cols-3 gap-2">
                    @foreach ($package->gallery ?? [] as $image)
                        <img src="{{ $image }}" class="w-full h-28 object-cover rounded-lg" />
                    @endforeach
                </div>
            </div>

            <aside class="lg:col-span-1">
                <div class="bg-white p-6 rounded-2xl shadow-md sticky top-6"
                    x-data="bookingWidget({{ $package->final_price }}, {{ json_encode($package->extras) }})">

                    <h3 class="text-2xl font-bold mb-2">${{ number_format($package->final_price, 2) }}</h3>
                    <p class="text-gray-500 mb-4">per person</p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium">Select Dates</label>
                        <input type="date" x-model="startDate" class="w-full mt-1 rounded-lg border-gray-300" />
                        <input type="date" x-model="endDate" class="w-full mt-2 rounded-lg border-gray-300" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium">Guests</label>
                        <input type="number" x-model="guests" min="1" class="w-full mt-1 rounded-lg border-gray-300" />
                    </div>

                    <div class="mb-4 space-y-2">
                        <label class="block text-sm font-medium">Customize Your Package</label>
                        <template x-for="extra in extras" :key="extra.id">
                            <div class="flex items-center justify-between">
                                <label :for="'extra-' + extra.id" x-text="extra.name + ' (+' + extra.formattedPrice() + ')'" class="cursor-pointer"></label>
                                <input type="checkbox" :id="'extra-' + extra.id" :value="extra.id" x-model="selectedExtras" class="rounded border-gray-300">
                            </div>
                        </template>
                    </div>

                    <div class="border-t pt-4 mt-4 text-sm text-gray-700 space-y-2">
                        <div class="flex justify-between">
                            <span>Base Price</span>
                            <span x-text="formattedBasePrice()"></span>
                        </div>
                        <template x-for="extra in extras" :key="extra.id">
                            <div class="flex justify-between" x-show="selectedExtras.includes(extra.id)">
                                <span x-text="extra.name"></span>
                                <span x-text="extra.formattedPrice()"></span>
                            </div>
                        </template>
                        <div class="flex justify-between font-bold text-lg text-blue-600">
                            <span>Total</span>
                            <span x-text="formattedTotal()"></span>
                        </div>
                    </div>

                    <button
                        @click="$dispatch('book-package', { id: {{ $package->id }}, total: totalPrice, guests: guests, startDate: startDate, endDate: endDate, selectedExtras: selectedExtras })"
                        class="mt-4 w-full bg-blue-600 text-white py-3 rounded-xl hover:bg-blue-700 transition">
                        Book Now
                    </button>
                </div>
            </aside>
        </div>

        <div class="space-y-6">
            <h1 class="text-3xl font-bold">{{ $package->title }}</h1>
            <p class="text-gray-600">{{ $package->destination }}, {{ $package->country }}</p>

            <div class="prose max-w-none">
                {!! nl2br(e($package->description)) !!}
            </div>
        </div>
    </div>
</x-layouts.app.header>

<script>
function bookingWidget(basePrice, extrasData) {
    return {
        startDate: null,
        endDate: null,
        guests: 1,
        selectedExtras: [],
        basePrice: basePrice,
        extras: extrasData.map(extra => ({
            ...extra,
            formattedPrice() {
                return `+$${(this.price * this.guests).toFixed(2)}`;
            }
        })),
        totalPrice: basePrice,

        init() {
            this.$watch('guests', () => this.calculateTotal());
            this.$watch('selectedExtras', () => this.calculateTotal());
            this.calculateTotal(); // Initial calculation
        },

        calculateTotal() {
            let total = this.basePrice * this.guests;
            this.selectedExtras.forEach(extraId => {
                const extra = this.extras.find(e => e.id === extraId);
                if (extra) {
                    total += extra.price * this.guests;
                }
            });
            this.totalPrice = total;
        },

        formattedBasePrice() {
            return `$${(this.basePrice * this.guests).toFixed(2)}`;
        },

        formattedTotal() {
            return `$${this.totalPrice.toFixed(2)}`;
        }
    }
}
</script>
