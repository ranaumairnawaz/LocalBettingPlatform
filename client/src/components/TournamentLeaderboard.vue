<template>
  <div class="space-y-4">
    <!-- Header with Stats -->
    <div class="grid grid-cols-3 gap-3">
      <div class="p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-xl text-center">
        <Crown :size="16" class="text-yellow-400 mx-auto mb-1" />
        <span class="text-[9px] text-slate-400 uppercase font-bold block">Winner Prize</span>
        <span class="text-sm font-black text-yellow-400">{{ currencySymbol }}{{ winnerPrize }}</span>
      </div>
      <div class="p-3 bg-neon-cyan/10 border border-neon-cyan/20 rounded-xl text-center">
        <Users :size="16" class="text-neon-cyan mx-auto mb-1" />
        <span class="text-[9px] text-slate-400 uppercase font-bold block">Players</span>
        <span class="text-sm font-black text-neon-cyan">{{ leaderboard.length }}</span>
      </div>
      <div class="p-3 bg-neon-pink/10 border border-neon-pink/20 rounded-xl text-center">
        <Zap :size="16" class="text-neon-pink mx-auto mb-1" />
        <span class="text-[9px] text-slate-400 uppercase font-bold block">Status</span>
        <span class="text-sm font-black text-neon-pink">{{ status }}</span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-neon-cyan"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="leaderboard.length === 0" class="text-center py-10">
      <Trophy :size="40" class="text-slate-600 mx-auto mb-3" />
      <p class="text-slate-400 text-sm font-semibold">No rankings yet</p>
      <p class="text-slate-500 text-xs mt-1">Tournament may not have started</p>
    </div>

    <!-- Leaderboard List -->
    <div v-else class="space-y-2">
      <!-- Top 3 Podium (if enough players) -->
      <div v-if="leaderboard.length >= 3" class="grid grid-cols-3 gap-2 mb-4">
        <!-- 2nd Place -->
        <div class="flex flex-col items-center pt-4">
          <div class="relative">
            <div class="w-12 h-12 rounded-full bg-slate-400/20 border-2 border-slate-400 flex items-center justify-center font-black text-slate-300 text-lg">
              {{ leaderboard[1]?.user?.username?.charAt(0)?.toUpperCase() }}
            </div>
            <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-slate-400 flex items-center justify-center text-[10px] font-black text-black">2</span>
          </div>
          <span class="text-[10px] font-black text-slate-300 mt-2 text-center truncate max-w-full">{{ leaderboard[1]?.user?.username }}</span>
          <span class="text-[9px] text-slate-400 font-bold">{{ leaderboard[1]?.points || 0 }} pts</span>
          <div class="w-full h-12 bg-slate-400/10 border-t border-slate-400/20 rounded-t-lg mt-2"></div>
        </div>

        <!-- 1st Place -->
        <div class="flex flex-col items-center">
          <Crown :size="20" class="text-yellow-400 mb-1 animate-bounce" />
          <div class="relative">
            <div class="w-14 h-14 rounded-full bg-yellow-500/20 border-2 border-yellow-400 flex items-center justify-center font-black text-yellow-300 text-xl shadow-[0_0_20px_rgba(234,179,8,0.3)]">
              {{ leaderboard[0]?.user?.username?.charAt(0)?.toUpperCase() }}
            </div>
            <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-yellow-400 flex items-center justify-center text-[11px] font-black text-black">1</span>
          </div>
          <span class="text-[10px] font-black text-yellow-300 mt-2 text-center truncate max-w-full">{{ leaderboard[0]?.user?.username }}</span>
          <span class="text-[9px] text-yellow-400 font-black">{{ leaderboard[0]?.points || 0 }} pts</span>
          <div class="w-full h-20 bg-yellow-500/10 border-t border-yellow-500/20 rounded-t-lg mt-2"></div>
        </div>

        <!-- 3rd Place -->
        <div class="flex flex-col items-center pt-8">
          <div class="relative">
            <div class="w-10 h-10 rounded-full bg-orange-500/20 border-2 border-orange-400 flex items-center justify-center font-black text-orange-300 text-base">
              {{ leaderboard[2]?.user?.username?.charAt(0)?.toUpperCase() }}
            </div>
            <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-orange-400 flex items-center justify-center text-[10px] font-black text-black">3</span>
          </div>
          <span class="text-[10px] font-black text-orange-300 mt-2 text-center truncate max-w-full">{{ leaderboard[2]?.user?.username }}</span>
          <span class="text-[9px] text-slate-400 font-bold">{{ leaderboard[2]?.points || 0 }} pts</span>
          <div class="w-full h-8 bg-orange-500/10 border-t border-orange-500/20 rounded-t-lg mt-2"></div>
        </div>
      </div>

      <!-- Full Rankings -->
      <div class="space-y-1.5 mt-4">
        <div
          v-for="(entry, index) in leaderboard"
          :key="entry.id || index"
          class="flex items-center gap-3 p-3 rounded-xl transition-all"
          :class="index === 0
            ? 'bg-yellow-500/10 border border-yellow-500/20'
            : index === 1
              ? 'bg-slate-400/5 border border-slate-400/15'
              : index === 2
                ? 'bg-orange-500/5 border border-orange-500/10'
                : 'bg-white/3 border border-white/5'"
        >
          <!-- Rank -->
          <div class="w-7 text-center shrink-0">
            <Crown v-if="index === 0" :size="16" class="text-yellow-400 mx-auto" />
            <Medal v-else-if="index === 1" :size="16" class="text-slate-400 mx-auto" />
            <Award v-else-if="index === 2" :size="16" class="text-orange-400 mx-auto" />
            <span v-else class="text-slate-500 font-black text-sm">{{ index + 1 }}</span>
          </div>

          <!-- Avatar -->
          <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm shrink-0"
            :class="index === 0 ? 'bg-yellow-500/20 text-yellow-300' : 'bg-white/10 text-slate-300'">
            {{ entry.user?.username?.charAt(0)?.toUpperCase() || '?' }}
          </div>

          <!-- Name & Status -->
          <div class="flex-1 min-w-0">
            <span class="font-black text-sm block truncate"
              :class="index === 0 ? 'text-yellow-300' : index < 3 ? 'text-white' : 'text-slate-300'">
              {{ entry.user?.username || 'Unknown' }}
            </span>
            <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">
              {{ entry.is_eliminated ? 'Eliminated' : entry.is_active ? '🔥 Active' : 'Registered' }}
            </span>
          </div>

          <!-- Wins -->
          <div class="text-center shrink-0">
            <span class="text-[9px] text-slate-400 uppercase font-bold block">Wins</span>
            <span class="text-sm font-black text-neon-cyan">{{ entry.wins || 0 }}</span>
          </div>

          <!-- Points -->
          <div class="text-center shrink-0 min-w-[50px]">
            <span class="text-[9px] text-slate-400 uppercase font-bold block">Points</span>
            <span class="font-black text-sm" :class="index === 0 ? 'text-yellow-400' : 'text-white'">{{ entry.points || 0 }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Trophy, Crown, Medal, Award, Users, Zap } from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
  tournamentId: {
    type: [Number, String],
    required: true
  },
  currencySymbol: {
    type: String,
    default: 'Rs.'
  }
})

const leaderboard = ref([])
const loading = ref(true)
const tournamentData = ref(null)
const status = ref('--')

const winnerPrize = computed(() => {
  if (!tournamentData.value) return '0'
  const t = tournamentData.value
  const pool = parseFloat(t.total_pool || (t.entry_fee * t.registered_players_count) || 0)
  const commission = pool * ((t.platform_commission_percentage || 10) / 100)
  return ((pool - commission) * (t.winner_prize_percentage / 100)).toFixed(0)
})

const fetchLeaderboard = async () => {
  try {
    loading.value = true
    const { data } = await axios.get(`/api/tournaments/${props.tournamentId}/leaderboard`)
    leaderboard.value = data.leaderboard || data || []
    tournamentData.value = data.tournament || null
    if (data.tournament?.status) {
      const statusMap = {
        open: 'Open',
        registration_closed: 'Starting',
        in_progress: '🔴 Live',
        completed: 'Done'
      }
      status.value = statusMap[data.tournament.status] || data.tournament.status
    }
  } catch (e) {
    console.error('Failed to fetch leaderboard:', e)
    leaderboard.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchLeaderboard()
})
</script>
