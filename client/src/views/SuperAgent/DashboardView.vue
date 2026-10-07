<template>
  <div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Your Rank</p>
        <div class="flex items-center justify-between">
          <div>
            <p class="text-3xl font-black italic tracking-wider capitalize" :style="{ color: rankColor }">
              {{ user?.rank || 'Bronze' }}
            </p>
            <p class="text-xs text-slate-400 mt-1">Level {{ rankLevel }} • Progress: {{ progressPercent }}%</p>
          </div>
          <div class="w-12 h-12 rounded-xl flex items-center justify-center border animate-pulse" :style="{ backgroundColor: rankColor + '20', borderColor: rankColor + '40' }">
            <Award class="w-6 h-6" :style="{ color: rankColor }" />
          </div>
        </div>
      </div>

      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Team Members</p>
        <p class="text-3xl font-black text-neon-cyan">{{ teamSize }}</p>
        <p class="text-xs text-slate-400 mt-2">Active recruits</p>
      </div>

      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Earnings</p>
        <p class="text-2xl font-black text-neon-gold font-mono">Rs. {{ formatCurrency(totalEarnings) }}</p>
      </div>

      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">This Month</p>
        <p class="text-2xl font-black text-emerald-400 font-mono">Rs. {{ formatCurrency(thisMonthEarnings) }}</p>
        <p class="text-xs text-slate-400 mt-2">Commission only</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <h2 class="text-lg font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
          <span class="bg-neon-pink/20 w-1.5 h-6 rounded-full"></span>
          Team Details
        </h2>

        <div class="space-y-4">
          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Team Name</label>
            <p class="text-sm text-white font-semibold bg-slate-700/50 rounded-lg p-3">{{ user?.team_name || 'Not Set' }}</p>
          </div>

          <div>
            <label class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2 block">Referral Link</label>
            <div class="flex gap-2">
              <input 
                :value="fullReferralLink" 
                readonly
                class="flex-1 text-xs bg-slate-700/50 border border-white/10 rounded-lg p-3 text-white font-mono"
              >
              <button 
                @click="copyReferralLink"
                class="bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all border border-neon-cyan/20 cursor-pointer"
              >
                Copy
              </button>
            </div>
          </div>

          <button 
            @click="showEditForm = !showEditForm"
            class="w-full bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all border border-neon-cyan/20 cursor-pointer"
          >
            Edit Team Name
          </button>

          <div v-if="showEditForm" class="pt-4 border-t border-white/10">
            <input 
              v-model="newTeamName"
              placeholder="New team name..."
              class="w-full text-xs bg-slate-700/50 border border-white/10 rounded-lg p-3 text-white mb-2 focus:outline-none focus:ring-1 focus:ring-neon-cyan"
            >
            <div class="flex gap-2">
              <button 
                @click="updateTeamName"
                :disabled="updating"
                class="flex-1 bg-emerald-500/20 hover:bg-emerald-500/40 text-emerald-400 px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all border border-emerald-500/20 disabled:opacity-50 cursor-pointer"
              >
                Save
              </button>
              <button 
                @click="showEditForm = false"
                class="flex-1 bg-white/5 hover:bg-white/10 text-slate-400 px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all cursor-pointer"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="lg:col-span-2 bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <h2 class="text-lg font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
          <span class="bg-neon-gold/20 w-1.5 h-6 rounded-full"></span>
          Share Your Code
        </h2>

        <div class="bg-slate-700/50 rounded-xl p-6 mb-4 text-center">
          <p class="text-xs text-slate-400 uppercase tracking-wider mb-2">Your Referral Code</p>
          <p class="text-2xl font-black text-neon-cyan font-mono mb-4">{{ referralCode }}</p>
          <p class="text-sm text-slate-300 mb-4">Share this link or code with your recruits to earn commissions</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <button 
            @click="shareVia('whatsapp')"
            class="bg-emerald-500/20 hover:bg-emerald-500/40 text-emerald-400 px-4 py-3 rounded-lg text-xs font-black uppercase tracking-wider transition-all border border-emerald-500/20 flex items-center justify-center gap-2 cursor-pointer"
          >
            <MessageCircle class="w-4 h-4" />
            WhatsApp
          </button>
          <button 
            @click="shareVia('copy')"
            class="bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-4 py-3 rounded-lg text-xs font-black uppercase tracking-wider transition-all border border-neon-cyan/20 flex items-center justify-center gap-2 cursor-pointer"
          >
            <Copy class="w-4 h-4" />
            Copy Link
          </button>
        </div>
      </div>
    </div>

    <div v-if="recentEarnings.length" class="bg-gradient-to-r from-neon-cyan/10 to-neon-pink/10 rounded-[20px] border border-neon-cyan/20 backdrop-blur-md p-6">
      <h2 class="text-lg font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
        <span class="bg-neon-cyan/20 w-1.5 h-6 rounded-full animate-pulse"></span>
        Recent Earnings
      </h2>
      <div class="grid gap-3 max-h-64 overflow-y-auto">
        <div v-for="earning in recentEarnings" :key="earning.id" class="flex items-center justify-between p-3 bg-white/5 rounded-lg border border-white/5">
          <div>
            <p class="text-xs text-slate-300 font-semibold">{{ titleCase(earning.commission_type) }}</p>
            <p class="text-[10px] text-slate-400">{{ timeAgo(earning.created_at) }}</p>
          </div>
          <p class="text-sm font-black text-neon-cyan font-mono">+Rs. {{ formatCurrency(earning.commission_amount) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Award, MessageCircle, Copy } from 'lucide-vue-next';
import axios from 'axios';

const user = ref(null);
const stats = ref(null);
const referralCode = ref('');
const recentEarnings = ref([]);

const showEditForm = ref(false);
const newTeamName = ref('');
const updating = ref(false);

const rankColor = computed(() => {
  const rankColors = {
    'bronze': '#fb923c',
    'silver': '#9ca3af',
    'gold': '#fbbf24',
    'diamond': '#3b82f6'
  };
  return rankColors[user.value?.rank] || '#00f2fe';
});

const rankLevel = computed(() => {
  const ranks = { 'bronze': 1, 'silver': 2, 'gold': 3, 'diamond': 4 };
  return ranks[user.value?.rank] || 1;
});

const teamSize = computed(() => stats.value?.team_size || 0);
const totalEarnings = computed(() => stats.value?.total_earnings || 0);
const thisMonthEarnings = computed(() => stats.value?.this_month_earnings || 0);
const progressPercent = computed(() => stats.value?.rank_progress?.progress_percent || 0);

const fullReferralLink = computed(() => {
  if (!referralCode.value) return '';
  return `${window.location.origin}/register?ref=${referralCode.value}`;
});

const formatCurrency = (amount) => new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);

const titleCase = (str) => {
  if (!str) return '';
  return str.toLowerCase().split('_').join(' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const timeAgo = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const seconds = Math.floor((now - date) / 1000);
  
  if (seconds < 60) return 'just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  
  return date.toLocaleDateString();
};

const copyReferralLink = async () => {
  try {
    await navigator.clipboard.writeText(fullReferralLink.value);
    alert('Referral link copied!');
  } catch (err) {
    console.error('Failed to copy:', err);
  }
};

const updateTeamName = async () => {
  if (!newTeamName.value) return;
  updating.value = true;
  try {
    const { data } = await axios.post('/api/super-agent/update-team-name', { team_name: newTeamName.value });
    if (data.success) {
      user.value.team_name = newTeamName.value;
      if (data.referral_code) {
        referralCode.value = data.referral_code;
      }
      showEditForm.value = false;
      newTeamName.value = '';
      alert('Team name updated successfully!');
    } else {
      alert('Error: ' + (data.message || 'Failed to update team name'));
    }
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'Failed to update team name'));
  } finally {
    updating.value = false;
  }
};

const shareVia = (platform) => {
  const text = `Join my team at 666Win! Use my referral link: ${fullReferralLink.value}`;
  
  if (platform === 'whatsapp') {
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`);
  } else if (platform === 'copy') {
    navigator.clipboard.writeText(text);
    alert('Referral link copied to clipboard!');
  }
};

const fetchDashboard = async () => {
  try {
    const { data } = await axios.get('/api/super-agent/dashboard');
    user.value = data.user;
    stats.value = data.stats;
    referralCode.value = data.referral_link || '';
  } catch (error) {
    console.error('Error fetching dashboard:', error);
  }
};

const fetchRecentEarnings = async () => {
  try {
    const { data } = await axios.get('/api/super-agent/earnings', { params: { per_page: 5 } });
    recentEarnings.value = data.data || [];
  } catch (error) {
    console.error('Error fetching recent earnings:', error);
  }
};

onMounted(() => {
  fetchDashboard();
  fetchRecentEarnings();
});
</script>