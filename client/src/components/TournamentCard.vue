<template>
  <div 
    class="glow-card-pink overflow-hidden flex flex-col cursor-pointer transition-all duration-300 bg-[#121623]/40 border border-white/10 rounded-3xl group"
    @click="showDetails"
  >
    <!-- Game Image Cover -->
    <div class="relative w-full aspect-video overflow-hidden shrink-0">
      <img 
        :src="getGameImage()" 
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out opacity-80" 
      />
      <div class="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-[#121623] to-transparent"></div>
      
      <!-- Status Badge -->
      <div class="absolute top-3 left-3">
        <span 
          class="inline-flex px-2 py-0.5 rounded-md text-[8px] font-black uppercase border"
          :class="statusClasses"
        >
          {{ statusLabel }}
        </span>
      </div>
      
      <!-- Countdown Timer -->
      <div class="absolute top-3 right-3">
        <div class="bg-black/60 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/10">
          <div class="flex items-center gap-1.5">
            <Timer size="12" class="text-neon-cyan" />
            <span class="text-[10px] font-black text-white font-mono">{{ timeRemaining }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Card Content -->
    <div class="p-5 flex flex-col text-left gap-2 bg-[#121623]/60 backdrop-blur-md flex-1">
      <div class="flex items-center justify-between">
        <span class="text-[9px] font-bold text-slate-400 font-mono">
          {{ tournament.game?.title || 'Tournament' }}
        </span>
        <span class="text-[9px] font-bold text-neon-cyan font-mono">
          Pool: {{ currencySymbol }}{{ totalPool }}
        </span>
      </div>
      
      <h4 class="text-lg font-black text-white italic tracking-wide mt-1 group-hover:text-neon-pink transition-colors">
        {{ tournament.title }}
      </h4>
      
      <div class="grid grid-cols-2 gap-3 mt-2 pt-2 border-t border-white/5">
        <div class="flex flex-col">
          <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Entry Fee</span>
          <span class="text-sm font-black text-neon-cyan">{{ currencySymbol }}{{ displayFee }}</span>
          <span v-if="tournament.is_dynamic_fee" class="text-[8px] text-orange-400 font-bold animate-pulse">⚡ Early Bird</span>
        </div>
        <div class="flex flex-col">
          <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Players</span>
          <span class="text-sm font-black text-white">{{ tournament.registered_players_count }}/{{ tournament.max_players }}</span>
        </div>
      </div>
      
      <!-- Dynamic Fee FOMO Banner -->
      <div
        v-if="tournament.is_dynamic_fee && tournament.status === 'open'"
        class="mt-2 px-3 py-2 rounded-lg bg-gradient-to-r from-orange-500/20 to-red-500/20 border border-orange-500/30 text-[9px] font-black text-orange-300"
      >
        🔥 Price rises every {{ tournament.fee_increment_every_n_players }} registrations · Next: {{ currencySymbol }}{{ nextFee }}
      </div>

      <div class="flex items-center justify-between mt-2">
        <span class="text-[10px] text-neon-cyan font-black uppercase tracking-wider">
          {{ tournament.is_open_for_registration ? 'Register Now' : 'View Details' }}
        </span>
        <ArrowRight size="14" class="text-slate-400 group-hover:translate-x-1 group-hover:text-neon-pink transition-all" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Timer, ArrowRight } from 'lucide-vue-next'

const props = defineProps({
  tournament: {
    type: Object,
    required: true
  },
  currencySymbol: {
    type: String,
    default: 'Rs.'
  }
})

const emit = defineEmits(['show-details'])

const timeRemaining = ref('')

const statusClasses = computed(() => {
  switch (props.tournament.status) {
    case 'open':
      return 'bg-neon-cyan/15 text-neon-cyan border-neon-cyan/25'
    case 'registration_closed':
      return 'bg-neon-pink/15 text-neon-pink border-neon-pink/25'
    case 'in_progress':
      return 'bg-green-500/15 text-green-400 border-green-500/25'
    case 'completed':
      return 'bg-slate-500/15 text-slate-400 border-slate-500/25'
    default:
      return 'bg-slate-500/15 text-slate-400 border-slate-500/25'
  }
})

const statusLabel = computed(() => {
  switch (props.tournament.status) {
    case 'open':
      return 'Open'
    case 'registration_closed':
      return 'Starting Soon'
    case 'in_progress':
      return 'Live'
    case 'completed':
      return 'Completed'
    default:
      return 'Draft'
  }
})

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

const totalPool = computed(() => {
  return props.tournament.total_pool || (props.tournament.entry_fee * props.tournament.registered_players_count).toFixed(2)
})

const updateTimeRemaining = () => {
  const now = new Date()
  let target
  
  if (props.tournament.status === 'open' || props.tournament.status === 'draft') {
    target = new Date(props.tournament.registration_closes_at)
  } else if (props.tournament.status === 'registration_closed') {
    target = new Date(props.tournament.starts_at)
  } else {
    timeRemaining.value = 'Started'
    return
  }

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

const showDetails = () => {
  emit('show-details', props.tournament)
}
</script>
