<template>
  <div class="space-y-8">
    <!-- Active Challenges -->
    <div>
      <h2 class="text-xl font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
        <span class="bg-emerald-500/20 w-1.5 h-6 rounded-full"></span>
        Active Challenges
      </h2>

      <div v-if="activeChallenges.length" class="grid gap-4">
        <div v-for="challenge in activeChallenges" :key="challenge.id" class="bg-slate-800/60 rounded-[20px] border border-emerald-500/20 backdrop-blur-md p-6">
          <div class="flex justify-between items-start mb-4">
            <div>
              <h3 class="text-lg font-black text-white italic tracking-wider">{{ challenge.title }}</h3>
              <p class="text-sm text-slate-400 mt-1">{{ challenge.description }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-slate-400">Time Left</p>
              <p class="text-sm font-black text-emerald-400">{{ timeRemaining(challenge.ends_at) }}</p>
            </div>
          </div>

          <div class="mb-4">
            <p class="text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Progress</p>
            <div class="w-full bg-slate-700/50 rounded-full h-2">
              <div class="bg-emerald-500 h-2 rounded-full transition-all" :style="{ width: progressPercent(challenge) + '%' }"></div>
            </div>
            <p class="text-xs text-slate-400 mt-1">{{ challenge.user_progress || 0 }} / {{ challenge.target_value }}</p>
          </div>

          <div class="grid grid-cols-3 gap-3 mb-4 bg-slate-700/50 rounded-lg p-3">
            <div>
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Target</p>
              <p class="text-sm font-black text-neon-cyan">{{ formatNumber(challenge.target_value) }}</p>
            </div>
            <div>
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Participants</p>
              <p class="text-sm font-black text-neon-pink">{{ challenge.participants_count || 0 }}</p>
            </div>
            <div>
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Prize Pool</p>
              <p class="text-sm font-black text-neon-gold font-mono">Rs. {{ formatCurrency(challenge.reward_amount) }}</p>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8 text-center">
        <p class="text-slate-400">No active challenges at the moment</p>
      </div>
    </div>

    <!-- Upcoming Challenges -->
    <div>
      <h2 class="text-xl font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
        <span class="bg-yellow-500/20 w-1.5 h-6 rounded-full"></span>
        Upcoming Challenges
      </h2>

      <div v-if="upcomingChallenges.length" class="grid gap-4">
        <div v-for="challenge in upcomingChallenges" :key="challenge.id" class="bg-slate-800/60 rounded-[20px] border border-yellow-500/20 backdrop-blur-md p-6 opacity-75">
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-lg font-black text-white italic tracking-wider">{{ challenge.title }}</h3>
              <p class="text-sm text-slate-400 mt-1">Starts {{ formatDate(challenge.starts_at) }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border border-yellow-500/20 bg-yellow-500/10 text-yellow-400">
              Coming Soon
            </span>
          </div>
        </div>
      </div>
      <div v-else class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8 text-center">
        <p class="text-slate-400">No upcoming challenges</p>
      </div>
    </div>

    <!-- Completed Challenges -->
    <div>
      <h2 class="text-xl font-black text-white italic tracking-wider mb-4 uppercase flex items-center gap-2">
        <span class="bg-slate-500/20 w-1.5 h-6 rounded-full"></span>
        Completed Challenges
      </h2>

      <div v-if="completedChallenges.length" class="grid gap-4">
        <div v-for="challenge in completedChallenges" :key="challenge.id" class="bg-slate-800/60 rounded-[20px] border border-slate-500/20 backdrop-blur-md p-6">
          <div class="flex justify-between items-start mb-2">
            <h3 class="text-lg font-black text-white italic tracking-wider">{{ challenge.title }}</h3>
            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border border-slate-500/20 bg-slate-500/10 text-slate-400">
              Completed
            </span>
          </div>
          <p class="text-sm text-slate-400">Ended {{ formatDate(challenge.ends_at) }}</p>
        </div>
      </div>
      <div v-else class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-8 text-center">
        <p class="text-slate-400">No completed challenges</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const activeChallenges = ref([]);
const upcomingChallenges = ref([]);
const completedChallenges = ref([]);

const formatCurrency = (amount) => new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
const formatDate = (date) => new Date(date).toLocaleDateString('en-PK', { year: 'numeric', month: 'short', day: 'numeric' });
const formatNumber = (num) => new Intl.NumberFormat('en-PK').format(num);

const timeRemaining = (endDate) => {
  const now = new Date();
  const end = new Date(endDate);
  const diff = Math.floor((end - now) / 1000);
  
  if (diff < 0) return 'Ended';
  
  const days = Math.floor(diff / 86400);
  const hours = Math.floor((diff % 86400) / 3600);
  
  if (days > 0) return `${days}d ${hours}h`;
  return `${hours}h`;
};

const progressPercent = (challenge) => {
  if (!challenge.user_progress || !challenge.target_value) return 0;
  return Math.min(100, (challenge.user_progress / challenge.target_value) * 100);
};

const fetchChallenges = async () => {
  try {
    const { data } = await axios.get('/api/super-agent/challenges');
    activeChallenges.value = data.active || [];
    upcomingChallenges.value = data.upcoming || [];
    completedChallenges.value = data.completed || [];
  } catch (error) {
    console.error('Error fetching challenges:', error);
  }
};

onMounted(() => {
  fetchChallenges();
});
</script>
