<template>
  <div class="space-y-6">
    <!-- Header Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Team Size</p>
        <p class="text-3xl font-black text-neon-cyan">{{ pagination?.total || 0 }}</p>
        <p class="text-xs text-slate-500">Direct downline level-1 recruits</p>
      </div>

      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Active Recruits</p>
        <p class="text-3xl font-black text-emerald-400">
          {{ activeCount }}
        </p>
        <p class="text-xs text-slate-500">Active in the last 7 days</p>
      </div>

      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Dormant Recruits</p>
        <p class="text-3xl font-black text-rose-400">
          {{ dormantCount }}
        </p>
        <p class="text-xs text-slate-500">No activity for 30+ days</p>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-5 flex flex-col md:flex-row gap-4 items-center justify-between">
      <!-- Activity Filter Buttons -->
      <div class="flex gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
        <button 
          v-for="filter in filters" 
          :key="filter.value"
          @click="activeFilter = filter.value"
          :class="activeFilter === filter.value ? 'bg-neon-cyan/20 text-neon-cyan border-neon-cyan/40 shadow-[0_0_10px_rgba(0,242,254,0.1)]' : 'bg-white/5 text-slate-400 hover:text-slate-200 border-transparent'"
          class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider border transition-all cursor-pointer whitespace-nowrap"
        >
          {{ filter.label }}
        </button>
      </div>

      <!-- Search Bar -->
      <div class="relative w-full md:w-72">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
          <Search class="w-4 h-4" />
        </span>
        <input 
          v-model="searchQuery"
          type="text" 
          placeholder="Search team by username..." 
          class="w-full bg-slate-700/50 border border-white/10 rounded-xl py-2 pl-9 pr-4 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-neon-cyan/50 focus:ring-1 focus:ring-neon-cyan/20"
        />
      </div>
    </div>

    <!-- Recruits Grid -->
    <div v-if="recruits.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="member in recruits" 
        :key="member.id"
        class="bg-slate-800/60 rounded-[24px] border border-white/10 backdrop-blur-md p-6 hover:border-white/20 transition-all flex flex-col justify-between"
      >
        <div class="space-y-4">
          <!-- Avatar & Status -->
          <div class="flex items-center gap-4">
            <div v-if="member.avatar" class="w-12 h-12 rounded-2xl border border-neon-cyan/30 overflow-hidden shrink-0">
              <img :src="getAvatarUrl(member.avatar)" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-neon-pink/20 to-neon-cyan/20 border border-neon-cyan/30 flex items-center justify-center font-black text-lg text-neon-cyan shrink-0">
              {{ member.name?.charAt(0).toUpperCase() }}
            </div>

            <div class="flex-1 min-w-0">
              <h4 class="text-sm font-black text-white uppercase tracking-wider truncate">{{ member.name }}</h4>
              <p class="text-[10px] text-slate-500 font-mono mt-0.5">Joined: {{ member.join_date }}</p>
            </div>

            <span 
              class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border shrink-0"
              :class="member.activity_badge === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                       member.activity_badge === 'new' ? 'bg-neon-pink/15 text-neon-pink border-neon-pink/20' :
                       'bg-slate-500/10 text-slate-400 border-white/10'"
            >
              {{ member.activity_badge }}
            </span>
          </div>

          <!-- Metrics Bar (Bet Volume Indicator) -->
          <div class="space-y-1">
            <div class="flex justify-between text-[10px] font-semibold text-slate-400">
              <span>Bet Volume</span>
              <span class="font-black text-white font-mono">Rs. {{ formatCurrency(member.bet_volume) }}</span>
            </div>
            <div class="w-full bg-slate-700/50 rounded-full h-1.5 overflow-hidden">
              <div 
                class="bg-gradient-to-r from-neon-cyan to-neon-pink h-1.5 rounded-full transition-all" 
                :style="{ width: getBetVolumePercentage(member.bet_volume) + '%' }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Deposit Stats -->
        <div class="mt-4 pt-4 border-t border-white/5 flex items-center justify-between text-xs">
          <span class="text-slate-400 font-semibold">Total Deposits</span>
          <span class="font-black text-emerald-400 font-mono">Rs. {{ formatCurrency(member.total_deposits) }}</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-16 text-center">
      <UserCheck class="w-12 h-12 text-slate-500 mx-auto mb-4 stroke-[1.5]" />
      <p class="text-slate-400 text-sm">No team members match the selected filters</p>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2 pt-4">
      <button 
        v-if="pagination.current_page > 1"
        @click="currentPage--"
        class="px-4 py-2 bg-white/5 hover:bg-white/10 text-slate-300 rounded-lg text-xs font-black uppercase transition-all cursor-pointer"
      >
        Previous
      </button>
      <span class="px-4 py-2 text-xs text-slate-400">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <button 
        v-if="pagination.current_page < pagination.last_page"
        @click="currentPage++"
        class="px-4 py-2 bg-white/5 hover:bg-white/10 text-slate-300 rounded-lg text-xs font-black uppercase transition-all cursor-pointer"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { Search, UserCheck } from 'lucide-vue-next';
import axios from 'axios';

const recruits = ref([]);
const pagination = ref(null);
const currentPage = ref(1);
const searchQuery = ref('');
const activeFilter = ref('all');

// Stats Counters (Calculated locally from simple stats endpoint or dashboard)
const activeCount = ref(0);
const dormantCount = ref(0);

const filters = [
  { label: 'All Recruits', value: 'all' },
  { label: 'Active (7d)', value: 'active' },
  { label: 'Dormant (30d+)', value: 'dormant' },
  { label: 'New (7d)', value: 'new' }
];

const formatCurrency = (amount) => new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);

const getAvatarUrl = (path) => {
  if (!path) return '';
  return path.startsWith('http') ? path : `http://localhost:8000/storage/${path}`;
};

const getBetVolumePercentage = (volume) => {
  // Base percentage on Rs. 100K target bet volume per player for nice scale
  const maxScale = 100000;
  return Math.min(100, (volume / maxScale) * 100);
};

const fetchTeam = async () => {
  try {
    const params = { page: currentPage.value };
    if (activeFilter.value !== 'all') {
      params.activity = activeFilter.value;
    }
    const { data } = await axios.get('/api/super-agent/team', { params });
    
    let list = data.data || [];
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase().trim();
      list = list.filter(m => m.name?.toLowerCase().includes(q));
    }
    
    recruits.value = list;
    pagination.value = data.pagination || null;
  } catch (error) {
    console.error('Error fetching team recruits:', error);
  }
};

const fetchSummaryStats = async () => {
  try {
    const { data } = await axios.get('/api/super-agent/team', { params: { per_page: 100 } });
    const list = data.data || [];
    
    activeCount.value = list.filter(m => m.activity_badge === 'active').length;
    dormantCount.value = list.filter(m => m.activity_badge === 'dormant').length;
  } catch (error) {
    console.error('Error fetching stats summaries:', error);
  }
};

watch([activeFilter, currentPage, searchQuery], () => {
  fetchTeam();
});

onMounted(() => {
  fetchTeam();
  fetchSummaryStats();
});
</script>
