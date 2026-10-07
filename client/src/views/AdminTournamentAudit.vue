<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-black text-white italic tracking-wide">Tournament Audit Dashboard</h1>
        <p class="text-slate-400 text-sm mt-1">Financial audit and tournament analytics</p>
      </div>
      <select
        v-model="selectedTournamentId"
        @change="fetchAuditData"
        class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-neon-cyan"
      >
        <option value="">Select Tournament</option>
        <option v-for="tournament in tournaments" :key="tournament.id" :value="tournament.id">
          {{ tournament.title }} ({{ formatDate(tournament.starts_at) }})
        </option>
      </select>
    </div>

    <!-- Overall Dashboard Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-[#121623]/60 border border-white/5 rounded-2xl p-4">
        <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Total Tournaments</span>
        <span class="text-2xl font-black text-white">{{ dashboardStats.total_tournaments }}</span>
      </div>
      <div class="bg-[#121623]/60 border border-white/5 rounded-2xl p-4">
        <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Total Revenue</span>
        <span class="text-2xl font-black text-neon-cyan">{{ currencySymbol }}{{ dashboardStats.total_revenue }}</span>
      </div>
      <div class="bg-[#121623]/60 border border-white/5 rounded-2xl p-4">
        <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Total House Profit</span>
        <span class="text-2xl font-black text-neon-pink">{{ currencySymbol }}{{ dashboardStats.total_house_profit }}</span>
      </div>
      <div class="bg-[#121623]/60 border border-white/5 rounded-2xl p-4">
        <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Profit Margin</span>
        <span class="text-2xl font-black text-green-400">{{ dashboardStats.profit_margin_percentage?.toFixed(2) || 0 }}%</span>
      </div>
    </div>

    <!-- Tournament Audit Details -->
    <div v-if="auditData" class="space-y-6">
      <!-- Tournament Overview -->
      <div class="bg-[#121623]/40 border border-white/10 rounded-3xl p-6">
        <h2 class="font-black text-white text-lg mb-4">Tournament Overview</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Title</span>
            <span class="text-white font-bold text-sm">{{ auditData.tournament.title }}</span>
          </div>
          <div>
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Game</span>
            <span class="text-white font-bold text-sm">{{ auditData.tournament.game }}</span>
          </div>
          <div>
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Status</span>
            <span 
              class="inline-flex px-2 py-1 rounded-md text-[8px] font-black uppercase border"
              :class="statusClasses(auditData.tournament.status)"
            >
              {{ statusLabel(auditData.tournament.status) }}
            </span>
          </div>
          <div>
            <span class="text-slate-400 text-xs font-bold uppercase block mb-1">Entry Fee</span>
            <span class="text-neon-cyan font-bold text-sm">{{ currencySymbol }}{{ auditData.tournament.entry_fee }}</span>
          </div>
        </div>
      </div>

      <!-- Financial Audit -->
      <div class="bg-[#121623]/40 border border-white/10 rounded-3xl p-6">
        <h2 class="font-black text-white text-lg mb-4">Financial Audit</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
              <span class="text-slate-400 text-sm">Total Pool</span>
              <span class="text-white font-black">{{ currencySymbol }}{{ auditData.financial_audit.total_pool }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
              <span class="text-slate-400 text-sm">Platform Commission ({{ auditData.financial_audit.platform_commission.percentage }}%)</span>
              <span class="text-neon-cyan font-black">{{ currencySymbol }}{{ auditData.financial_audit.platform_commission.amount }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
              <span class="text-slate-400 text-sm">Prize Pool</span>
              <span class="text-neon-pink font-black">{{ currencySymbol }}{{ auditData.financial_audit.prize_pool }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
              <span class="text-slate-400 text-sm">Total Distributed Prizes</span>
              <span class="text-white font-black">{{ currencySymbol }}{{ auditData.financial_audit.total_distributed_prizes }}</span>
            </div>
          </div>
          <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl">
              <span class="text-slate-400 text-sm">Remaining Prize Pool</span>
              <span class="text-slate-300 font-black">{{ currencySymbol }}{{ auditData.financial_audit.remaining_prize_pool }}</span>
            </div>
            <div class="p-4 bg-neon-pink/10 border border-neon-pink/25 rounded-xl">
              <span class="text-neon-pink text-xs font-bold uppercase block mb-2">House Profit Breakdown</span>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-400">Platform Commission</span>
                  <span class="text-white">{{ currencySymbol }}{{ auditData.financial_audit.house_profit.platform_commission }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-400">Remaining Pool</span>
                  <span class="text-white">{{ currencySymbol }}{{ auditData.financial_audit.house_profit.remaining_pool }}</span>
                </div>
                <div class="border-t border-white/10 pt-2 flex justify-between">
                  <span class="text-neon-pink font-bold">Total House Profit</span>
                  <span class="text-neon-pink font-black">{{ currencySymbol }}{{ auditData.financial_audit.house_profit.total }}</span>
                </div>
              </div>
            </div>
            <div class="flex justify-between items-center p-3 bg-green-500/10 border border-green-500/25 rounded-xl">
              <span class="text-green-400 text-sm font-bold">Profit Margin</span>
              <span class="text-green-400 font-black">{{ auditData.financial_audit.profit_margin_percentage?.toFixed(2) }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Prize Breakdown -->
      <div class="bg-[#121623]/40 border border-white/10 rounded-3xl p-6">
        <h2 class="font-black text-white text-lg mb-4">Prize Breakdown Configuration</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-4 bg-yellow-500/10 border border-yellow-500/25 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
              <Crown size="16" class="text-yellow-500" />
              <span class="text-yellow-500 font-bold text-sm">Winner</span>
            </div>
            <div class="text-2xl font-black text-white">{{ auditData.prize_breakdown.winner.percentage }}%</div>
            <div class="text-neon-cyan font-bold">{{ currencySymbol }}{{ auditData.prize_breakdown.winner.amount }}</div>
          </div>
          <div class="p-4 bg-slate-400/10 border border-slate-400/25 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
              <Medal size="16" class="text-slate-400" />
              <span class="text-slate-400 font-bold text-sm">Runner-up</span>
            </div>
            <div class="text-2xl font-black text-white">{{ auditData.prize_breakdown.runner_up.percentage }}%</div>
            <div class="text-neon-cyan font-bold">{{ currencySymbol }}{{ auditData.prize_breakdown.runner_up.amount }}</div>
          </div>
          <div v-if="auditData.prize_breakdown.semifinalists" class="p-4 bg-orange-500/10 border border-orange-500/25 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
              <Award size="16" class="text-orange-500" />
              <span class="text-orange-500 font-bold text-sm">Semifinalists</span>
            </div>
            <div class="text-2xl font-black text-white">{{ auditData.prize_breakdown.semifinalists.percentage }}%</div>
            <div class="text-neon-cyan font-bold">{{ currencySymbol }}{{ auditData.prize_breakdown.semifinalists.amount }}</div>
            <div class="text-slate-400 text-xs mt-1">{{ currencySymbol }}{{ auditData.prize_breakdown.semifinalists.per_player }} each</div>
          </div>
        </div>
      </div>

      <!-- Registrations -->
      <div class="bg-[#121623]/40 border border-white/10 rounded-3xl overflow-hidden">
        <div class="p-4 border-b border-white/5">
          <h2 class="font-black text-white text-lg">Registrations ({{ auditData.registrations.length }})</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-white/5">
              <tr>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Player</th>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Entry Fee</th>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Bracket Position</th>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Final Rank</th>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Prize Won</th>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="reg in auditData.registrations" 
                :key="reg.id"
                class="border-b border-white/5 hover:bg-white/5 transition-colors"
              >
                <td class="px-4 py-3">
                  <span class="text-white font-bold text-sm">{{ reg.user_name }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-neon-cyan font-bold text-sm">{{ currencySymbol }}{{ reg.entry_fee_paid }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-white text-sm">{{ reg.bracket_position || '-' }}</span>
                  <span v-if="reg.received_bye" class="text-neon-pink text-xs ml-1">(BYE)</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-white text-sm">{{ reg.final_rank || '-' }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-neon-pink font-bold text-sm">{{ reg.prize_won > 0 ? currencySymbol + reg.prize_won : '-' }}</span>
                </td>
                <td class="px-4 py-3">
                  <span 
                    class="inline-flex px-2 py-1 rounded-md text-[8px] font-black uppercase border"
                    :class="registrationStatusClasses(reg.status)"
                  >
                    {{ reg.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Prize Distributions -->
      <div v-if="auditData.prize_distributions.length > 0" class="bg-[#121623]/40 border border-white/10 rounded-3xl overflow-hidden">
        <div class="p-4 border-b border-white/5">
          <h2 class="font-black text-white text-lg">Prize Distributions</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-white/5">
              <tr>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Rank</th>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Player</th>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Prize Amount</th>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">% of Pool</th>
                <th class="text-left px-4 py-3 text-slate-400 text-xs font-bold uppercase tracking-wider">Distributed At</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="dist in auditData.prize_distributions" 
                :key="dist.id"
                class="border-b border-white/5 hover:bg-white/5 transition-colors"
              >
                <td class="px-4 py-3">
                  <span class="text-white font-bold text-sm">{{ dist.rank_label }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-white text-sm">{{ dist.user_name }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-neon-pink font-bold text-sm">{{ currencySymbol }}{{ dist.prize_amount }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-slate-300 text-sm">{{ dist.percentage_of_pool }}%</span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-slate-400 text-sm">{{ formatDate(dist.distributed_at) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12">
      <FileText size="48" class="text-slate-600 mx-auto mb-4" />
      <p class="text-slate-400 text-sm font-semibold">Select a tournament to view audit details</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Crown, Medal, Award, FileText } from 'lucide-vue-next'
import axios from 'axios'

const tournaments = ref([])
const selectedTournamentId = ref('')
const auditData = ref(null)
const dashboardStats = ref({
  total_tournaments: 0,
  total_revenue: '0.00',
  total_house_profit: '0.00',
  profit_margin_percentage: 0
})
const currencySymbol = ref('Rs.')

const fetchTournaments = async () => {
  try {
    const { data } = await axios.get('/api/admin/tournaments/list')
    tournaments.value = data.tournaments || []
  } catch (error) {
    console.error('Failed to fetch tournaments:', error)
  }
}

const fetchDashboardStats = async () => {
  try {
    const { data } = await axios.get('/api/admin/tournaments/dashboard')
    dashboardStats.value = {
      total_tournaments: data.overview?.total_tournaments || 0,
      total_revenue: data.financials?.total_revenue || '0.00',
      total_house_profit: data.financials?.total_house_profit || '0.00',
      profit_margin_percentage: data.financials?.profit_margin_percentage || 0
    }
  } catch (error) {
    console.error('Failed to fetch dashboard stats:', error)
  }
}

const fetchAuditData = async () => {
  if (!selectedTournamentId.value) {
    auditData.value = null
    return
  }

  try {
    const { data } = await axios.get(`/api/admin/tournaments/${selectedTournamentId.value}/audit`)
    auditData.value = data
  } catch (error) {
    console.error('Failed to fetch audit data:', error)
    auditData.value = null
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

const registrationStatusClasses = (status) => {
  switch (status) {
    case 'registered':
      return 'bg-neon-cyan/15 text-neon-cyan border-neon-cyan/25'
    case 'winner':
      return 'bg-yellow-500/15 text-yellow-400 border-yellow-500/25'
    case 'runner_up':
      return 'bg-slate-400/15 text-slate-300 border-slate-400/25'
    case 'semifinalist':
      return 'bg-orange-500/15 text-orange-400 border-orange-500/25'
    case 'eliminated':
      return 'bg-red-500/15 text-red-400 border-red-500/25'
    case 'forfeited':
      return 'bg-red-500/15 text-red-400 border-red-500/25'
    default:
      return 'bg-slate-500/15 text-slate-400 border-slate-500/25'
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
  fetchDashboardStats()
  fetchSettings()
})
</script>
