<div>
<script>
    // Alpine.js logic is extracted into a global object for better readability
    document.addEventListener('alpine:init', () => {
        Alpine.data('calendar', () => ({
            // State properties are synchronized with Livewire
            checkin: @entangle('checkin').defer,
            checkout: @entangle('checkout').defer,
            // Added a fallback to an empty object to prevent JavaScript syntax errors
            availability: @entangle('availability').defer ?? {},
            // Added a fallback to 1 to ensure a valid minStay value
            minStay: @entangle('minStay').defer ?? 1,

            // Local component state
            currentMonth: new Date(),
            nextMonth: new Date(new Date().setMonth(new Date().getMonth() + 1)),

            // Method to navigate months
            navigate(direction) {
                this.currentMonth.setMonth(this.currentMonth.getMonth() + direction);
                this.nextMonth.setMonth(this.nextMonth.getMonth() + direction);
            },

            // Helper to get all days in a given month
            getDaysInMonth(year, month) {
                const date = new Date(year, month, 1);
                const days = [];
                const firstDayOfWeek = date.getDay(); // 0 = Sunday, 1 = Monday

                // Add leading empty cells for alignment
                for (let i = (firstDayOfWeek === 0 ? 6 : firstDayOfWeek - 1); i > 0; i--) {
                    days.push({ day: '', date: '', is_available: false, price: 0 });
                }

                while (date.getMonth() === month) {
                    const dateStr = date.toISOString().split('T')[0];
                    const dayData = this.availability[dateStr] || { is_available: false, price: 0 };
                    days.push({
                        day: date.getDate(),
                        date: dateStr,
                        is_available: dayData.available ?? dayData.is_available,
                        price: dayData.price
                    });
                    date.setDate(date.getDate() + 1);
                }
                return days;
            },

            // Logic for selecting check-in and check-out dates
            selectDate(date) {
                const selectedDate = new Date(date);
                const checkinDate = new Date(this.checkin);

                if (!this.checkin || this.checkout || selectedDate < checkinDate) {
                    this.checkin = date;
                    this.checkout = null;
                } else if (selectedDate > checkinDate) {
                    this.checkout = date;
                } else if (selectedDate.getTime() === checkinDate.getTime()){
                    this.checkin = null;
                    this.checkout = null;
                }
            },

            // Total price calculation
            totalPrice() {
                if (!this.checkin || !this.checkout) return 0;
                const start = new Date(this.checkin);
                const end = new Date(this.checkout);
                let total = 0;
                let current = new Date(start);

                while (current < end) {
                    const dateStr = current.toISOString().split('T')[0];
                    if (this.availability[dateStr] && (this.availability[dateStr].available ?? this.availability[dateStr].is_available)) {
                        total += this.availability[dateStr].price;
                    }
                    current.setDate(current.getDate() + 1);
                }
                return total;
            },

            // Helper to check if a date is in the selected range
            isDateSelected(date) {
                if (!this.checkin) return false;
                const current = new Date(date);
                const start = new Date(this.checkin);
                const end = this.checkout ? new Date(this.checkout) : null;
                return (end && current >= start && current <= end) || current.getTime() === start.getTime();
            },

            // Helper to check if a date is a check-in date
            isCheckin(date) {
                return date === this.checkin;
            },

            // Helper to check if a date is a check-out date
            isCheckout(date) {
                return date === this.checkout;
            },

            // Get number of nights for display
            getNights() {
                if (!this.checkin || !this.checkout) return 0;
                const start = new Date(this.checkin);
                const end = new Date(this.checkout);
                return (end - start) / (1000 * 60 * 60 * 24);
            }
        }));
    });
</script>

<div
    x-data="calendar"
    class="bg-white p-8 rounded-3xl shadow-2xl max-w-4xl mx-auto border border-gray-100 font-sans"
>

    <!-- Calendar Header & Price Summary -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-900 leading-tight">Select Your Dates</h2>
            <p class="text-gray-500 mt-1">
                Minimum stay: <span x-text="minStay"></span> nights
                <span x-show="totalPrice() > 0" class="ml-2 font-semibold">
                    &bull; <span x-text="getNights()"></span> nights
                </span>
            </p>
        </div>
        <div class="text-right flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-700">Total Price</h3>
            <p
                class="text-4xl font-black text-lime-500 transition-all duration-300 transform"
                :class="{ 'scale-110': totalPrice() > 0 }"
                x-text="`$${totalPrice()}`"
            ></p>
        </div>
    </div>

    <!-- Error Display for Livewire -->
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg" role="alert">
            <p class="font-bold">Heads up!</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Calendar Grid Container -->
    <div class="flex flex-col md:flex-row gap-8 justify-center">

        <!-- Calendar for current month -->
        <div class="flex-1">
            <div class="flex justify-between items-center text-gray-800 font-bold mb-4">
                <button
                    @click="navigate(-1)"
                    class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <h4 class="text-xl font-extrabold" x-text="currentMonth.toLocaleString('default', { month: 'long', year: 'numeric' })"></h4>
                <button
                    @click="navigate(1)"
                    class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center font-medium text-sm text-gray-500">
                <span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span>
            </div>
            <div class="grid grid-cols-7 gap-1 mt-2">
                <template x-for="day in getDaysInMonth(currentMonth.getFullYear(), currentMonth.getMonth())">
                    <button
                        @click="selectDate(day.date)"
                        :disabled="!day.is_available"
                        class="h-12 w-full flex flex-col items-center justify-center p-2 rounded-xl transition-colors duration-200 ease-in-out"
                        :class="{
                            // General styles
                            'cursor-not-allowed': !day.is_available,
                            'opacity-40': day.date && new Date(day.date) < new Date(),
                            // Selection styles
                            'bg-indigo-600 text-white shadow-lg z-10': isDateSelected(day.date),
                            'bg-white text-gray-800 ring-1 ring-green-400 hover:bg-green-50': day.is_available && !isDateSelected(day.date),
                            // Disabled styles
                            'bg-gray-100 text-gray-400 line-through': !day.is_available,
                            // Check-in/Check-out specific styles
                            'rounded-r-none': isCheckin(day.date) && checkout,
                            'rounded-l-none': isCheckout(day.date) && checkin,
                        }"
                    >
                        <span class="font-bold text-sm" x-text="day.day"></span>
                        <div x-show="day.is_available" class="text-green-600 font-semibold text-xs mt-1" :class="{ 'text-white': isDateSelected(day.date) }" x-text="day.price > 0 ? `$${day.price}` : ''"></div>
                        <div x-show="!day.is_available && day.day" class="text-xs font-medium text-gray-400 mt-1">Booked</div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Booking Button & Call to Action -->
    <div class="mt-12 text-center">
        <button
            wire:click="book"
            :disabled="!checkin || !checkout || getNights() < minStay"
            class="px-12 py-5 bg-indigo-600 text-white font-extrabold rounded-full shadow-2xl transition-all duration-300 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105"
        >
            Book Now
        </button>
    </div>
</div>
</div>
