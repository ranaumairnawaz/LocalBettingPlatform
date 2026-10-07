<template>
  <div class="space-y-4">
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-2">
        <Trophy size="18" class="text-neon-pink animate-pulse" />
        <h3 class="font-black text-lg text-white italic tracking-wider">ACTIVE TOURNAMENTS</h3>
      </div>
      <router-link to="/tournaments" class="text-neon-cyan hover:text-neon-cyan/85 font-black uppercase text-xs tracking-wider flex items-center gap-1.5 group">
        <span>See All</span>
        <ArrowRight size="14" class="group-hover:translate-x-1 transition-transform" />
      </router-link>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-neon-cyan"></div>
    </div>

    <div v-else-if="tournaments.length === 0" class="text-center py-12">
      <Trophy size="48" class="text-slate-600 mx-auto mb-4" />
      <p class="text-slate-400 text-sm font-semibold">No active tournaments at the moment</p>
      <p class="text-slate-500 text-xs mt-1">Check back later for exciting competitions!</p>
    </div>

    <div v-else class="relative">
      <!-- Carousel Container -->
      <div 
        ref="carouselRef"
        class="flex gap-6 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide"
        :style="{ scrollBehavior: 'smooth' }"
      >
        <TournamentCard
          v-for="tournament in tournaments"
          :key="tournament.id"
          :tournament="tournament"
          :currency-symbol="currencySymbol"
          @show-details="handleShowDetails"
          class="snap-center shrink-0 w-[320px]"
        />
      </div>

      <!-- Navigation Buttons -->
      <button
        v-if="showNavButtons"
        @click="scrollLeft"
        class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-3 bg-[#121623]/90 backdrop-blur-md border border-white/10 rounded-full p-2 hover:bg-neon-cyan/20 hover:border-neon-cyan/50 transition-all z-10"
      >
        <ChevronLeft size="20" class="text-white" />
      </button>
      <button
        v-if="showNavButtons"
        @click="scrollRight"
        class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-3 bg-[#121623]/90 backdrop-blur-md border border-white/10 rounded-full p-2 hover:bg-neon-cyan/20 hover:border-neon-cyan/50 transition-all z-10"
      >
        <ChevronRight size="20" class="text-white" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { Trophy, ArrowRight, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import TournamentCard from './TournamentCard.vue'
import axios from 'axios'

const emit = defineEmits(['show-details'])

const tournaments = ref([])
const loading = ref(true)
const carouselRef = ref(null)
const currencySymbol = ref('Rs.')
const showNavButtons = ref(false)

const fetchTournaments = async () => {
  try {
    loading.value = true
    const { data } = await axios.get('/api/tournaments/active')
    tournaments.value = data.tournaments || []
  } catch (error) {
    console.error('Failed to fetch tournaments:', error)
    tournaments.value = []
  } finally {
    loading.value = false
  }
}

const fetchSettings = async () => {
  try {
    const { data } = await axios.get('/api/settings')
    if (data.currency_symbol) {
      currencySymbol.value = data.currency_symbol
    }
  } catch (e) {
    console.error('Failed to fetch settings:', e)
  }
}

const scrollLeft = () => {
  if (carouselRef.value) {
    carouselRef.value.scrollBy({ left: -340, behavior: 'smooth' })
  }
}

const scrollRight = () => {
  if (carouselRef.value) {
    carouselRef.value.scrollBy({ left: 340, behavior: 'smooth' })
  }
}

const checkOverflow = () => {
  if (carouselRef.value) {
    showNavButtons.value = carouselRef.value.scrollWidth > carouselRef.value.clientWidth
  }
}

const handleShowDetails = (tournament) => {
  emit('show-details', tournament)
}

onMounted(() => {
  fetchTournaments()
  fetchSettings()
  
  // Check for overflow after tournaments load
  setTimeout(checkOverflow, 100)
  
  // Recheck on window resize
  window.addEventListener('resize', checkOverflow)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkOverflow)
})
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
