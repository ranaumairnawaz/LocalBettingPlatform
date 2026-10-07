<template>
  <div class="h-screen bg-black flex flex-col">
    <!-- Header -->
    <div class="p-4 flex items-center gap-4 border-b border-zinc-800">
      <button @click="$router.back()" class="p-2 bg-zinc-800 rounded-full">
        <ArrowLeft size="20" />
      </button>
      <h1 class="font-bold capitalize">{{ $route.params.slug }}</h1>
    </div>

    <!-- Game Iframe -->
    <div class="flex-1 relative">
      <iframe 
        v-if="launchUrl"
        :src="launchUrl" 
        class="w-full h-full border-none"
        allow="autoplay; fullscreen"
      ></iframe>
      <div v-else class="absolute inset-0 flex items-center justify-center">
        <div class="text-center">
           <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
           <p class="text-zinc-500">Launching game...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft } from 'lucide-vue-next'
import axios from 'axios'

const route = useRoute()
const launchUrl = ref(null)

const fetchLaunchUrl = async () => {
  try {
    const { data } = await axios.get(`/api/games/launch/${route.params.slug}`)
    launchUrl.value = data.url
  } catch (e) {
    console.error('Failed to launch game', e)
  }
}

onMounted(() => {
  fetchLaunchUrl()
})
</script>
