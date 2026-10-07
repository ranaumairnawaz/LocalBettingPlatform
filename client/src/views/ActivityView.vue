<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-6 text-primary italic">Game Activity</h1>

    <div class="flex gap-2 mb-6">
        <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-primary text-black' : 'bg-zinc-800 text-zinc-400'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all">All</button>
        <button @click="filter = 'win'" :class="filter === 'win' ? 'bg-green-500 text-black' : 'bg-zinc-800 text-zinc-400'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all">Wins</button>
        <button @click="filter = 'lose'" :class="filter === 'lose' ? 'bg-red-500 text-black' : 'bg-zinc-800 text-zinc-400'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all">Losses</button>
    </div>

    <!-- Activity List -->
    <div class="space-y-4">
        <div v-for="log in filteredLogs" :key="log.id" class="bg-zinc-900 p-5 rounded-3xl border border-zinc-800 relative overflow-hidden">
            <div v-if="log.result === 'win'" class="absolute -right-4 -top-4 opacity-10 rotate-12 text-green-500">
                <Trophy size="80" />
            </div>
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="font-black text-white italic uppercase">{{ log.game?.name || 'Game' }}</h4>
                    <p class="text-[10px] text-zinc-500">{{ formatDate(log.created_at) }}</p>
                </div>
                <div :class="log.result === 'win' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase">
                    {{ log.result }}
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-zinc-800/50">
                <div>
                    <p class="text-[10px] text-zinc-500 uppercase font-bold">Bet Amount</p>
                    <p class="font-bold text-sm">Rs. {{ log.bet_amount }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-zinc-500 uppercase font-bold">Payout</p>
                    <p :class="log.result === 'win' ? 'text-green-500' : 'text-zinc-400'" class="font-black text-sm">Rs. {{ log.payout_amount }}</p>
                </div>
            </div>
        </div>
        <div v-if="filteredLogs.length === 0" class="text-center py-20 text-zinc-700 italic text-sm">
            No gaming activity recorded yet.
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Trophy } from 'lucide-vue-next'
import axios from 'axios'

const logs = ref([])
const filter = ref('all')

const fetchLogs = async () => {
    try {
        const { data } = await axios.get('/api/activity')
        logs.value = data
    } catch (e) {
        console.error(e)
    }
}

const filteredLogs = computed(() => {
    if (filter.value === 'all') return logs.value
    return logs.value.filter(l => l.result === filter.value)
})

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
    fetchLogs()
})
</script>
