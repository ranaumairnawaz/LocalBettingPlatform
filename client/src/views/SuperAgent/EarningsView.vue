<template>
  <div class="space-y-8">
    <!-- Month Selector & Download -->
    <div class="flex justify-between items-center bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
      <div class="flex gap-4 items-center">
        <label class="text-xs font-black text-slate-400 uppercase tracking-wider block">Statement Month</label>
        <input 
          type="month"
          v-model="selectedMonth"
          class="bg-slate-700/50 border border-white/10 rounded-lg px-4 py-2 text-xs text-white focus:outline-none focus:ring-1 focus:ring-neon-cyan cursor-pointer font-bold"
        >
      </div>
      <button 
        @click="openStatementPrint"
        class="bg-neon-cyan/20 hover:bg-neon-cyan/40 text-neon-cyan px-4 py-2.5 rounded-lg text-xs font-black uppercase tracking-wider transition-all border border-neon-cyan/20 flex items-center gap-2 cursor-pointer"
      >
        <Download class="w-4 h-4" />
        Print Statement
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Deposit Commissions</p>
        <p class="text-2xl font-black text-emerald-400 font-mono">Rs. {{ formatCurrency(stats.deposit_commissions) }}</p>
      </div>
      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Game Commissions</p>
        <p class="text-2xl font-black text-neon-cyan font-mono">Rs. {{ formatCurrency(stats.game_commissions) }}</p>
      </div>
      <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md p-6">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Content Bonuses</p>
        <p class="text-2xl font-black text-neon-gold font-mono">Rs. {{ formatCurrency(stats.content_bonuses) }}</p>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-slate-800/60 rounded-[20px] border border-white/10 backdrop-blur-md overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-slate-700/50 border-b border-white/10">
              <th class="py-3 px-4 font-black uppercase tracking-wider text-slate-400">Date</th>
              <th class="py-3 px-4 font-black uppercase tracking-wider text-slate-400">Type</th>
              <th class="py-3 px-4 font-black uppercase tracking-wider text-slate-400">Source</th>
              <th class="py-3 px-4 font-black uppercase tracking-wider text-slate-400">Level</th>
              <th class="py-3 px-4 font-black uppercase tracking-wider text-slate-400">Amount</th>
              <th class="py-3 px-4 font-black uppercase tracking-wider text-slate-400 text-right">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-for="txn in transactions" :key="txn.id" class="hover:bg-white/5 transition-colors">
              <td class="py-3 px-4 text-slate-300">{{ formatDate(txn.created_at) }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border" 
                  :class="txn.commission_type === 'deposit_commission' ? 'bg-neon-cyan/10 text-neon-cyan border-neon-cyan/20' : 
                           txn.commission_type === 'game_commission' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                           'bg-neon-gold/10 text-neon-gold border-neon-gold/20'">
                  {{ titleCase(txn.commission_type) }}
                </span>
              </td>
              <td class="py-3 px-4 text-slate-300">{{ txn.source_user?.username || 'System' }}</td>
              <td class="py-3 px-4 font-black text-neon-cyan">L{{ txn.referral_level }}</td>
              <td class="py-3 px-4 font-black text-white font-mono">Rs. {{ formatCurrency(txn.commission_amount) }}</td>
              <td class="py-3 px-4 text-right">
                <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border"
                  :class="txn.is_paid ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20'">
                  {{ txn.is_paid ? 'Paid' : 'Pending' }}
                </span>
              </td>
            </tr>
            <tr v-if="!transactions.length">
              <td colspan="6" class="py-8 px-4 text-center text-slate-400">No transactions found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2">
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
import { Download } from 'lucide-vue-next';
import axios from 'axios';

const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const currentPage = ref(1);
const transactions = ref([]);
const pagination = ref(null);
const stats = ref({ deposit_commissions: 0, game_commissions: 0, content_bonuses: 0 });

const formatCurrency = (amount) => new Intl.NumberFormat('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
const formatDate = (date) => new Date(date).toLocaleDateString('en-PK', { year: 'numeric', month: 'short', day: 'numeric' });

const titleCase = (str) => {
  if (!str) return '';
  return str.toLowerCase().split('_').join(' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const fetchEarnings = async () => {
  try {
    const [year, month] = selectedMonth.value.split('-');
    const { data } = await axios.get('/api/super-agent/earnings', {
      params: { year, month, page: currentPage.value }
    });
    transactions.value = data.data || [];
    pagination.value = data.pagination || null;
    stats.value = data.summary || stats.value;
  } catch (error) {
    console.error('Error fetching earnings:', error);
  }
};

const openStatementPrint = () => {
  const [year, month] = selectedMonth.value.split('-');
  window.open(`/api/super-agent/statement/${year}/${month}/html`, '_blank');
};

watch([selectedMonth, currentPage], () => fetchEarnings());

onMounted(() => {
  fetchEarnings();
});
</script>
