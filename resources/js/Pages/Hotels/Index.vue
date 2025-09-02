<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const hotels = ref([])
const filters = ref({
  location: '',
  price_min: null,
  price_max: null,
  rating: null,
  available: true
})

const fetchHotels = async () => {
  const { data } = await axios.get('/hotels', { params: filters.value })
  hotels.value = data.data // because HotelResource is wrapped in a "data" key
}

onMounted(fetchHotels)
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Available Hotels</h1>

    <!-- Filters -->
    <div class="flex gap-4 mb-6">
      <input v-model="filters.location" placeholder="Search by location" class="border p-2 rounded" />
      <input v-model="filters.price_min" type="number" placeholder="Min Price" class="border p-2 rounded" />
      <input v-model="filters.price_max" type="number" placeholder="Max Price" class="border p-2 rounded" />
      <button @click="fetchHotels" class="bg-blue-600 text-white px-4 py-2 rounded">Search</button>
    </div>

    <!-- Hotel List -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div v-for="hotel in hotels" :key="hotel.id" class="border rounded-lg shadow-md overflow-hidden">
        <img :src="hotel.thumbnail" alt="Hotel" class="w-full h-48 object-cover" />
        <div class="p-4">
          <h2 class="text-lg font-semibold">{{ hotel.name }}</h2>
          <p class="text-gray-600">{{ hotel.location }}</p>
          <p class="font-bold mt-2">${{ hotel.price_per_night }} / night</p>
          <a :href="`/hotels/${hotel.slug}`" class="text-blue-600 hover:underline">View Details</a>
        </div>
      </div>
    </div>
  </div>
</template>
