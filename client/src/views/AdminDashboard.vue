<template>
  <div class="p-4 bg-zinc-950 min-h-screen pb-20">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-2xl font-bold text-primary">ADMIN PANEL</h1>
      <button @click="$router.push('/')" class="text-xs text-zinc-500">Back to App</button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 gap-4 mb-8">
      <div v-for="(val, label) in stats" :key="label" class="bg-zinc-900 p-4 rounded-2xl border border-zinc-800">
        <p class="text-[10px] text-zinc-500 uppercase font-bold mb-1">{{ label.replace(/_/g, ' ') }}</p>
        <p class="text-lg font-black text-white italic">Rs. {{ val }}</p>
      </div>
    </div>

    <!-- Pending Transactions -->
    <section>
      <h3 class="font-bold mb-4 flex items-center gap-2">
        Pending Transactions
        <span class="bg-primary text-black text-[10px] px-2 py-0.5 rounded-full">{{ pending.length }}</span>
      </h3>
      <div class="space-y-3">
        <div v-for="tx in pending" :key="tx.id" class="bg-zinc-900 p-4 rounded-2xl border border-zinc-800">
          <div class="flex justify-between items-start mb-4">
            <div>
              <p class="font-bold text-sm">{{ tx.user.username }}</p>
              <p class="text-[10px] text-zinc-400 capitalize">{{ tx.type }} via {{ tx.method }}</p>
            </div>
            <p class="font-black text-primary italic">Rs. {{ tx.amount }}</p>
          </div>
          <div class="flex gap-2">
            <button @click="updateTx(tx.id, 'completed')" class="flex-1 bg-green-600 text-white text-xs font-bold py-2 rounded-xl">APPROVE</button>
            <button @click="updateTx(tx.id, 'rejected')" class="flex-1 bg-red-600 text-white text-xs font-bold py-2 rounded-xl">REJECT</button>
          </div>
        </div>
        <div v-if="pending.length === 0" class="text-center py-10 text-zinc-700 italic text-sm">
          No pending transactions.
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const stats = ref({})
const pending = ref([])

const fetchData = async () => {
  try {
    const s = await axios.get('/api/admin/dashboard')
    stats.value = s.data
    const p = await axios.get('/api/admin/transactions/pending')
    pending.value = p.data
  } catch (e) {
    console.error(e)
  }
}

const updateTx = async (id, status) => {
  if (!confirm(`Are you sure you want to ${status} this transaction?`)) return
  try {
    await axios.post(`/api/admin/transactions/${id}/update`, { status })
    fetchData()
  } catch (e) {
    alert('Failed to update transaction')
  }
}

onMounted(() => {
  fetchData()
})
</script>
