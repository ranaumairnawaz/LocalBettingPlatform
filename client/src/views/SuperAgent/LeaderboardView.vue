<template>
  <div class="space-y-6">
    <!-- Toggle View -->
    <div class="flex gap-3">
      <button 
        @click="sortBy = 'earnings'"
        :class="sortBy === 'earnings' ? 'bg-neon-cyan/20 text-neon-cyan border-neon-cyan/40 shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'bg-white/5 text-slate-400 border-white/10'"
        class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider border transition-all cursor-pointer"
      >
        Top by Earnings
      </button>
      <button 
        @click="sortBy = 'team_size'"
        :class="sortBy === 'team_size' ? 'bg-neon-cyan/20 text-neon-cyan border-neon-cyan/40 shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'bg-white/5 text-slate-400 border-white/10'"
        class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider border transition-all cursor-pointer"
      >
        Top by Team Size
      </button>
    </div>

    <!-- Leaderboard -->
    <div class="space-y-3">
      <div v-for="(agent, index) in leaderboard" :key="agent.id" 
        :class="agent.is_self ? 'border-neon-gold bg-gradient-to-r from-slate-800/80 to-neon-gold/5 shadow-[0_0_20px_rgba(251,191,36,0.08)]' : 'border-white/10 bg-slate-800/60'"
        class="rounded-[20px] border backdrop-blur-md p-4 hover:border-white/20 transition-all">
        <div class="flex items-center justify-between">
          <!-- Rank & Avatar -->
          <div class="flex items-center gap-4 flex-1">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center font-black text-lg" :style="{ backgroundColor: getRankColor(index) + '20', color: getRankColor(index) }">
              {{ index + 1 }}
            </div>
            <img :src="`https://ui-avatars.com/api/?name=${agent.team_name || 'SA'}&background=121623&color=00f2fe`" 
              class="w-10 h-10 rounded-lg border border-white/10">
            <div>
              <p class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                <span>{{ agent.team_name || 'Unnamed Team' }}</span>
                <span v-if="agent.is_self" class="text-[8px] bg-neon-gold/15 text-neon-gold border border-neon-gold/25 font-black uppercase px-2 py-0.5 rounded-md">YOU</span>
              </p>
              <div class="flex gap-2 mt-1">
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border capitalize"
                  :class="getRankBadgeClass(agent.rank)">
                  {{ agent.rank }}
                </span>
              </div>
            </div>
          </div>

          <!-- Stats -->
          <div class="text-right">
            <div v-if="sortBy === 'earnings'" class="mb-2">
              <p class="text-xs text-slate-400 font-semibold mb-0.5">Total Earnings</p>
              <p class="text-lg font-black text-neon-gold font-mono">Rs. {{ formatCurrency(agent.earnings) }}</p>
            </div>
            <div v-else class="mb-2">
              <p class="text-xs text-slate-400 font-semibold mb-0.5">Team Size</p>
              <p class="text-lg font-black text-neon-cyan">{{ agent.team_size }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!leaderboard.length" class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-12 text-center">
        <p class="text-slate-400">No Super Agents found</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

const leaderboard = ref([]);
const sortBy = ref('earnings');

const formatCurrency = (amount) => new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);

const getRankColor = (index) => {
  const colors = ['#fbbf24', '#a8dadc', '#457b9d', '#06b6d4', '#00f2fe'];
  return colors[Math.min(index, colors.length - 1)];
};

const getRankBadgeClass = (rank) => {
  const classes = {
    'bronze': 'bg-orange-500/10 text-orange-400 border-orange-500/20',
    'silver': 'bg-gray-500/10 text-gray-400 border-gray-500/20',
    'gold': 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
    'diamond': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    'platinum': 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20'
  };
  return classes[rank] || 'bg-white/5 text-slate-400 border-white/10';
};

const fetchLeaderboard = async () => {
  try {
    const { data } = await axios.get('/api/super-agent/leaderboard', {
      params: { sort_by: sortBy.value }
    });
    leaderboard.value = data.data || [];
  } catch (error) {
    console.error('Error fetching leaderboard:', error);
  }
};

watch(sortBy, () => {
  fetchLeaderboard();
});

onMounted(() => {
  fetchLeaderboard();
});
</script>
