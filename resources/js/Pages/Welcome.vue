<script setup>
import { Head, Link } from '@inertiajs/vue3'

const { featuredDestinations, offeredHotels, testimonials } = defineProps({
  featuredDestinations: {
    type: Array,
    required: false,
    default: () => [],
  },
  offeredHotels: {
    type: Array,
    required: false,
    default: () => [],
  },
  testimonials: {
    type: Array,
    required: false,
    default: () => [],
  },
})
</script>

<template>
  <Head title="Welcome" />

  <div class="min-h-screen bg-gray-50 text-gray-900">
    <!-- HEADER -->
    <header class="bg-white shadow">
      <div class="container mx-auto px-4 py-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">HolidayHub</h1>
        <nav class="space-x-4">
          <Link href="/" class="text-gray-600 hover:text-gray-900">Home</Link>
          <Link href="/destinations" class="text-gray-600 hover:text-gray-900">Destinations</Link>
          <Link href="/hotels" class="text-gray-600 hover:text-gray-900">Hotels</Link>
          <Link href="/contact" class="text-gray-600 hover:text-gray-900">Contact</Link>
        </nav>
      </div>
    </header>

    <!-- HERO -->
    <section class="bg-blue-600 text-white text-center py-20">
      <h2 class="text-4xl font-extrabold mb-4">Find Your Perfect Holiday</h2>
      <p class="text-lg mb-6 max-w-2xl mx-auto">
        Exclusive destinations, top hotels, and unforgettable experiences await.
      </p>
      <Link
        href="/book"
        class="bg-white text-blue-600 font-semibold px-8 py-4 rounded-full shadow-lg hover:bg-gray-100 transition-colors"
      >
        Book Now
      </Link>
    </section>

    <!-- FEATURED DESTINATIONS -->
    <section class="container mx-auto px-4 py-16">
      <h3 class="text-3xl font-bold text-center mb-10">Featured Destinations</h3>
      <div v-if="featuredDestinations.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <div
          v-for="destination in featuredDestinations"
          :key="destination.id"
          class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-shadow duration-300 overflow-hidden"
        >
          <img
            :src="destination.image_url || destination.thumbnail"
            :alt="destination.name"
            class="w-full h-64 object-cover"
          />
          <div class="p-6">
            <h4 class="text-xl font-bold mb-2">{{ destination.name }}</h4>
            <p class="text-gray-600 text-sm mb-4 line-clamp-3">
              {{ destination.description }}
            </p>
            <Link
              :href="`/destinations/${destination.slug}`"
              class="inline-block text-blue-600 font-semibold hover:text-blue-800 transition-colors"
            >
              Learn More &rarr;
            </Link>
          </div>
        </div>
      </div>
      <p v-else class="text-center text-gray-500 text-lg py-12">
        No featured destinations are available at the moment.
      </p>
    </section>

    <!-- HOTEL OFFERS -->
    <section class="bg-gray-100 py-16">
      <div class="container mx-auto px-4">
        <h3 class="text-3xl font-bold text-center mb-10">Exclusive Hotel Offers</h3>
        <div v-if="offeredHotels.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
          <div
            v-for="hotel in offeredHotels"
            :key="hotel.id"
            class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-shadow duration-300 overflow-hidden"
          >
            <img
              :src="hotel.cover_image"
              :alt="hotel.name"
              class="w-full h-64 object-cover"
            />
            <div class="p-6">
              <h4 class="text-xl font-bold mb-1">{{ hotel.name }}</h4>
              <p class="text-gray-600 mb-4">{{ hotel.city }}</p>
              <div class="flex items-baseline justify-between">
                <div class="flex items-baseline space-x-2">
                  <span class="text-2xl font-bold text-green-600">
                    ${{ hotel.avg_price_per_night }}
                  </span>
                  <span class="text-gray-500">/ night</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-center text-gray-500 text-lg py-12">
          No special hotel offers available at this time.
        </p>
      </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="container mx-auto px-4 py-16">
      <h3 class="text-3xl font-bold text-center mb-10">What Our Customers Say</h3>
      <div v-if="testimonials.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div
          v-for="testimonial in testimonials"
          :key="testimonial.id"
          class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 flex flex-col"
        >
          <p class="text-gray-700 italic flex-grow">"{{ testimonial.content }}"</p>
          <div class="mt-6 flex items-center space-x-4">
            <img
              :src="testimonial.avatar || `https://i.pravatar.cc/100?u=${testimonial.id}`"
              :alt="testimonial.name || 'Traveler'"
              class="w-12 h-12 rounded-full object-cover border-2 border-blue-600"
            />
            <div>
              <p class="font-bold text-gray-900">{{ testimonial.name || 'Anonymous Traveler' }}</p>
              <p class="text-sm text-gray-500">{{ testimonial.location || 'Happy Traveler' }}</p>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-center text-gray-500 text-lg py-12">
        Be the first to leave a testimonial!
      </p>
    </section>

    <!-- FOOTER -->
    <footer class="bg-white shadow mt-12 py-8">
      <div class="container mx-auto px-4 text-center text-gray-600">
        <p>© {{ new Date().getFullYear() }} HolidayHub. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>
