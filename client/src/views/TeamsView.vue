<template>
  <div class="min-h-screen text-slate-100 flex flex-col gap-6 pb-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h2 class="text-xs uppercase tracking-widest text-slate-400 font-bold">Referral Program</h2>
        <h1 class="text-2xl font-black text-white italic tracking-wide uppercase">My Team & Network</h1>
      </div>
      
      <!-- User Referral Active Badge -->
      <div 
        :class="teamData?.has_deposited ? 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.15)]' : 'bg-rose-500/10 border-rose-500/25 text-rose-400 shadow-[0_0_15px_rgba(244,63,94,0.15)]'"
        class="border px-4 py-2.5 rounded-2xl flex items-center gap-2.5 font-bold uppercase tracking-wider text-xs"
      >
        <span :class="teamData?.has_deposited ? 'bg-emerald-400' : 'bg-rose-400'" class="w-2 h-2 rounded-full animate-pulse"></span>
        <span>Account Status: {{ teamData?.has_deposited ? 'ACTIVE' : 'INACTIVE' }}</span>
      </div>
    </div>

    <!-- Active Deposit Rule Alert -->
    <div v-if="teamData && !teamData.has_deposited" class="bg-neon-pink/5 border border-neon-pink/15 p-4 rounded-3xl flex items-start gap-3.5 relative overflow-hidden">
      <AlertTriangle size="20" class="text-neon-pink shrink-0 mt-0.5" />
      <div class="space-y-1">
        <h4 class="text-xs font-black text-white uppercase tracking-wider">Commission Inactive Warning</h4>
        <p class="text-[10px] text-slate-400 font-semibold leading-relaxed">
          You currently have a completed deposit count of <strong class="text-neon-pink font-mono">0</strong>. Under the active referrer rule, you will only receive Level 1-5 commissions once you perform <strong class="text-white">at least one deposit</strong> on the platform.
        </p>
      </div>
    </div>

    <div v-else-if="teamData" class="bg-emerald-500/5 border border-emerald-500/15 p-4 rounded-3xl flex items-start gap-3.5 relative overflow-hidden">
      <CheckCircle size="20" class="text-emerald-400 shrink-0 mt-0.5" />
      <div class="space-y-1">
        <h4 class="text-xs font-black text-white uppercase tracking-wider">Commissions Active</h4>
        <p class="text-[10px] text-slate-400 font-semibold leading-relaxed">
          Your account is fully qualified to receive Level 1 to 5 commission credits because you have completed at least one lifetime deposit! Keep growing your network!
        </p>
      </div>
    </div>

    <!-- Main Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- Referral Link Card -->
      <div class="glow-card-cyan p-6 flex flex-col justify-between gap-4 md:col-span-2">
        <div>
          <h3 class="font-black text-sm text-white italic uppercase tracking-wider mb-1">Invite Players</h3>
          <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Share your code or link to build your downline</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <!-- Link -->
          <div class="flex-1 bg-black/40 border border-white/5 rounded-2xl py-3 px-4 flex items-center justify-between text-xs font-semibold text-slate-300 font-mono truncate select-all">
            {{ referralLink }}
          </div>
          <!-- Copy Button -->
          <button 
            @click="copyLink" 
            class="bg-neon-cyan text-black font-black italic uppercase tracking-widest text-xs px-6 py-3.5 rounded-2xl active:scale-95 transition-all shadow-[0_0_15px_rgba(0,242,254,0.25)] flex items-center justify-center gap-1.5 cursor-pointer shrink-0"
          >
            <Copy size="14" />
            Copy Link
          </button>
        </div>
      </div>

      <!-- Commission Earned Card -->
      <div class="glow-card-cyan p-6 flex items-center justify-between">
        <div class="flex flex-col">
          <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold">Commission Earned</span>
          <span class="text-3xl font-black text-neon-cyan font-mono italic mt-2">Rs. {{ teamData?.total_commission || '0.00' }}</span>
        </div>
        <div class="p-4.5 bg-neon-cyan/15 rounded-2xl border border-neon-cyan/20">
          <Coins size="24" class="text-neon-cyan shrink-0" />
        </div>
      </div>

    </div>

    <!-- Grouped Downline Tree Layout -->
    <div class="glow-card-cyan p-6 flex flex-col gap-6">
      
      <!-- Tab Controls & Rates -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/5 pb-3">
        <!-- Tabs Header -->
        <div class="flex flex-wrap gap-2">
          <button 
            v-for="lvl in [1, 2, 3, 4, 5]" 
            :key="lvl"
            @click="activeLevel = lvl"
            class="text-xs font-black italic tracking-wider px-4 py-2.5 rounded-xl border transition-all cursor-pointer"
            :class="activeLevel === lvl ? 'bg-neon-cyan/15 border-neon-cyan text-neon-cyan shadow-[0_0_10px_rgba(0,242,254,0.1)]' : 'bg-white/5 border-transparent text-slate-400 hover:text-slate-200'"
          >
            LEVEL {{ lvl }} ({{ getLevelCount(lvl) }})
          </button>
        </div>

        <!-- Commission Rate tag -->
        <span class="text-[10px] font-black uppercase tracking-widest text-neon-cyan bg-neon-cyan/5 border border-neon-cyan/10 px-3.5 py-2 rounded-xl flex items-center gap-1.5 font-mono">
          <Award size="12" />
          COMMISSION RATE: {{ teamData?.percentages['level' + activeLevel] || '0' }}%
        </span>
      </div>

      <!-- Tab Content: Referrals list -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="border-b border-white/5 text-slate-400 font-bold uppercase tracking-widest text-[9px]">
              <th class="py-3 px-4">Username</th>
              <th class="py-3 px-4">Email</th>
              <th class="py-3 px-4">Registered Date</th>
            </tr>
          </thead>
          <tbody v-if="activeLevelMembers.length > 0" class="divide-y divide-white/5 font-semibold">
            <tr v-for="(member, idx) in activeLevelMembers" :key="idx" class="hover:bg-white/5 transition-colors">
              <td class="py-3.5 px-4 font-black italic text-slate-200 uppercase tracking-wide">
                {{ member.username }}
              </td>
              <td class="py-3.5 px-4 font-mono text-slate-300">
                {{ member.email }}
              </td>
              <td class="py-3.5 px-4 text-[10px] text-slate-400 font-mono">
                {{ formatDate(member.created_at) }}
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr>
              <td colspan="3" class="py-12 text-center text-slate-500 italic text-xs flex flex-col items-center justify-center gap-2">
                <HelpCircle size="28" class="stroke-[1.5]" />
                <span>No team members registered at Level {{ activeLevel }} yet.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { AlertTriangle, CheckCircle, Copy, Coins, Award, HelpCircle } from 'lucide-vue-next'
import { useToastStore } from '../store/toast'
import axios from 'axios'

const toast = useToastStore()

const teamData = ref(null)
const activeLevel = ref(1)

const referralLink = computed(() => {
  const code = teamData.value?.referral_code || ''
  return `${window.location.origin}/register?ref=${code}`
})

const activeLevelMembers = computed(() => {
  if (!teamData.value) return []
  const key = 'level' + activeLevel.value
  return teamData.value.team[key] || []
})

const getLevelCount = (lvl) => {
  if (!teamData.value) return 0
  const key = 'level' + lvl
  return teamData.value.team[key]?.length || 0
}

const fetchTeamData = async () => {
  try {
    const { data } = await axios.get('/api/user/team')
    teamData.value = data
  } catch (e) {
    console.error('Failed to load referral team details', e)
  }
}

const copyLink = () => {
  navigator.clipboard.writeText(referralLink.value)
  toast.show('Referral link copied successfully!', 'success')
}

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  const date = new Date(dateStr)
  return date.toLocaleDateString(undefined, { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  }) + ' ' + date.toLocaleTimeString(undefined, {
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchTeamData()
})
</script>

<style scoped>
.glow-card-cyan {
  background: rgba(18, 22, 35, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  backdrop-filter: blur(12px);
  box-shadow: 0 0 30px rgba(0, 242, 254, 0.02);
  transition: all 0.3s ease;
}
.glow-card-cyan:hover {
  border-color: rgba(0, 242, 254, 0.2);
  box-shadow: 0 0 40px rgba(0, 242, 254, 0.05);
}
</style>
