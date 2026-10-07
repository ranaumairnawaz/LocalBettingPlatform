<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-black text-white italic tracking-wide">Tournament Scheduler</h1>
        <p class="text-slate-400 text-sm mt-1">Create and manage knockout tournaments</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="px-6 py-3 bg-gradient-to-r from-neon-cyan to-neon-pink text-white font-black uppercase tracking-wider rounded-xl hover:opacity-90 transition-opacity"
      >
        Create Tournament
      </button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-[#121623]/60 border border-white/5 rounded-2xl p-4">
        <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Total Tournaments</span>
        <span class="text-2xl font-black text-white">{{ stats.total_tournaments }}</span>
      </div>
      <div class="bg-[#121623]/60 border border-white/5 rounded-2xl p-4">
        <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Active</span>
        <span class="text-2xl font-black text-neon-cyan">{{ stats.active_tournaments }}</span>
      </div>
      <div class="bg-[#121623]/60 border border-white/5 rounded-2xl p-4">
        <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Completed</span>
        <span class="text-2xl font-black text-green-400">{{ stats.completed_tournaments }}</span>
      </div>
      <div class="bg-[#121623]/60 border border-white/5 rounded-2xl p-4">
        <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Total Revenue</span>
        <span class="text-2xl font-black text-neon-pink">{{ currencySymbol }}{{ stats.total_revenue }}</span>
      </div>
    </div>

    <!-- Tournaments List -->
    <div class="bg-[#121623]/40 border border-white/10 rounded-3xl overflow-hidden">
      <div class="p-4 border-b border-white/5 flex items-center justify-between">
        <h2 class="font-black text-white text-lg">All Tournaments</h2>
        <div class="flex gap-2">
          <select
            v-model="filterStatus"
            class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-neon-cyan"
          >
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="open">Open</option>
            <option value="registration_closed">Registration Closed</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-neon-cyan"></div>
      </div>

      <div v-else-if="tournaments.length === 0" class="text-center py-12">
        <Trophy size="48" class="text-slate-600 mx-auto mb-4" />
        <p class="text-slate-400 text-sm font-semibold">No tournaments found</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-white/5">
            <tr>
              <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Tournament</th>
              <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Game</th>
              <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Entry Fee</th>
              <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Players</th>
              <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Pool</th>
              <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Status</th>
              <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="tournament in filteredTournaments" 
              :key="tournament.id"
              class="border-b border-white/5 hover:bg-white/5 transition-colors"
            >
              <td class="px-4 py-4">
                <div>
                  <span class="text-white font-bold text-sm">{{ tournament.title }}</span>
                  <span class="text-slate-400 text-xs block mt-1">{{ formatDate(tournament.starts_at) }}</span>
                </div>
              </td>
              <td class="px-4 py-4">
                <span class="text-slate-300 text-sm">{{ tournament.game?.title }}</span>
              </td>
              <td class="px-4 py-4">
                <span class="text-neon-cyan font-bold text-sm">{{ currencySymbol }}{{ tournament.entry_fee }}</span>
              </td>
              <td class="px-4 py-4">
                <span class="text-white font-bold text-sm">{{ tournament.registered_players_count }}/{{ tournament.max_players }}</span>
              </td>
              <td class="px-4 py-4">
                <span class="text-neon-pink font-bold text-sm">{{ currencySymbol }}{{ tournament.total_pool }}</span>
              </td>
              <td class="px-4 py-4">
                <span 
                  class="inline-flex px-2 py-1 rounded-md text-[8px] font-black uppercase border"
                  :class="statusClasses(tournament.status)"
                >
                  {{ statusLabel(tournament.status) }}
                </span>
              </td>
              <td class="px-4 py-4">
                <div class="flex gap-2">
                  <button
                    @click="viewTournament(tournament)"
                    class="p-2 rounded-lg bg-white/5 hover:bg-neon-cyan/20 transition-colors"
                    title="View Details"
                  >
                    <Eye size="16" class="text-slate-400 hover:text-neon-cyan" />
                  </button>
                  <button
                    v-if="tournament.status === 'open'"
                    @click="generateBracket(tournament)"
                    class="p-2 rounded-lg bg-white/5 hover:bg-neon-pink/20 transition-colors"
                    title="Generate Bracket"
                  >
                    <GitBranch size="16" class="text-slate-400 hover:text-neon-pink" />
                  </button>
                  <button
                    v-if="['draft', 'open', 'registration_closed'].includes(tournament.status)"
                    @click="cancelTournament(tournament)"
                    class="p-2 rounded-lg bg-white/5 hover:bg-red-500/20 transition-colors"
                    title="Cancel Tournament"
                  >
                    <X size="16" class="text-slate-400 hover:text-red-500" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Tournament Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showCreateModal = false"></div>
      <div class="relative bg-[#121623] border border-white/10 rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
          <h2 class="text-xl font-black text-white italic tracking-wide">Create Tournament</h2>
          <button @click="showCreateModal = false" class="p-2 rounded-full bg-white/5 hover:bg-white/10">
            <X size="20" class="text-white" />
          </button>
        </div>
        <form @submit.prevent="createTournament" class="p-6 space-y-4">
          <div>
            <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Game</label>
            <select
              v-model="form.game_id"
              required
              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
            >
              <option value="">Select a game</option>
              <option v-for="game in games" :key="game.id" :value="game.id">{{ game.title }}</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Title</label>
            <input
              v-model="form.title"
              required
              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
              placeholder="Tournament title"
            />
          </div>
          <div>
            <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Description</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
              placeholder="Tournament description"
            />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Entry Fee</label>
              <input
                v-model="form.entry_fee"
                type="number"
                step="0.01"
                required
                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
                placeholder="0.00"
              />
            </div>
            <div>
              <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Max Players</label>
              <input
                v-model="form.max_players"
                type="number"
                required
                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
                placeholder="64"
              />
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Registration Opens</label>
              <input
                v-model="form.registration_opens_at"
                type="datetime-local"
                required
                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
              />
            </div>
            <div>
              <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Registration Closes</label>
              <input
                v-model="form.registration_closes_at"
                type="datetime-local"
                required
                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
              />
            </div>
            <div>
              <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Starts At</label>
              <input
                v-model="form.starts_at"
                type="datetime-local"
                required
                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
              />
            </div>
          </div>
          <div>
            <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Prize Configuration</label>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="text-slate-500 text-xs block mb-1">Winner %</label>
                <input
                  v-model="form.winner_prize_percentage"
                  type="number"
                  step="0.01"
                  required
                  class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
                  placeholder="50"
                />
              </div>
              <div>
                <label class="text-slate-500 text-xs block mb-1">Runner-up %</label>
                <input
                  v-model="form.runner_up_prize_percentage"
                  type="number"
                  step="0.01"
                  required
                  class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
                  placeholder="30"
                />
              </div>
              <div>
                <label class="text-slate-500 text-xs block mb-1">Platform Commission %</label>
                <input
                  v-model="form.platform_commission_percentage"
                  type="number"
                  step="0.01"
                  required
                  class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
                  placeholder="10"
                />
              </div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <input
              v-model="form.include_semifinalist_prizes"
              type="checkbox"
              id="semifinalist"
              class="w-4 h-4 rounded bg-white/5 border-white/10"
            />
            <label for="semifinalist" class="text-slate-300 text-sm">Include semifinalist prizes</label>
          </div>
          <div v-if="form.include_semifinalist_prizes">
            <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Semifinalist Prize %</label>
            <input
              v-model="form.semifinalist_prize_percentage"
              type="number"
              step="0.01"
              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
              placeholder="20"
            />
          </div>
          <button
            type="submit"
            :disabled="loading"
            class="w-full py-4 bg-gradient-to-r from-neon-cyan to-neon-pink text-white font-black uppercase tracking-wider rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50"
          >
            {{ loading ? 'Creating...' : 'Create Tournament' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Trophy, Eye, GitBranch, X } from 'lucide-vue-next'
import axios from 'axios'

const tournaments = ref([])
const games = ref([])
const stats = ref({
  total_tournaments: 0,
  active_tournaments: 0,
  completed_tournaments: 0,
  total_revenue: '0.00'
})
const loading = ref(false)
const showCreateModal = ref(false)
const filterStatus = ref('')
const currencySymbol = ref('Rs.')

const form = ref({
  game_id: '',
  title: '',
  description: '',
  entry_fee: '',
  min_players: 4,
  max_players: 64,
  registration_opens_at: '',
  registration_closes_at: '',
  starts_at: '',
  winner_prize_percentage: 50,
  runner_up_prize_percentage: 30,
  include_semifinalist_prizes: false,
  semifinalist_prize_percentage: 20,
  platform_commission_percentage: 10
})

const filteredTournaments = computed(() => {
  if (!filterStatus.value) return tournaments.value
  return tournaments.value.filter(t => t.status === filterStatus.value)
})

const fetchTournaments = async () => {
  try {
    loading.value = true
    const { data } = await axios.get('/api/admin/tournaments/list')
    tournaments.value = data.tournaments || []
  } catch (error) {
    console.error('Failed to fetch tournaments:', error)
  } finally {
    loading.value = false
  }
}

const fetchGames = async () => {
  try {
    const { data } = await axios.get('/api/games')
    games.value = data || []
  } catch (error) {
    console.error('Failed to fetch games:', error)
  }
}

const fetchStats = async () => {
  try {
    const { data } = await axios.get('/api/admin/tournaments/dashboard')
    stats.value = data.overview || {}
    stats.value.total_revenue = data.financials?.total_revenue || '0.00'
  } catch (error) {
    console.error('Failed to fetch stats:', error)
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

const createTournament = async () => {
  try {
    loading.value = true
    await axios.post('/api/admin/tournaments', form.value)
    showCreateModal.value = false
    form.value = {
      game_id: '',
      title: '',
      description: '',
      entry_fee: '',
      min_players: 4,
      max_players: 64,
      registration_opens_at: '',
      registration_closes_at: '',
      starts_at: '',
      winner_prize_percentage: 50,
      runner_up_prize_percentage: 30,
      include_semifinalist_prizes: false,
      semifinalist_prize_percentage: 20,
      platform_commission_percentage: 10
    }
    fetchTournaments()
    fetchStats()
  } catch (error) {
    console.error('Failed to create tournament:', error)
    alert(error.response?.data?.error || 'Failed to create tournament')
  } finally {
    loading.value = false
  }
}

const generateBracket = async (tournament) => {
  if (!confirm(`Generate bracket for "${tournament.title}"? This cannot be undone.`)) return
  
  try {
    loading.value = true
    await axios.post(`/api/admin/tournaments/${tournament.id}/generate-bracket`)
    fetchTournaments()
    alert('Bracket generated successfully!')
  } catch (error) {
    console.error('Failed to generate bracket:', error)
    alert(error.response?.data?.error || 'Failed to generate bracket')
  } finally {
    loading.value = false
  }
}

const cancelTournament = async (tournament) => {
  if (!confirm(`Cancel "${tournament.title}"? All entry fees will be refunded.`)) return
  
  try {
    loading.value = true
    await axios.post(`/api/admin/tournaments/${tournament.id}/cancel`)
    fetchTournaments()
    fetchStats()
    alert('Tournament cancelled and refunds processed!')
  } catch (error) {
    console.error('Failed to cancel tournament:', error)
    alert(error.response?.data?.error || 'Failed to cancel tournament')
  } finally {
    loading.value = false
  }
}

const viewTournament = (tournament) => {
  // Navigate to tournament details view
  console.log('View tournament:', tournament)
}

const statusClasses = (status) => {
  switch (status) {
    case 'open':
      return 'bg-neon-cyan/15 text-neon-cyan border-neon-cyan/25'
    case 'registration_closed':
      return 'bg-neon-pink/15 text-neon-pink border-neon-pink/25'
    case 'in_progress':
      return 'bg-green-500/15 text-green-400 border-green-500/25'
    case 'completed':
      return 'bg-slate-500/15 text-slate-400 border-slate-500/25'
    case 'cancelled':
      return 'bg-red-500/15 text-red-400 border-red-500/25'
    default:
      return 'bg-slate-500/15 text-slate-400 border-slate-500/25'
  }
}

const statusLabel = (status) => {
  switch (status) {
    case 'open':
      return 'Open'
    case 'registration_closed':
      return 'Starting Soon'
    case 'in_progress':
      return 'Live'
    case 'completed':
      return 'Completed'
    case 'cancelled':
      return 'Cancelled'
    default:
      return 'Draft'
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchTournaments()
  fetchGames()
  fetchStats()
  fetchSettings()
})
</script>
