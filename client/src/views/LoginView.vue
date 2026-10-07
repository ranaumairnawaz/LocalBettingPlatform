<template>
  <div class="min-h-screen bg-[#090a0f] text-slate-100 md:grid md:grid-cols-2 relative overflow-hidden flex flex-col justify-center px-4">
    <!-- Glowing background elements -->
    <div class="absolute w-96 h-96 bg-neon-pink/5 rounded-full blur-3xl -left-20 -top-20 pointer-events-none"></div>
    <div class="absolute w-96 h-96 bg-neon-cyan/5 rounded-full blur-3xl -right-20 -bottom-20 pointer-events-none"></div>

    <!-- LEFT PANE: Premium E-Sports Graphic Console (Desktop Only) -->
    <div class="hidden md:flex flex-col justify-between p-12 bg-[#121623]/30 border-r border-white/5 relative overflow-hidden h-full">
      <!-- Top Branding -->
      <div class="flex items-center gap-3 relative z-10">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-neon-pink to-neon-cyan flex items-center justify-center font-black italic text-lg text-black shadow-[0_0_20px_rgba(0,242,254,0.3)]">666</div>
        <h1 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-neon-cyan to-neon-pink italic tracking-wider">666WIN</h1>
      </div>

      <!-- Mid Console HUD -->
      <div class="my-auto space-y-6 relative z-10 max-w-md">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-neon-cyan/10 border border-neon-cyan/25 rounded-full text-xs font-black uppercase text-neon-cyan">
          <span class="w-1.5 h-1.5 rounded-full bg-neon-cyan animate-pulse"></span>
          <span>Next Gen Multiplayer Arena</span>
        </div>
        <h2 class="text-5xl font-black text-white italic tracking-wide leading-tight">
          ENTER THE <span class="text-transparent bg-clip-text bg-gradient-to-r from-neon-cyan to-neon-pink">ARENA.</span>
        </h2>
        <p class="text-slate-400 font-semibold text-sm leading-relaxed">
          Experience low-latency, dynamic 1v1 and multiplayer matches in Ludo, Carrom, and 8 Ball Pool. Secure deposits, instantaneous verified cashouts, and dynamic settings.
        </p>

        <!-- Dynamic scoreboard mock logs -->
        <div class="p-4 bg-white/5 border border-white/5 rounded-2xl space-y-2.5 backdrop-blur-md">
          <div class="flex justify-between items-center text-[10px] text-slate-500 font-bold uppercase">
            <span>Recent Victory</span>
            <span class="text-neon-cyan">LIVE</span>
          </div>
          <div class="flex justify-between items-center text-xs font-bold">
            <span class="text-white">Player_402 (Ludo)</span>
            <span class="text-neon-green font-black">+Rs. 1,420.00</span>
          </div>
        </div>
      </div>

      <!-- Footer specs -->
      <div class="relative z-10 text-[9px] uppercase font-black tracking-widest text-slate-500">
        Copyright © 2026 666Win E-Sports • SECURED & VALIDATED
      </div>
    </div>

    <!-- RIGHT PANE: Glassmorphic Login Form (PC Card / Mobile Full Screen) -->
    <div class="flex items-center justify-center p-4 md:p-12 relative z-10">
      <div class="w-full max-w-md bg-[#121623]/60 border border-white/10 p-8 rounded-3xl backdrop-blur-md shadow-2xl flex flex-col gap-6 relative">
        <div class="text-center space-y-1.5">
          <h2 class="text-2xl font-black text-white italic uppercase tracking-wider">Player Login</h2>
          <p class="text-xs text-slate-400 font-semibold">Sign in to start winning stakes</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div class="space-y-1.5">
            <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider ml-1">Username</label>
            <input 
              v-model="form.username"
              type="text" 
              placeholder="Enter your username" 
              class="w-full bg-[#090a0f] border border-white/5 focus:border-neon-cyan/40 rounded-2xl py-4 px-5 text-sm focus:outline-none focus:ring-1 focus:ring-neon-cyan/20 transition-all font-semibold text-slate-200"
              required
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider ml-1">Password</label>
            <input 
              v-model="form.password"
              type="password" 
              placeholder="••••••••" 
              class="w-full bg-[#090a0f] border border-white/5 focus:border-neon-cyan/40 rounded-2xl py-4 px-5 text-sm focus:outline-none focus:ring-1 focus:ring-neon-cyan/20 transition-all font-semibold text-slate-200"
              required
            />
          </div>

          <button 
            type="submit" 
            :disabled="loading"
            class="w-full bg-neon-pink text-white font-black italic py-4 rounded-2xl shadow-[0_0_15px_rgba(255,0,127,0.3)] hover:shadow-[0_0_25px_rgba(255,0,127,0.5)] hover:scale-[1.02] active:scale-95 transition-all flex justify-center items-center gap-2 cursor-pointer border border-neon-pink/20 uppercase text-sm tracking-widest mt-2"
          >
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            <span>{{ loading ? 'Signing in...' : 'Sign In' }}</span>
          </button>
        </form>

        <p class="text-center text-slate-400 text-xs font-semibold">
          Don't have an account? 
          <router-link to="/register" class="text-neon-cyan hover:text-neon-cyan/85 font-black uppercase ml-1 tracking-wider">Register Now</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToastStore()
const loading = ref(false)
const form = ref({
  username: '',
  password: ''
})

const handleLogin = async () => {
  loading.value = true
  const success = await authStore.login(form.value)
  loading.value = false
  
  if (success) {
    toast.show('Welcome back!')
    router.push('/')
  } else {
    toast.show(authStore.error || 'Login failed', 'error')
  }
}
</script>
