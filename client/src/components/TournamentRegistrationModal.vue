<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div 
      class="absolute inset-0 bg-black/80 backdrop-blur-sm"
      @click="close"
    ></div>

    <!-- Modal Content -->
    <div class="relative bg-[#121623] border border-white/10 rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-2xl">
      <!-- Close Button -->
      <button 
        @click="close"
        class="absolute top-4 right-4 p-2 rounded-full bg-white/5 hover:bg-white/10 transition-colors"
      >
        <X size="20" class="text-white" />
      </button>

      <!-- Header -->
      <div class="p-6 border-b border-white/5">
        <div class="flex items-start gap-4">
          <div class="w-20 h-20 rounded-xl overflow-hidden bg-zinc-950 flex-shrink-0">
            <img 
              :src="getGameImage()" 
              class="w-full h-full object-cover"
            />
          </div>
          <div class="flex-1">
            <span 
              class="inline-flex px-2 py-0.5 rounded-md text-[8px] font-black uppercase border mb-2"
              :class="statusClasses"
            >
              {{ statusLabel }}
            </span>
            <h2 class="text-xl font-black text-white italic tracking-wide">{{ tournament.title }}</h2>
            <p class="text-slate-400 text-sm mt-1">{{ tournament.game?.title || 'Tournament' }}</p>
          </div>
        </div>
      </div>

      <!-- Prize Breakdown -->
      <div class="p-6 border-b border-white/5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-black text-neon-cyan uppercase tracking-wider">Prize Distribution</h3>
          <span 
            class="text-[10px] bg-neon-cyan/10 text-neon-cyan px-2 py-0.5 rounded-md font-bold uppercase tracking-wide"
          >
            🏆 Min to Max Range
          </span>
        </div>
        <div class="space-y-3">
          <!-- Winner -->
          <div class="p-3 bg-white/5 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-yellow-500/20 flex items-center justify-center">
                  <Crown size="16" class="text-yellow-500" />
                </div>
                <span class="text-white font-bold text-sm">Winner</span>
              </div>
              <span class="text-neon-cyan font-black text-lg">
                {{ currencySymbol }}{{ winnerPrize.min }} - {{ currencySymbol }}{{ winnerPrize.max }}
              </span>
            </div>
            <div class="text-[10px] text-slate-400 border-t border-white/5 pt-1.5 flex justify-between">
              <span>Based on {{ tournament.min_players || 2 }} players min</span>
              <span>{{ tournament.max_players }} players max</span>
            </div>
          </div>

          <!-- Runner-up -->
          <div class="p-3 bg-white/5 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-400/20 flex items-center justify-center">
                  <Medal size="16" class="text-slate-400" />
                </div>
                <span class="text-white font-bold text-sm">Runner-up</span>
              </div>
              <span class="text-neon-cyan font-black text-lg">
                {{ currencySymbol }}{{ runnerUpPrize.min }} - {{ currencySymbol }}{{ runnerUpPrize.max }}
              </span>
            </div>
            <div class="text-[10px] text-slate-400 border-t border-white/5 pt-1.5 flex justify-between">
              <span>Based on {{ tournament.min_players || 2 }} players min</span>
              <span>{{ tournament.max_players }} players max</span>
            </div>
          </div>

          <!-- Semifinalists -->
          <div v-if="tournament.include_semifinalist_prizes" class="p-3 bg-white/5 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-orange-500/20 flex items-center justify-center">
                  <Award size="16" class="text-orange-500" />
                </div>
                <span class="text-white font-bold text-sm">Semifinalists</span>
              </div>
              <span class="text-neon-cyan font-black text-lg">
                {{ currencySymbol }}{{ semifinalistPrize.min }} - {{ currencySymbol }}{{ semifinalistPrize.max }}
              </span>
            </div>
            <div class="text-[10px] text-slate-400 border-t border-white/5 pt-1.5 flex justify-between">
              <span>Based on {{ tournament.min_players || 2 }} players min</span>
              <span>{{ tournament.max_players }} players max</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tournament Details -->
      <div class="p-6 border-b border-white/5">
        <h3 class="text-sm font-black text-neon-cyan uppercase tracking-wider mb-4">Tournament Details</h3>
        <div class="grid grid-cols-2 gap-4">
          <div class="p-3 bg-white/5 rounded-xl">
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Entry Fee</span>
            <span class="text-white font-black text-lg">{{ currencySymbol }}{{ displayFee }}</span>
            <span v-if="tournament.is_dynamic_fee" class="block text-orange-400 text-[9px] font-bold mt-0.5">⚡ Dynamic Pricing</span>
          </div>
          <div class="p-3 bg-white/5 rounded-xl">
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Prize Pool Range</span>
            <span class="text-white font-black text-base leading-7">
              {{ currencySymbol }}{{ currentPool.min }} - {{ currencySymbol }}{{ currentPool.max }}
            </span>
          </div>
          <div class="p-3 bg-white/5 rounded-xl">
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Players Registered</span>
            <span class="text-white font-black text-lg">{{ tournament.registered_players_count }}/{{ tournament.max_players }}</span>
          </div>
          <div class="p-3 bg-white/5 rounded-xl">
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Time Left</span>
            <span class="text-white font-black text-lg">{{ timeRemaining }}</span>
          </div>
          <div class="p-3 bg-white/5 rounded-xl col-span-2">
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Bracket Format</span>
            <span class="text-white font-black text-sm">
              {{ tournament.bracket_type === 'double_elimination' ? '🏆 Double Elimination' : 'Single Elimination' }}
            </span>
          </div>
        </div>

        <!-- FOMO Early Bird Banner -->
        <div
          v-if="tournament.is_dynamic_fee && tournament.status === 'open'"
          class="mt-4 p-4 rounded-xl bg-gradient-to-r from-orange-500/20 via-red-500/15 to-pink-500/10 border border-orange-500/40"
        >
          <div class="flex items-start gap-3">
            <span class="text-2xl">🔥</span>
            <div>
              <p class="text-orange-300 font-black text-sm">Early Bird Pricing Active!</p>
              <p class="text-orange-200/80 text-xs mt-1">
                Price increases every <strong>{{ tournament.fee_increment_every_n_players }}</strong> registrations by
                <strong>{{ currencySymbol }}{{ tournament.fee_increment_amount }}</strong>.
                Register now at <strong>{{ currencySymbol }}{{ displayFee }}</strong> before it goes up!
              </p>
              <p class="text-orange-300 text-xs font-bold mt-2">
                Next price: {{ currencySymbol }}{{ nextFee }} — only {{ spotsUntilPriceRise }} spots left at this price!
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Rules -->
      <div v-if="tournament.game?.guidelines" class="p-6 border-b border-white/5">
        <h3 class="text-sm font-black text-neon-cyan uppercase tracking-wider mb-4">Rules & Guidelines</h3>
        <div class="text-slate-300 text-sm leading-relaxed whitespace-pre-line">
          {{ tournament.game.guidelines }}
        </div>
      </div>

      <!-- Action Button -->
      <div class="p-6">
        <div
          v-if="tournament.is_registered"
          class="w-full py-4 bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 font-black uppercase tracking-wider rounded-xl text-center"
        >
          ✓ Already Registered
        </div>
        <button
          v-else-if="tournament.is_open_for_registration && !tournament.is_full"
          @click="register"
          :disabled="loading"
          class="w-full py-4 bg-gradient-to-r from-neon-cyan to-neon-pink text-white font-black uppercase tracking-wider rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="loading">Processing...</span>
          <span v-else>Register for {{ currencySymbol }}{{ displayFee }}</span>
        </button>
        <button
          v-else-if="tournament.is_full"
          disabled
          class="w-full py-4 bg-slate-700 text-slate-400 font-black uppercase tracking-wider rounded-xl cursor-not-allowed"
        >
          Tournament Full
        </button>
        <button
          v-else
          disabled
          class="w-full py-4 bg-slate-700 text-slate-400 font-black uppercase tracking-wider rounded-xl cursor-not-allowed"
        >
          Registration Closed
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { X, Crown, Medal, Award } from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
  tournament: {
    type: Object,
    required: true
  },
  isOpen: {
    type: Boolean,
    default: false
  },
  currencySymbol: {
    type: String,
    default: 'Rs.'
  }
})

const emit = defineEmits(['close', 'registered'])

const loading = ref(false)
const timeRemaining = ref('')

const displayFee = computed(() => {
  if (props.tournament.is_dynamic_fee && props.tournament.base_entry_fee) {
    const increments = Math.floor(props.tournament.registered_players_count / (props.tournament.fee_increment_every_n_players || 1))
    return (parseFloat(props.tournament.base_entry_fee) + increments * parseFloat(props.tournament.fee_increment_amount || 0)).toFixed(2)
  }
  return props.tournament.entry_fee
})

const nextFee = computed(() => {
  if (!props.tournament.is_dynamic_fee) return props.tournament.entry_fee
  const increments = Math.floor(props.tournament.registered_players_count / (props.tournament.fee_increment_every_n_players || 1))
  return (parseFloat(props.tournament.base_entry_fee) + (increments + 1) * parseFloat(props.tournament.fee_increment_amount || 0)).toFixed(2)
})

const spotsUntilPriceRise = computed(() => {
  if (!props.tournament.is_dynamic_fee) return 0
  const n = props.tournament.fee_increment_every_n_players || 1
  return n - (props.tournament.registered_players_count % n)
})

const statusClasses = computed(() => {
  switch (props.tournament.status) {
    case 'open':
      return 'bg-neon-cyan/15 text-neon-cyan border-neon-cyan/25'
    case 'registration_closed':
      return 'bg-neon-pink/15 text-neon-pink border-neon-pink/25'
    case 'in_progress':
      return 'bg-green-500/15 text-green-400 border-green-500/25'
    default:
      return 'bg-slate-500/15 text-slate-400 border-slate-500/25'
  }
})

const statusLabel = computed(() => {
  switch (props.tournament.status) {
    case 'open':
      return 'Open for Registration'
    case 'registration_closed':
      return 'Registration Closed'
    case 'in_progress':
      return 'In Progress'
    default:
      return 'Draft'
  }
})

// Calculates the entry pools mapping explicitly from the minimum player requirement up to max slots
const currentPool = computed(() => {
  const minPlayers = props.tournament.min_players || 2 // Fallback to 2 players if backend property is omitted
  const maxPlayers = props.tournament.max_players || 0

  let minPool = 0
  let maxPool = 0

  // 1. If it's an escalating dynamic pricing tournament, step-simulate both metrics
  if (props.tournament.is_dynamic_fee && props.tournament.base_entry_fee) {
    const incrementEvery = props.tournament.fee_increment_every_n_players || 1
    const baseFee = parseFloat(props.tournament.base_entry_fee)
    const incAmount = parseFloat(props.tournament.fee_increment_amount || 0)

    // Calculate Min Player Pool accumulation
    for (let i = 0; i < minPlayers; i++) {
      const increments = Math.floor(i / incrementEvery)
      minPool += baseFee + (increments * incAmount)
    }

    // Calculate Max Player Pool accumulation
    for (let i = 0; i < maxPlayers; i++) {
      const increments = Math.floor(i / incrementEvery)
      maxPool += baseFee + (increments * incAmount)
    }
  } else {
    // 2. Standard uniform flat fee math
    const fee = props.tournament.entry_fee || 0
    minPool = fee * minPlayers
    maxPool = fee * maxPlayers
  }

  return {
    min: minPool.toFixed(2),
    max: maxPool.toFixed(2)
  }
})

// Reusable calculation logic mapping target prize lines against the platform commission structures
const calculatePrizeFromPool = (poolValue, prizePercentage) => {
  const pool = parseFloat(poolValue)
  const platformCommission = pool * (props.tournament.platform_commission_percentage / 100)
  const prizePool = pool - platformCommission
  return (prizePool * (prizePercentage / 100)).toFixed(2)
}

const winnerPrize = computed(() => {
  return {
    min: calculatePrizeFromPool(currentPool.value.min, props.tournament.winner_prize_percentage),
    max: calculatePrizeFromPool(currentPool.value.max, props.tournament.winner_prize_percentage)
  }
})

const runnerUpPrize = computed(() => {
  return {
    min: calculatePrizeFromPool(currentPool.value.min, props.tournament.runner_up_prize_percentage),
    max: calculatePrizeFromPool(currentPool.value.max, props.tournament.runner_up_prize_percentage)
  }
})

const semifinalistPrize = computed(() => {
  if (!props.tournament.include_semifinalist_prizes || !props.tournament.semifinalist_prize_percentage) {
    return { min: '0.00', max: '0.00' }
  }
  return {
    min: calculatePrizeFromPool(currentPool.value.min, props.tournament.semifinalist_prize_percentage),
    max: calculatePrizeFromPool(currentPool.value.max, props.tournament.semifinalist_prize_percentage)
  }
})

const updateTimeRemaining = () => {
  const now = new Date()
  const target = new Date(props.tournament.registration_closes_at)

  if (target < now) {
    timeRemaining.value = 'Ended'
    return
  }

  const diff = target - now
  
  if (diff > 86400000) {
    const days = Math.floor(diff / 86400000)
    timeRemaining.value = `${days}d`
  } else if (diff > 3600000) {
    const hours = Math.floor(diff / 3600000)
    const minutes = Math.floor((diff % 3600000) / 60000)
    timeRemaining.value = `${hours}h ${minutes}m`
  } else if (diff > 60000) {
    const minutes = Math.floor(diff / 60000)
    const seconds = Math.floor((diff % 60000) / 1000)
    timeRemaining.value = `${minutes}m ${seconds}s`
  } else {
    const seconds = Math.floor(diff / 1000)
    timeRemaining.value = `${seconds}s`
  }
}

let timerInterval

onMounted(() => {
  updateTimeRemaining()
  timerInterval = setInterval(updateTimeRemaining, 1000)
})

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})

const getGameImage = () => {
  if (props.tournament.game?.image) {
    return `http://localhost:8000/storage/${props.tournament.game.image}`
  }
  if (props.tournament.game?.thumbnail) {
    return `http://localhost:8000/storage/${props.tournament.game.thumbnail}`
  }
  return 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=600'
}

const close = () => {
  emit('close')
}

const register = async () => {
  try {
    loading.value = true
    await axios.post(`/api/tournaments/${props.tournament.id}/register`)
    emit('registered')
    close()
  } catch (error) {
    console.error('Registration failed:', error)
    alert(error.response?.data?.error || 'Registration failed. Please try again.')
  } finally {
    loading.value = false
  }
}
</script>