<template>
  <div class="px-4 pt-6 pb-8">
    <div class="flex items-center gap-3 mb-6">
      <TrophyIcon class="text-primary w-8 h-8" />
      <div>
        <h1 class="text-2xl font-bold text-white">Top Players</h1>
        <p class="text-zinc-400 text-sm">Top 20 users by total winnings.</p>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center h-48">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary"></div>
    </div>
    
    <div v-else-if="users.length === 0" class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 text-center text-zinc-500">
      <TrophyIcon class="w-12 h-12 mx-auto mb-3 opacity-20" />
      <p>No rankings available yet.</p>
    </div>

    <div v-else class="space-y-3">
      <div v-for="(user, index) in users" :key="user.user.id" 
           class="bg-zinc-900 border border-zinc-800 rounded-2xl p-4 flex items-center justify-between transition-all hover:bg-zinc-800"
           :class="{'border-primary shadow-[0_0_15px_rgba(34,197,94,0.15)]': index < 3}">
        
        <div class="flex items-center gap-4">
          <!-- Rank Badge -->
          <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm shrink-0"
               :class="{
                 'bg-yellow-500 text-black shadow-[0_0_10px_rgba(234,179,8,0.5)]': index === 0,
                 'bg-zinc-300 text-black': index === 1,
                 'bg-amber-700 text-white': index === 2,
                 'bg-zinc-800 text-zinc-400': index > 2
               }">
            {{ index + 1 }}
          </div>
          
          <div class="flex items-center gap-3">
            <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user.user.username)}&background=18181b&color=fff`" 
                 class="w-10 h-10 rounded-full border border-zinc-700">
            <div>
              <h3 class="font-bold text-white leading-tight">{{ user.user.username }}</h3>
              <p v-if="index === 0" class="text-[10px] text-primary uppercase font-black tracking-wider mt-0.5">Grand Champion</p>
            </div>
          </div>
        </div>
        
        <div class="text-right">
          <p class="text-xs text-zinc-500 mb-0.5 uppercase tracking-wider font-bold">Winnings</p>
          <p class="font-bold text-primary">Rs. {{ formatAmount(user.total_won) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Trophy as TrophyIcon } from 'lucide-vue-next'

const users = ref([])
const loading = ref(true)

const fetchLeaderboard = async () => {
  try {
    const token = localStorage.getItem('token')
    const response = await fetch('/api/leaderboard', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
    
    if (response.ok) {
      users.value = await response.json()
    }
  } catch (error) {
    console.error('Error fetching leaderboard:', error)
  } finally {
    loading.value = false
  }
}

const formatAmount = (amount) => {
  return Number(amount).toLocaleString('en-IN', { maximumFractionDigits: 0 })
}

onMounted(() => {
  fetchLeaderboard()
})
</script>
