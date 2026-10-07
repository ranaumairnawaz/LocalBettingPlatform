<template>
  <div class="min-h-screen bg-gradient-to-b from-slate-900 to-slate-950 text-white">
    <!-- Header -->
    <div class="border-b border-white/10 bg-slate-900/50 backdrop-blur-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-neon-cyan/20 border border-neon-cyan/40 flex items-center justify-center">
              <Star class="w-6 h-6 text-neon-cyan animate-spin-slow" />
            </div>
            <h1 class="text-2xl font-black italic tracking-wider">SUPER AGENT</h1>
          </div>
          <button @click="$router.back()" class="text-slate-400 hover:text-slate-200 transition-colors flex items-center gap-2 text-sm font-semibold cursor-pointer">
            <ArrowLeft class="w-4 h-4" />
            Back
          </button>
        </div>

        <!-- Navigation Tabs — only visible for approved Super Agents -->
        <div v-if="auth.user?.is_super_agent" class="flex gap-2 overflow-x-auto pb-2">
          <router-link 
            to="/super-agent/dashboard"
            class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap"
            :class="$route.path === '/super-agent/dashboard' ? 'bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/40 shadow-[0_0_10px_rgba(0,242,254,0.1)]' : 'bg-white/5 text-slate-400 hover:text-slate-200 border border-white/5'"
          >
            Dashboard
          </router-link>
          <router-link 
            to="/super-agent/team"
            class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap"
            :class="$route.path === '/super-agent/team' ? 'bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/40 shadow-[0_0_10px_rgba(0,242,254,0.1)]' : 'bg-white/5 text-slate-400 hover:text-slate-200 border border-white/5'"
          >
            Team
          </router-link>
          <router-link 
            to="/super-agent/earnings"
            class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap"
            :class="$route.path === '/super-agent/earnings' ? 'bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/40 shadow-[0_0_10px_rgba(0,242,254,0.1)]' : 'bg-white/5 text-slate-400 hover:text-slate-200 border border-white/5'"
          >
            Earnings
          </router-link>
          <router-link 
            to="/super-agent/content"
            class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap"
            :class="$route.path === '/super-agent/content' ? 'bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/40 shadow-[0_0_10px_rgba(0,242,254,0.1)]' : 'bg-white/5 text-slate-400 hover:text-slate-200 border border-white/5'"
          >
            Content
          </router-link>
          <router-link 
            to="/super-agent/challenges"
            class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap"
            :class="$route.path === '/super-agent/challenges' ? 'bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/40 shadow-[0_0_10px_rgba(0,242,254,0.1)]' : 'bg-white/5 text-slate-400 hover:text-slate-200 border border-white/5'"
          >
            Challenges
          </router-link>
          <router-link 
            to="/super-agent/leaderboard"
            class="px-4 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap"
            :class="$route.path === '/super-agent/leaderboard' ? 'bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/40 shadow-[0_0_10px_rgba(0,242,254,0.1)]' : 'bg-white/5 text-slate-400 hover:text-slate-200 border border-white/5'"
          >
            Leaderboard
          </router-link>
        </div>
      </div>
    </div>

    <!-- Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
      <router-view :key="$route.path" />
    </main>
  </div>
</template>

<script setup>
import { Star, ArrowLeft } from 'lucide-vue-next';
import { useAuthStore } from '../../store/auth';

const auth = useAuthStore();
</script>

<style scoped>
@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.animate-spin-slow {
  animation: spin-slow 10s linear infinite;
}
</style>