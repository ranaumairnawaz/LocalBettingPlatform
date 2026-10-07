<template>
  <div class="space-y-8 pb-10">

    <!-- Glowing Parallax Welcome Banner -->
    <div class="bg-gradient-to-r from-[#121623]/80 to-[#0d0c15] border border-white/5 rounded-3xl p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center relative overflow-hidden shadow-2xl">
      <!-- Background neon graphics -->
      <div class="absolute -right-20 -top-20 w-80 h-80 bg-neon-pink/5 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-neon-cyan/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="relative z-10 space-y-2 max-w-lg">
        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] font-black uppercase bg-neon-cyan/15 text-neon-cyan border border-neon-cyan/25">
          <span class="w-1.5 h-1.5 rounded-full bg-neon-cyan animate-ping"></span>
          <span>SYSTEM ONLINE</span>
        </span>
        <h2 class="text-2xl md:text-3xl font-black text-white italic tracking-wide">WELCOME BACK, <span class="text-transparent bg-clip-text bg-gradient-to-r from-neon-cyan to-neon-pink">{{ auth.user?.username }}</span></h2>
        <p class="text-slate-400 text-xs font-semibold leading-relaxed">Join active match lobbies, slide strikers, sync pool physics, and cashout your winnings instantly.</p>
      </div>

      <div class="hidden md:flex items-center gap-4 relative z-10 shrink-0">
        <!-- Quick stats -->
        <div class="p-4 bg-white/5 border border-white/5 rounded-2xl flex flex-col items-center min-w-[100px]">
          <span class="text-[9px] uppercase tracking-widest text-slate-400 font-bold">STAKE BALANCE</span>
          <span class="text-lg font-black text-neon-cyan mt-1">{{ currencySymbol }}{{ auth.user?.balance || '0.00' }}</span>
        </div>
      </div>
    </div>

    <!-- Quick Navigation Icons -->
    <div class="grid grid-cols-4 gap-3">
      <button 
        v-for="cat in categories" 
        :key="cat.name" 
        @click="$router.push(cat.path)" 
        class="bg-[#121623]/60 border border-white/5 hover:border-neon-cyan/30 p-3 rounded-2xl text-[10px] uppercase font-black tracking-widest flex flex-col items-center gap-2 active:scale-95 transition-all duration-300 cursor-pointer shadow-sm hover:shadow-[0_0_15px_rgba(0,242,254,0.1)] group"
      >
        <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center border border-white/5 group-hover:bg-neon-cyan/10 transition-colors">
          <Gamepad2 v-if="cat.name === 'Games'" size="18" class="text-neon-cyan" />
          <Trophy v-else-if="cat.name === 'Rankings'" size="18" class="text-neon-cyan" />
          <Wallet v-else-if="cat.name === 'Wallet'" size="18" class="text-neon-cyan" />
          <User v-else size="18" class="text-neon-cyan" />
        </div>
        <span class="text-slate-300 group-hover:text-slate-100">{{ cat.name }}</span>
      </button>
    </div>

    <!-- TOURNAMENT CAROUSEL -->
    <TournamentCarousel
      @show-details="handleTournamentDetails"
    />

    <!-- Registration Modal (for carousel) -->
    <TournamentRegistrationModal
      v-if="selectedTournament && showTournamentModal"
      :tournament="selectedTournament"
      :is-open="showTournamentModal"
      :currency-symbol="currencySymbol"
      @close="showTournamentModal = false"
      @registered="onTournamentRegistered"
    />

    <section class="space-y-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center gap-2">
          <Flame size="18" class="text-neon-pink animate-pulse" />
          <h3 class="font-black text-lg text-white italic tracking-wider">FEATURED GAMES</h3>
        </div>
        <router-link to="/games" class="text-neon-cyan hover:text-neon-cyan/85 font-black uppercase text-xs tracking-wider flex items-center gap-1.5 group">
          <span>See All</span>
          <ArrowRight size="14" class="group-hover:translate-x-1 transition-transform" />
        </router-link>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <router-link 
          v-for="game in featuredGames" 
          :key="game.slug" 
          :to="'/games/' + game.slug"
          class="group glow-card-pink overflow-hidden flex flex-col cursor-pointer transition-all duration-300 bg-[#121623]/40 border border-white/10 rounded-3xl"
        >
          <!-- Featured Image Cover (Top part) -->
          <div class="relative w-full aspect-video overflow-hidden shrink-0">
            <img 
              :src="getGameImage(game)" 
              @error="e => handleImageError(e, game)"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out opacity-80" 
            />
            <!-- Dark gradient bottom overlay on image to merge beautifully with card base -->
            <div class="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-[#121623] to-transparent"></div>
          </div>

          <!-- Card Content (Bottom part, below the thumbnail image) -->
          <div class="p-5 flex flex-col text-left gap-2 bg-[#121623]/60 backdrop-blur-md flex-1">
            <div class="flex items-center justify-between">
              <span class="inline-flex px-2 py-0.5 rounded-md text-[8px] font-black uppercase bg-neon-pink/15 text-neon-pink border border-neon-pink/25">
                {{ game.type }}
              </span>
              <span class="text-[9px] font-bold text-slate-400 font-mono">Min Bet: {{ currencySymbol }}{{ game.min_bet }}</span>
            </div>
            
            <h4 class="text-xl font-black text-white italic tracking-wide mt-1 group-hover:text-neon-pink transition-colors">
              {{ game.name }}
            </h4>
            
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-white/5">
              <span class="text-[10px] text-neon-cyan font-black uppercase tracking-wider">Play Tourney</span>
              <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider group-hover:translate-x-1 transition-transform duration-300 flex items-center gap-1">
                Lobby &amp; Rules ➜
              </span>
            </div>
          </div>
        </router-link>
      </div>
    </section>

    <!-- OTHER GAMES: COMPACT LOGO QUICK-LAUNCH CHIPS -->
    <section class="space-y-4">
      <div class="flex items-center gap-2">
        <Gamepad2 size="18" class="text-neon-cyan" />
        <h3 class="font-black text-lg text-white italic tracking-wider">QUICK LAUNCH ARENA</h3>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <router-link 
          v-for="game in otherGames" 
          :key="game.slug" 
          :to="'/games/' + game.slug"
          class="glow-card-cyan p-3.5 flex items-center gap-3 active:scale-[0.98] transition-all duration-300 group cursor-pointer"
        >
          <!-- Small logo preview inside card -->
          <div class="w-10 h-10 rounded-xl border border-white/10 overflow-hidden bg-zinc-950 flex items-center justify-center shrink-0 shadow-md">
            <img 
              :src="getGameThumbnail(game)" 
              @error="e => handleImageError(e, game)"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
            />
          </div>
          <!-- Info -->
          <div class="flex flex-col text-left overflow-hidden">
            <span class="text-xs font-black text-slate-200 truncate group-hover:text-neon-cyan transition-colors leading-snug">
              {{ game.name }}
            </span>
            <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">
              {{ game.type }}
            </span>
          </div>
        </router-link>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, Gamepad2, Trophy, Users, Gift, Flame, ArrowRight, Wallet, User } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import axios from 'axios'
import TournamentCarousel from '../components/TournamentCarousel.vue'
import TournamentRegistrationModal from '../components/TournamentRegistrationModal.vue'

import tomJerryImg from '../assets/tom_and_jerry/create_an_image_where_both_202605220403.jpeg'

const router = useRouter()
const auth = useAuthStore()
const showTournamentModal = ref(false)
const selectedTournament = ref(null)
const categories = [
  { name: 'Games', path: '/games' },
  { name: 'Rankings', path: '/rankings' },
  { name: 'Wallet', path: '/wallet' },
  { name: 'Profile', path: '/profile' }
]
const featuredGames = ref([])
const otherGames = ref([])

const siteName = ref('666EGame')
const siteLogo = ref('')
const currencySymbol = ref('Rs.')

const fetchSettings = async () => {
  try {
    const { data } = await axios.get('/api/settings')
    if (data.site_name) {
      siteName.value = data.site_name
    }
    if (data.site_logo) {
      siteLogo.value = `http://localhost:8000/storage/${data.site_logo}`
    }
    if (data.currency_symbol) {
      currencySymbol.value = data.currency_symbol
    }
  } catch (e) {
    console.error('Failed to fetch settings:', e)
  }
}

const fetchGames = async () => {
  try {
    const { data } = await axios.get('/api/games')
    featuredGames.value = data.filter(g => g.is_featured)
    otherGames.value = data.filter(g => !g.is_featured)
  } catch (e) {
    // Fallback static list
    const fallback = [
      { name: 'Cricket Super Over', type: 'Skill', slug: 'cricket', is_featured: false },
      { name: 'Super Cricket 3D', type: 'Skill', slug: 'cricket3d', is_featured: true },
      { name: 'Carrom Club', type: 'Skill', slug: 'carrom', is_featured: false },
      { name: '8 Ball Pool 1v1', type: 'Skill', slug: 'pool_1v1', is_featured: false },
      { name: 'Championship Snooker', type: 'Skill', slug: 'pool_snooker', is_featured: false },
      { name: 'Ludo Pro', type: 'Skill', slug: 'ludo', is_featured: true },
      { name: 'Chicken Road Cross', type: 'Skill', slug: 'chicken', is_featured: false },
      { name: 'Dino Road Cross', type: 'Skill', slug: 'dino-cross', is_featured: true },
      { name: 'Sabong Fight', type: 'Casino', slug: 'chicken_fight', is_featured: true },
      { name: 'Aviator 3D', type: 'Casino', slug: 'aviator', is_featured: true },
      { name: 'Tom & Jerry Race', type: 'Casino', slug: 'tom_jerry', is_featured: true }
    ]
    featuredGames.value = fallback.filter(g => g.is_featured)
    otherGames.value = fallback.filter(g => !g.is_featured)
  }
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
    aviator: 'https://images.unsplash.com/photo-1569154941061-e231b4725ef1?q=80&w=600',
    ludo: 'https://images.unsplash.com/photo-1596720426673-e483d74482d7?q=80&w=600',
    pool: 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?q=80&w=600',
    pool_1v1: 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?q=80&w=600',
    pool_snooker: 'https://images.unsplash.com/photo-1609141975765-a8c6f2df6a09?q=80&w=600',
    chicken: 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?q=80&w=600',
    chicken_fight: 'https://images.unsplash.com/photo-1608096299210-db7e38487075?q=80&w=600',
    cricket: 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?q=80&w=600',
    cricket3d: 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?q=80&w=600',
    carrom: 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?q=80&w=600',
    teen_patti: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=600'
  }
  return images[slug] || 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=600'
}

const getGameThumbnail = (game) => {
  if (game && game.thumbnail) {
    return `http://localhost:8000/storage/${game.thumbnail}`
  }
  return getGameImage(game)
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
    carrom: 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?q=80&w=400',
    teen_patti: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=400'
  }
  e.target.src = images[slug] || 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=400'
}

const onTournamentRegistered = () => {
  // balance refresh on registration
  auth.fetchUser()
}

const handleTournamentDetails = (tournament) => {
  selectedTournament.value = tournament
  if (tournament.is_registered || tournament.status === 'in_progress' || tournament.status === 'completed') {
    router.push({ name: 'tournaments' })
  } else {
    showTournamentModal.value = true
  }
}

onMounted(() => {
  fetchGames()
  fetchSettings()
  auth.fetchUser()
})
</script>
