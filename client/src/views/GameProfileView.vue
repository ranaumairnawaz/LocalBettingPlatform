<template>
  <div v-if="game" class="min-h-screen text-slate-100 flex flex-col gap-6">
    <!-- Top Nav Back -->
    <div class="flex items-center gap-4">
      <button 
        @click="$router.push('/')" 
        class="p-2.5 bg-white/5 border border-white/5 hover:border-neon-cyan/40 hover:bg-white/10 rounded-xl transition-all cursor-pointer"
      >
        <ArrowLeft size="20" class="text-neon-cyan" />
      </button>
      <div class="flex flex-col">
        <h2 class="text-xs uppercase tracking-widest text-slate-400 font-bold">Game Hub</h2>
        <h1 class="text-xl font-bold text-slate-100">{{ game.name }} Overview</h1>
      </div>
    </div>

    <!-- Parallax Hero Cover Banner -->
    <div class="relative w-full aspect-video max-h-[280px] sm:max-h-[360px] md:max-h-[420px] rounded-3xl overflow-hidden border border-white/5 shadow-2xl bg-zinc-900">
      <!-- Background Image -->
      <img 
        :src="getCoverImage(game)" 
        class="absolute inset-0 w-full h-full object-cover opacity-90 scale-100 hover:scale-102 transition-transform duration-700"
      />
      <!-- Glowing Overlay -->
      <div class="absolute inset-0 bg-gradient-to-t from-[#090a0f] via-transparent to-transparent"></div>
    </div>

    <!-- Structured Game Details Panel Below -->
    <div class="glow-card-cyan p-5 md:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
      <div class="flex items-center gap-4 md:gap-6">
        <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl border border-white/10 shadow-lg overflow-hidden bg-zinc-950 shrink-0">
          <img :src="getLogoImage(game)" class="w-full h-full object-cover" />
        </div>
        <div class="flex flex-col justify-center">
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-neon-cyan/15 text-neon-cyan border border-neon-cyan/25 w-max mb-2">
            {{ game.type }}
          </span>
          <h2 class="text-xl md:text-3xl font-black text-white italic tracking-wide">{{ game.name }}</h2>
          <p class="text-xs text-slate-400 mt-1 font-semibold">Min Bet: <span class="text-white">{{ currencySymbol }}{{ game.min_bet }}</span> • Max Bet: <span class="text-white">{{ currencySymbol }}{{ game.max_bet }}</span></p>
        </div>
      </div>

      <button 
        @click="launchGame" 
        class="flex items-center justify-center gap-3 bg-neon-pink text-white font-black italic px-8 py-3.5 rounded-2xl hover:scale-105 active:scale-95 transition-all shadow-[0_0_15px_rgba(255,0,127,0.4)] hover:shadow-[0_0_25px_rgba(255,0,127,0.6)] cursor-pointer"
      >
        <span>PLAY NOW</span>
        <Play size="18" class="fill-white" />
      </button>
    </div>

    <!-- Detailed Content Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left 2 Columns: Guidelines & Description -->
      <div class="lg:col-span-2 glow-card-cyan p-6 md:p-8 flex flex-col gap-6">
        <div class="flex items-center gap-2 border-b border-white/5 pb-3">
          <BookOpen size="20" class="text-neon-cyan" />
          <h3 class="font-black text-lg text-white">How to Play & Guidelines</h3>
        </div>
        
        <!-- Guidelines content -->
        <div class="text-slate-300 text-sm leading-relaxed whitespace-pre-line font-medium">
          <div v-if="game.guidelines">
            {{ game.guidelines }}
          </div>
          <div v-else class="text-slate-500 italic flex flex-col items-center justify-center py-10 gap-2">
            <HelpCircle size="32" class="stroke-[1.5]" />
            <p>No custom guidelines are set for this game yet.</p>
            <p class="text-[10px] uppercase font-bold text-slate-600">Standard system matching and payouts apply</p>
          </div>
        </div>
      </div>

      <!-- Right Column: Game specifications & Quick metrics -->
      <div class="flex flex-col gap-6">
        <!-- Specs Card -->
        <div class="glow-card-cyan p-6 flex flex-col gap-4">
          <div class="flex items-center gap-2 border-b border-white/5 pb-2">
            <Trophy size="18" class="text-neon-cyan" />
            <h4 class="font-black text-sm text-white">Specifications</h4>
          </div>

          <div class="space-y-3.5 text-xs font-semibold">
            <div class="flex justify-between">
              <span class="text-slate-400">Game Type:</span>
              <span class="text-white uppercase">{{ game.type }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Platform Commission:</span>
              <span class="text-neon-cyan">{{ game.commission_rate }}%</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Status:</span>
              <span class="text-neon-green">ACTIVE & VERIFIED</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">RTP (Return to Player):</span>
              <span class="text-neon-gold">96.5% - 98%</span>
            </div>
          </div>
        </div>

        <!-- Hot Matches Card -->
        <div class="glow-card-pink p-6 flex flex-col gap-4">
          <div class="flex items-center gap-2 border-b border-white/5 pb-2">
            <Users size="18" class="text-neon-pink" />
            <h4 class="font-black text-sm text-white">Active Lobby</h4>
          </div>

          <div class="flex items-center justify-between py-2">
            <div class="flex flex-col">
              <span class="text-xl font-black text-neon-pink">142+</span>
              <span class="text-[10px] text-slate-400 font-bold uppercase">Players Online</span>
            </div>
            <div class="w-1.5 h-1.5 rounded-full bg-neon-green animate-pulse shadow-[0_0_8px_#39ff14]"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading State -->
  <div v-else class="min-h-screen flex items-center justify-center">
    <div class="text-center">
      <div class="w-12 h-12 border-4 border-neon-cyan border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
      <p class="text-slate-500 font-bold">Loading Game Profile...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Play, BookOpen, Trophy, Users, HelpCircle } from 'lucide-vue-next'
import axios from 'axios'

import tomJerryImg from '../assets/tom_and_jerry/create_an_image_where_both_202605220403.jpeg'

const route = useRoute()
const router = useRouter()
const game = ref(null)
const currencySymbol = ref('Rs.')

const fetchGameDetails = async () => {
  try {
    const { data } = await axios.get('/api/games')
    const found = data.find(g => g.slug === route.params.slug)
    if (found) {
      game.value = found
    } else {
      console.error('Game not found')
      router.push('/')
    }
  } catch (e) {
    console.error('Failed to load game details', e)
  }
}

const fetchSettings = async () => {
  try {
    const { data } = await axios.get('/api/settings')
    if (data.currency_symbol) {
      currencySymbol.value = data.currency_symbol
    }
  } catch (e) {
    console.error('Failed to load settings', e)
  }
}

const getLogoImage = (g) => {
  if (g.image) {
    return `http://localhost:8000/storage/${g.image}`
  }
  if (g.thumbnail) {
    return `http://localhost:8000/storage/${g.thumbnail}`
  }
  if (g.slug === 'tom_jerry') {
    return tomJerryImg
  }
  return ''
}

const getThumbnailImage = (g) => {
  if (g.thumbnail) {
    return `http://localhost:8000/storage/${g.thumbnail}`
  }
  if (g.image) {
    return `http://localhost:8000/storage/${g.image}`
  }
  if (g.slug === 'tom_jerry') {
    return tomJerryImg
  }
  return ''
}

const getCoverImage = (g) => {
  if (g.thumbnail) {
    return `http://localhost:8000/storage/${g.thumbnail}`
  }
  if (g.image) {
    return `http://localhost:8000/storage/${g.image}`
  }
  if (g.slug === 'tom_jerry') {
    return tomJerryImg
  }
  return ''
}

const launchGame = () => {
  const slug = game.value.slug
  if (slug === 'chicken') {
    router.push('/games/chicken')
  } else if (slug === 'dino-cross') {
    router.push('/games/dino-cross')
  } else if (slug === 'chicken_fight') {
    router.push('/game/chicken-fight')
  } else if (slug === 'tom_jerry') {
    router.push('/game/tom-jerry')
  } else if (slug === 'aviator') {
    router.push('/game/aviator')
  } else if (slug === 'teen_patti') {
    router.push('/game/three-patti')
  } else if (slug === 'ludo') {
    router.push('/ludo')
  } else if (slug === 'cricket') {
    router.push('/cricket')
  } else if (slug === 'cricket3d') {
    router.push('/cricket3d')
  } else if (slug === 'carrom') {
    router.push('/carrom')
  } else if (slug.startsWith('pool_')) {
    router.push(`/pool/${slug}`)
  } else {
    router.push(`/game/${slug}`)
  }
}

onMounted(() => {
  fetchGameDetails()
  fetchSettings()
})
</script>
