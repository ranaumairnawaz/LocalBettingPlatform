<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-4 text-primary italic">My Wallet</h1>
    
    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-primary to-yellow-600 p-6 rounded-3xl text-black mb-6 shadow-xl shadow-primary/20 relative overflow-hidden">
      <div class="absolute -right-4 -bottom-4 opacity-10">
          <WalletIcon size="120" />
      </div>
      <div class="flex justify-between items-start mb-2 relative">
        <span class="text-sm font-bold uppercase opacity-80">Available Balance</span>
      </div>
      <h2 class="text-4xl font-black italic relative">Rs. {{ auth.user?.balance || '0.00' }}</h2>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-4 mb-8">
      <button 
        @click="showDeposit = true"
        class="bg-primary text-black font-bold py-4 rounded-2xl flex items-center justify-center gap-2 active:scale-95 transition-all shadow-lg shadow-primary/10"
      >
        <ArrowDownCircle size="20" />
        Deposit
      </button>
      <button 
        @click="showWithdraw = true"
        class="bg-zinc-800 text-white font-bold py-4 rounded-2xl flex items-center justify-center gap-2 active:scale-95 transition-all"
      >
        <ArrowUpCircle size="20" />
        Withdraw
      </button>
    </div>

    <!-- Transaction History -->
    <section>
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold">Transaction History</h3>
        <button @click="fetchTransactions" class="text-primary text-xs font-bold">Refresh</button>
      </div>
      <div class="space-y-3">
        <div v-for="tx in transactions" :key="tx.id" class="bg-zinc-900 p-4 rounded-2xl flex justify-between items-center border border-zinc-800/50">
          <div class="flex items-center gap-3">
            <div :class="tx.type === 'deposit' ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'" class="p-2 rounded-xl">
              <ArrowDownLeft v-if="tx.type === 'deposit'" size="18" />
              <ArrowUpRight v-else size="18" />
            </div>
            <div>
              <p class="font-bold capitalize text-sm">{{ tx.type }}</p>
              <p class="text-[10px] text-zinc-500">{{ formatDate(tx.created_at) }} via {{ tx.method }}</p>
            </div>
          </div>
          <div class="text-right">
            <p :class="tx.type === 'deposit' ? 'text-green-500' : 'text-red-500'" class="font-bold text-sm">
              {{ tx.type === 'deposit' ? '+' : '-' }} Rs. {{ tx.amount }}
            </p>
            <p class="text-[10px] uppercase font-black" :class="statusColor(tx.status)">{{ tx.status }}</p>
          </div>
        </div>
        <div v-if="transactions.length === 0" class="text-center py-10 text-zinc-600 text-sm italic">
          No transactions found.
        </div>
      </div>
    </section>

    <!-- Deposit Modal -->
    <div v-if="showDeposit" class="fixed inset-0 z-[60] bg-black/90 p-4 flex flex-col justify-end">
       <div class="bg-zinc-900 rounded-3xl p-6 mb-4 animate-in slide-in-from-bottom duration-300 max-h-[80vh] overflow-y-auto">
          <div class="flex justify-between items-center mb-6">
             <h2 class="text-xl font-bold">Deposit Funds</h2>
             <button @click="resetDeposit" class="text-zinc-500"><X /></button>
          </div>

          <!-- Step 1: Request -->
          <form v-if="depositStep === 1" @submit.prevent="handleDepositRequest" class="space-y-4">
             <div>
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-1 ml-1">Payment Method</label>
                <div class="grid grid-cols-2 gap-3">
                    <button 
                        v-for="method in depositMethods" :key="method.id"
                        type="button" 
                        @click="depositForm.method_id = method.id" 
                        :class="depositForm.method_id === method.id ? 'border-primary bg-primary/10 text-primary' : 'border-zinc-800'" 
                        class="p-4 rounded-2xl border-2 font-bold text-sm transition-all text-white">
                        {{ method.name }}
                    </button>
                </div>
             </div>
             <div>
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-1 ml-1">Amount (PKR)</label>
                <input v-model="depositForm.amount" type="number" placeholder="Min 100" class="w-full bg-zinc-800 p-4 rounded-2xl border-none focus:ring-2 focus:ring-primary" required min="100" />
             </div>
             <button type="submit" :disabled="loading" class="w-full bg-primary text-black font-bold py-4 rounded-2xl mt-4 active:scale-95 transition-all flex justify-center">
                <span v-if="loading" class="w-5 h-5 border-2 border-black/30 border-t-black rounded-full animate-spin"></span>
                <span v-else>Request Account Details</span>
             </button>
          </form>

          <!-- Step 2: Confirm -->
          <form v-if="depositStep === 2" @submit.prevent="handleDepositConfirm" class="space-y-4">
             <div class="bg-primary/10 border border-primary/20 p-4 rounded-2xl mb-4">
                <p class="text-xs text-primary mb-2 font-bold uppercase">Send exactly Rs. {{ depositForm.amount }} to:</p>
                <div class="space-y-3">
                    <div v-for="val in assignedAccount.values" :key="val.id" class="flex justify-between items-center bg-zinc-950 p-3 rounded-xl border border-zinc-800">
                        <div>
                            <p class="text-[10px] text-zinc-500 font-bold uppercase">{{ val.key }}</p>
                            <p class="text-sm font-black">{{ val.value }}</p>
                        </div>
                        <button type="button" @click="copyText(val.value)" class="text-xs bg-zinc-800 px-3 py-1 rounded text-primary font-bold">Copy</button>
                    </div>
                </div>
             </div>

             <div>
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-1 ml-1">Transaction ID / Reference</label>
                <input v-model="depositForm.reference" type="text" placeholder="Enter TRID" class="w-full bg-zinc-800 p-4 rounded-2xl border-none focus:ring-2 focus:ring-primary" required />
             </div>
             <div>
                <label class="block text-[10px] font-black uppercase text-zinc-500 mb-1 ml-1">Screenshot Proof (Optional)</label>
                <input type="file" @change="onFileChange" class="w-full bg-zinc-800 p-3 rounded-2xl border-none text-xs text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-primary file:text-black cursor-pointer" accept="image/*" />
             </div>
             <button type="submit" :disabled="loading" class="w-full bg-green-500 text-black font-bold py-4 rounded-2xl mt-4 active:scale-95 transition-all flex justify-center">
                <span v-if="loading" class="w-5 h-5 border-2 border-black/30 border-t-black rounded-full animate-spin"></span>
                <span v-else>I Have Paid - Confirm</span>
             </button>
          </form>
       </div>
    </div>

    <!-- Withdraw Modal -->
    <div v-if="showWithdraw" class="fixed inset-0 z-[60] bg-black/90 p-4 flex flex-col justify-end">
       <div class="bg-[#121623] rounded-3xl p-6 mb-4 animate-in slide-in-from-bottom duration-300 border border-white/10 max-h-[80vh] overflow-y-auto">
          <div class="flex justify-between items-center mb-6">
             <h2 class="text-xl font-bold text-white italic uppercase tracking-wider">Withdraw Funds</h2>
             <button @click="showWithdraw = false" class="text-slate-400 hover:text-white transition-colors cursor-pointer"><X /></button>
          </div>
          <form @submit.prevent="handleWithdraw" class="space-y-4">
             <div>
                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1">Amount to Withdraw</label>
                <input v-model="withdrawForm.amount" type="number" placeholder="Min 500" class="w-full bg-black/40 p-4 rounded-2xl border border-white/5 focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-mono font-bold" required min="500" />
                <p class="text-[10px] text-slate-400 mt-1 font-bold">Available: Rs. {{ auth.user?.balance }}</p>
             </div>
             <div>
                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 ml-1">Receive via</label>
                <select v-model="withdrawForm.withdrawal_method_id" @change="checkBindingOnChange" class="w-full bg-black/40 p-4 rounded-2xl border border-white/5 focus:ring-1 focus:ring-neon-cyan focus:border-neon-cyan text-slate-200 font-bold cursor-pointer">
                    <option v-for="method in withdrawalMethods" :key="method.id" :value="method.id" class="bg-[#121623] text-slate-200">
                      {{ method.name }}
                    </option>
                </select>
             </div>
             <button type="submit" :disabled="loading" class="w-full bg-neon-cyan hover:bg-neon-cyan/95 text-black font-black italic uppercase tracking-widest text-xs py-4 rounded-2xl mt-4 active:scale-95 transition-all flex justify-center shadow-[0_0_15px_rgba(0,242,254,0.25)] cursor-pointer">
                <span v-if="loading" class="w-5 h-5 border-2 border-black/30 border-t-black rounded-full animate-spin"></span>
                <span v-else>Submit Withdrawal</span>
             </button>
          </form>
       </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Wallet as WalletIcon, ArrowDownCircle, ArrowUpCircle, ArrowDownLeft, ArrowUpRight, X } from 'lucide-vue-next'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import { useAudioStore } from '../store/audio'
import axios from 'axios'

const auth = useAuthStore()
const toast = useToastStore()
const audio = useAudioStore()
const router = useRouter()
const showDeposit = ref(false)
const showWithdraw = ref(false)
const loading = ref(false)
const transactions = ref([])
const depositMethods = ref([])
const withdrawalMethods = ref([])
const boundAccounts = ref([])

const depositStep = ref(1)
const assignedAccount = ref(null)
const activeTransactionId = ref(null)

const depositForm = ref({
    amount: '',
    method_id: '',
    reference: '',
    proof: null
})

const withdrawForm = ref({
    amount: '',
    withdrawal_method_id: ''
})

const resetDeposit = () => {
    showDeposit.value = false
    depositStep.value = 1
    assignedAccount.value = null
    activeTransactionId.value = null
    depositForm.value = { amount: '', method_id: depositMethods.value[0]?.id || '', reference: '', proof: null }
}

const onFileChange = (e) => {
    depositForm.value.proof = e.target.files[0]
}

const copyText = (text) => {
    navigator.clipboard.writeText(text)
    toast.show('Copied to clipboard!', 'info')
}

const fetchMethods = async () => {
    try {
        const { data } = await axios.get('/api/deposit-methods')
        depositMethods.value = data
        if (data.length > 0) depositForm.value.method_id = data[0].id
    } catch (e) {
        console.error(e)
    }
}

const fetchTransactions = async () => {
  try {
    const { data } = await axios.get('/api/transactions')
    transactions.value = data.data
  } catch (e) {
    console.error(e)
  }
}

const handleDepositRequest = async () => {
    loading.value = true
    try {
        const { data } = await axios.post('/api/deposit', {
            amount: depositForm.value.amount,
            method_id: depositForm.value.method_id
        })
        assignedAccount.value = data.account
        activeTransactionId.value = data.transaction.id
        depositStep.value = 2
        toast.show('Account assigned! Please complete payment.', 'info')
    } catch (e) {
        toast.show(e.response?.data?.message || 'Failed to request account', 'error')
    } finally {
        loading.value = false
    }
}

const handleDepositConfirm = async () => {
    loading.value = true
    const formData = new FormData()
    formData.append('reference', depositForm.value.reference)
    if (depositForm.value.proof) {
        formData.append('proof', depositForm.value.proof)
    }

    try {
        await axios.post(`/api/deposit/${activeTransactionId.value}/confirm`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        resetDeposit()
        audio.playDeposit()
        toast.show('Payment confirmed! Waiting for admin approval.')
        fetchTransactions()
    } catch (e) {
        toast.show(e.response?.data?.message || 'Confirmation failed', 'error')
    } finally {
        loading.value = false
    }
}

const fetchWithdrawalMethods = async () => {
    try {
        const { data } = await axios.get('/api/withdrawal-methods')
        withdrawalMethods.value = data
        if (data.length > 0) {
            withdrawForm.value.withdrawal_method_id = data[0].id
        }
    } catch (e) {
        console.error(e)
    }
}

const fetchBoundAccounts = async () => {
    try {
        const { data } = await axios.get('/api/user/withdrawal-accounts')
        boundAccounts.value = data
    } catch (e) {
        console.error(e)
    }
}

const checkBindingOnChange = () => {
    const isBound = boundAccounts.value.some(a => a.withdrawal_method_id === withdrawForm.value.withdrawal_method_id)
    if (!isBound) {
        const selectedMethod = withdrawalMethods.value.find(m => m.id === withdrawForm.value.withdrawal_method_id)
        toast.show(`You must bind your ${selectedMethod?.name || 'withdrawal'} account details! Redirecting to bind page...`, 'error')
        setTimeout(() => {
            router.push('/profile/bind')
        }, 1500)
    }
}

const handleWithdraw = async () => {
    // Check if the selected method is bound
    const selectedMethod = withdrawalMethods.value.find(m => m.id === withdrawForm.value.withdrawal_method_id)
    const isBound = boundAccounts.value.some(a => a.withdrawal_method_id === withdrawForm.value.withdrawal_method_id)

    if (!isBound) {
        toast.show(`You must bind your ${selectedMethod?.name || 'withdrawal'} account before requesting a payout! Redirecting...`, 'error')
        setTimeout(() => {
            router.push('/profile/bind')
        }, 1500)
        return
    }

    loading.value = true
    try {
        await axios.post('/api/withdraw', {
            amount: withdrawForm.value.amount,
            withdrawal_method_id: withdrawForm.value.withdrawal_method_id
        })
        showWithdraw.value = false
        audio.playDeposit()
        toast.show('Withdrawal request submitted!')
        fetchTransactions()
        auth.fetchUser()
        withdrawForm.value = { amount: '', withdrawal_method_id: withdrawalMethods.value[0]?.id || '' }
    } catch (e) {
        if (e.response?.data?.redirect_to_bind) {
            toast.show(e.response.data.message, 'error')
            setTimeout(() => {
                router.push('/profile/bind')
            }, 1500)
        } else {
            toast.show(e.response?.data?.message || 'Withdrawal failed', 'error')
        }
    } finally {
        loading.value = false
    }
}

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

const statusColor = (status) => {
  if (status === 'completed') return 'text-green-500'
  if (status === 'pending') return 'text-yellow-500'
  return 'text-red-500'
}

onMounted(() => {
  fetchMethods()
  fetchWithdrawalMethods()
  fetchBoundAccounts()
  fetchTransactions()
  auth.fetchUser()
})
</script>
