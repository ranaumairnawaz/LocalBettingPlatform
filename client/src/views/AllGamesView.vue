<template>
  <div class="p-4 min-h-screen text-white pb-20">
    <!-- Header -->
    <header class="flex items-center gap-4 mb-6">
      <button 
        @click="$router.push('/')" 
        class="bg-zinc-800/80 hover:bg-zinc-700/80 text-zinc-300 p-2 rounded-xl border border-white/5 active:scale-95 transition-all duration-200"
        aria-label="Go Back"
        id="back-button"
      >
        <ArrowLeft size="20" />
      </button>
      <div>
        <h1 class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-zinc-200 to-zinc-400 bg-clip-text text-transparent">
          All Games
        </h1>
        <p class="text-zinc-500 text-[11px]">Explore our collection of immersive games</p>
      </div>
    </header>

    <!-- Search & Category Filters Row -->
    <div class="space-y-4 mb-6">
      <!-- Search Input -->
      <div class="relative group">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-500 group-focus-within:text-primary transition-colors duration-200">
          <Search size="18" />
        </span>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Search games..." 
          id="game-search-input"
          class="w-full bg-zinc-900/90 border border-white/5 rounded-2xl py-3 pl-10 pr-10 text-sm text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/20 transition-all duration-300 shadow-inner"
        />
        <button 
          v-if="searchQuery" 
          @click="searchQuery = ''" 
          class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-500 hover:text-white"
        >
          <X size="16" />
        </button>
      </div>

      <!-- Categories Tabs -->
      <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
        <button 
          v-for="tab in tabs" 
          :key="tab.value" 
          @click="activeTab = tab.value"
          :class="[
            'px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-300 border',
            activeTab === tab.value 
              ? 'bg-primary text-black border-primary shadow-lg shadow-primary/10 scale-105' 
              : 'bg-zinc-900/60 text-zinc-400 border-white/5 hover:text-zinc-200 hover:bg-zinc-800/40'
          ]"
        >
          <div class="flex items-center gap-1.5">
            <Gamepad2 v-if="tab.value === 'all'" size="14" />
            <Flame v-else-if="tab.value === 'casino'" size="14" />
            <Trophy v-else size="14" />
            <span>{{ tab.label }}</span>
            <span class="text-[10px] opacity-60 font-normal">({{ getCount(tab.value) }})</span>
          </div>
        </button>
      </div>
    </div>

    <!-- Games Compact Grid -->
    <div v-if="filteredGames.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-3">
      <router-link 
        v-for="game in filteredGames" 
        :key="game.slug" 
        :to="getGameRoute(game)"
        class="group flex flex-col active:scale-[0.97] transition-all duration-200"
      >
        <!-- Square Compact Image Wrapper -->
        <div class="w-full aspect-square rounded-xl overflow-hidden border border-white/5 bg-zinc-900/80 relative shadow-md">
          <img 
            :src="getGameImage(game)" 
            @error="e => handleImageError(e, game)"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out" 
            :alt="game.name"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-2">
            <div class="bg-primary/90 text-black rounded-full p-1.5 shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
              <Play size="12" fill="currentColor" />
            </div>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Empty State -->
    <div v-else class="flex flex-col items-center justify-center py-16 px-4 text-center">
      <div class="w-16 h-16 bg-zinc-900/80 rounded-full flex items-center justify-center mb-4 border border-white/5 text-zinc-500">
        <Gamepad2 size="32" class="opacity-40" />
      </div>
      <h3 class="font-bold text-zinc-300 text-base mb-1">No Games Found</h3>
      <p class="text-zinc-500 text-xs max-w-xs mb-6">We couldn't find any games matching "{{ searchQuery }}". Try checking your spelling or selecting another category.</p>
      <button 
        @click="resetFilters" 
        class="bg-zinc-800 hover:bg-zinc-700 text-white font-semibold text-xs px-4 py-2 rounded-xl border border-white/5 active:scale-95 transition-all duration-200"
      >
        Reset Filters
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ArrowLeft, Search, X, Gamepad2, Flame, Trophy, Play } from 'lucide-vue-next'
import axios from 'axios'

import tomJerryImg from '../assets/tom_and_jerry/create_an_image_where_both_202605220403.jpeg'

const searchQuery = ref('')
const activeTab = ref('all')
const games = ref([])

const tabs = [
  { label: 'All', value: 'all' },
  { label: 'Casino', value: 'casino' },
  { label: 'Skill', value: 'skill' }
]

const fetchGames = async () => {
  try {
    const { data } = await axios.get('/api/games')
    games.value = data
  } catch (e) {
    // Fallback to static lists if API is unavailable
    games.value = [
      { name: 'Cricket Super Over', type: 'skill', slug: 'cricket', is_featured: false },
      { name: 'Super Cricket 3D', type: 'skill', slug: 'cricket3d', is_featured: true },
      { name: 'Carrom Club', type: 'skill', slug: 'carrom', is_featured: false },
      { name: '8 Ball Pool 1v1', type: 'skill', slug: 'pool_1v1', is_featured: false },
      { name: 'Championship Snooker', type: 'skill', slug: 'pool_snooker', is_featured: false },
      { name: 'Ludo Pro', type: 'skill', slug: 'ludo', is_featured: true },
      { name: 'Chicken Road Cross', type: 'skill', slug: 'chicken', is_featured: false },
      { name: 'Dino Road Cross', type: 'skill', slug: 'dino-cross', is_featured: true },
      { name: 'Sabong Fight', type: 'casino', slug: 'chicken_fight', is_featured: true },
      { name: 'Aviator 3D', type: 'casino', slug: 'aviator', is_featured: true },
      { name: 'Tom & Jerry Race', type: 'casino', slug: 'tom_jerry', is_featured: true }
    ]
  }
}

// Counts for the categories tabs
const getCount = (tabValue) => {
  if (tabValue === 'all') return games.value.length
  return games.value.filter(g => g.type?.toLowerCase() === tabValue).length
}

const filteredGames = computed(() => {
  return games.value.filter(game => {
    // Category match
    const categoryMatch = activeTab.value === 'all' || game.type?.toLowerCase() === activeTab.value
    
    // Search match (fuzzy match by name or type)
    const normalizedQuery = searchQuery.value.toLowerCase().trim()
    const nameMatch = game.name?.toLowerCase().includes(normalizedQuery)
    const typeMatch = game.type?.toLowerCase().includes(normalizedQuery)
    
    return categoryMatch && (nameMatch || typeMatch)
  })
})

const getGameRoute = (game) => {
  if (!game) return '/'
  return `/games/${game.slug}`
}

const getGameImage = (game) => {
  if (game && game.thumbnail) {
    return `http://localhost:8000/storage/${game.thumbnail}`
  }
  if (game && game.image) {
    return `http://localhost:8000/storage/${game.image}`
  }
  const slug = game ? (typeof game === 'string' ? game : game.slug) : ''
  if (slug === 'tom_jerry') {
    return tomJerryImg
  }
  const images = {
    aviator: 'https://images.unsplash.com/photo-1569154941061-e231b4725ef1?q=80&w=400',
    ludo: 'https://images.unsplash.com/photo-1596720426673-e483d74482d7?q=80&w=400',
    pool: 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?q=80&w=400',
    pool_1v1: 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?q=80&w=400',
    pool_snooker: 'https://images.unsplash.com/photo-1609141975765-a8c6f2df6a09?q=80&w=400',
    chicken: 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?q=80&w=400',
    'dino-cross': 'https://images.unsplash.com/photo-1551103756-c466a693ad7a?q=80&w=400',
    chicken_fight: 'https://images.unsplash.com/photo-1608096299210-db7e38487075?q=80&w=400',
    cricket: 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?q=80&w=400',
    cricket3d: 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?q=80&w=400',
    carrom: 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?q=80&w=400',
    teen_patti: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=400'
  }
  return images[slug] || 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=400'
}

const handleImageError = (e, game) => {
  const slug = game?.slug || ''
  if (slug === 'tom_jerry') {
    e.target.src = tomJerryImg
    return
  }
  const images = {
    aviator: 'https://images.unsplash.com/photo-1569154941061-e231b4725ef1?q=80&w=400',
    ludo: 'https://images.unsplash.com/photo-1596720426673-e483d74482d7?q=80&w=400',
    pool: 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?q=80&w=400',
    pool_1v1: 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?q=80&w=400',
    pool_snooker: 'https://images.unsplash.com/photo-1609141975765-a8c6f2df6a09?q=80&w=400',
    chicken: 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?q=80&w=400',
    chicken_fight: 'https://images.unsplash.com/photo-1608096299210-db7e38487075?q=80&w=400',
    cricket: 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?q=80&w=400',
    cricket3d: 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?q=80&w=400',
    carrom: 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?q=80&w=400',
    teen_patti: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=400'
  }
  e.target.src = images[slug] || 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=400'
}

const resetFilters = () => {
  searchQuery.value = ''
  activeTab.value = 'all'
}

onMounted(() => {
  fetchGames()
})
</script>

<style scoped>
/* Custom styled hide scrollbars */
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
