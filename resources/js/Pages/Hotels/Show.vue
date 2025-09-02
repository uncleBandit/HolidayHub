<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const page = usePage()
const hotel = ref(null)

onMounted(async () => {
  const slug = page.props.slug // provided via Inertia route param
  const { data } = await axios.get(`/hotels/${slug}`)
  hotel.value = data.data
})
</script>

<template>
  <div v-if="hotel" class="p-6 max-w-4xl mx-auto">
    <!-- Hotel Header -->
    <img
      :src="hotel.image_url ?? '/placeholder.jpg'"
      class="w-full h-64 object-cover rounded-lg"
    />
    <h1 class="text-3xl font-bold mt-4">{{ hotel.name }}</h1>
    <p class="text-gray-600">{{ hotel.location ?? 'No location provided' }}</p>
    <p class="mt-4">{{ hotel.description }}</p>

    <!-- Price -->
    <p v-if="hotel.price_per_night" class="mt-4 text-lg font-semibold">
      ${{ hotel.price_per_night }} / night
    </p>

    <!-- Amenities -->
    <div v-if="hotel.amenities" class="mt-4">
      <h2 class="text-xl font-bold mb-2">Amenities</h2>
      <ul class="list-disc list-inside text-gray-700">
        <li v-for="(amenity, index) in JSON.parse(hotel.amenities)" :key="index">
          {{ amenity }}
        </li>
      </ul>
    </div>

    <!-- Rooms -->
    <div v-if="hotel.rooms && hotel.rooms.length" class="mt-8">
      <h2 class="text-xl font-bold mb-2">Rooms</h2>
      <div v-for="room in hotel.rooms" :key="room.id" class="border p-4 rounded-lg mb-4">
        <h3 class="text-lg font-semibold">{{ room.name }}</h3>
        <p>{{ room.description }}</p>
        <p class="text-sm text-gray-500">Capacity: {{ room.capacity.adults }} adults, {{ room.capacity.children }} children</p>
        <p class="text-sm">Beds: {{ room.capacity.beds }}</p>
      </div>
    </div>

    <!-- Reviews -->
    <div v-if="hotel.reviews && hotel.reviews.length" class="mt-8">
      <h2 class="text-xl font-bold mb-2">Guest Reviews</h2>
      <div v-for="review in hotel.reviews" :key="review.id" class="border p-4 rounded-lg mb-4">
        <p class="font-semibold">{{ review.reviewer.name }}</p>
        <p class="text-yellow-500">⭐ {{ review.rating }}/5</p>
        <p class="italic">{{ review.title }}</p>
        <p>{{ review.comment ?? 'No comment provided.' }}</p>
      </div>
    </div>

    <!-- CTA -->
    <button class="bg-green-600 text-white px-6 py-2 mt-4 rounded">
      Book Now
    </button>
  </div>
</template>
