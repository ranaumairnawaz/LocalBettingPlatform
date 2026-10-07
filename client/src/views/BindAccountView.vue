<template>
  <div class="min-h-screen text-slate-100 flex flex-col gap-6 pb-10">
    <!-- Header -->
    <div>
      <router-link to="/profile" class="inline-flex items-center gap-2 text-slate-400 hover:text-neon-cyan transition-all mb-4 text-xs font-black uppercase tracking-wider">
        <ArrowLeft size="14" /> Back to Profile
      </router-link>
      <h1 class="text-2xl font-black text-white italic tracking-wide uppercase">Withdrawal Binding</h1>
      <p class="text-xs text-slate-400">Bind your accounts for fast, seamless automated withdrawals.</p>
    </div>

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- 1. BIND FORM (LEFT/COL-SPAN-2) -->
      <div class="lg:col-span-2 flex flex-col gap-6">
        <div class="glow-card-cyan p-6 flex flex-col gap-6">
          <div class="border-b border-white/5 pb-3">
            <h3 class="font-black text-base text-white italic uppercase tracking-wider">Bind New Account</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Choose method and input required credentials</p>
          </div>

          <form @submit.prevent="handleBind" class="space-y-6">
            <!-- Select Method -->
            <div class="space-y-2">
              <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest">Select Withdrawal Method</label>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <button 
                  v-for="method in methods" 
                  :key="method.id" 
                  type="button" 
                  @click="selectMethod(method)"
                  :class="selectedMethod?.id === method.id ? 'bg-neon-cyan/10 border-neon-cyan text-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.15)]' : 'bg-white/5 border-white/5 hover:border-white/10 text-slate-400'"
                  class="p-4 rounded-2xl border-2 font-black italic uppercase tracking-wider text-xs transition-all active:scale-95 text-center flex flex-col items-center justify-center gap-2"
                >
                  <WalletCards size="20" />
                  <span>{{ method.name }}</span>
                </button>
              </div>
            </div>

            <!-- Dynamic Input Fields -->
            <div v-if="selectedMethod" class="space-y-4 pt-4 border-t border-white/5 animate-in fade-in duration-300">
              <div class="bg-neon-cyan/5 border border-neon-cyan/10 p-3.5 rounded-xl flex items-center gap-3">
                <Info size="16" class="text-neon-cyan shrink-0" />
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                  Details entered must match your official <strong class="text-neon-cyan">{{ selectedMethod.name }}</strong> account credentials.
                </p>
              </div>

              <div v-for="field in selectedMethod.required_fields" :key="field" class="space-y-2">
                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest">{{ field }}</label>
                <input 
                  v-model="formDetails[field]" 
                  type="text" 
                  required 
                  class="w-full bg-white/5 border border-white/5 rounded-xl py-3.5 px-4 focus:outline-none focus:border-neon-cyan/40 text-sm text-slate-200 font-bold placeholder-slate-600 transition-all"
                  :placeholder="'Enter your ' + field.toLowerCase()"
                />
              </div>

              <!-- Submit Button -->
              <button 
                type="submit" 
                :disabled="submitting" 
                class="w-full bg-neon-cyan text-black font-black italic uppercase tracking-widest text-xs py-4 rounded-xl active:scale-95 transition-all shadow-[0_0_20px_rgba(0,242,254,0.25)] flex justify-center items-center gap-2 cursor-pointer"
              >
                <span v-if="submitting" class="w-4 h-4 border-2 border-black/30 border-t-black rounded-full animate-spin"></span>
                <span v-else>Confirm & Bind Account</span>
              </button>
            </div>

            <div v-else class="text-center py-10 text-slate-500 italic text-xs flex flex-col items-center justify-center gap-2">
              <HelpCircle size="28" class="stroke-[1.5]" />
              <span>Select a withdrawal gateway above to configure.</span>
            </div>
          </form>
        </div>
      </div>

      <!-- 2. BOUND ACCOUNTS LIST (RIGHT/COL-SPAN-1) -->
      <div class="flex flex-col gap-6">
        <div class="glow-card-cyan p-6 flex flex-col gap-6">
          <div class="border-b border-white/5 pb-3">
            <h3 class="font-black text-base text-white italic uppercase tracking-wider">Your Bindings</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Currently active payout gateways</p>
          </div>

          <div v-if="boundAccounts.length > 0" class="space-y-4">
            <div 
              v-for="account in boundAccounts" 
              :key="account.id" 
              class="bg-white/5 border border-white/5 hover:border-white/10 p-5 rounded-2xl flex flex-col gap-4 transition-all"
            >
              <div class="flex justify-between items-center border-b border-white/5 pb-2.5">
                <span class="font-black italic text-white uppercase tracking-wider text-sm flex items-center gap-1.5">
                  <WalletCards size="16" class="text-neon-cyan" />
                  {{ account.method?.name }}
                </span>
                <button 
                  @click="handleUnbind(account.id)" 
                  class="text-neon-pink hover:bg-neon-pink/15 p-2 rounded-xl transition-all active:scale-90 cursor-pointer"
                  title="Remove account credentials"
                >
                  <Trash2 size="16" />
                </button>
              </div>

              <!-- Bound fields list -->
              <div class="space-y-2">
                <div 
                  v-for="(val, key) in account.account_details" 
                  :key="key" 
                  class="flex justify-between items-center text-xs bg-black/40 p-2.5 rounded-xl border border-white/5"
                >
                  <span class="text-slate-400 uppercase font-black tracking-wider text-[8px]">{{ key }}</span>
                  <strong class="text-slate-200 font-mono select-all">{{ val }}</strong>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12 text-slate-600 italic text-xs flex flex-col items-center justify-center gap-2 border border-dashed border-white/5 rounded-2xl bg-black/10">
            <HelpCircle size="30" class="stroke-[1.5]" />
            <span>No withdrawal methods bound yet.</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ArrowLeft, WalletCards, Info, HelpCircle, Trash2 } from 'lucide-vue-next'
import { useToastStore } from '../store/toast'
import axios from 'axios'

const toast = useToastStore()

const methods = ref([])
const boundAccounts = ref([])
const selectedMethod = ref(null)
const formDetails = ref({})
const submitting = ref(false)

const selectMethod = (method) => {
  selectedMethod.value = method
  formDetails.value = {}
  method.required_fields.forEach(field => {
    formDetails.value[field] = ''
  })
}

const fetchMethods = async () => {
  try {
    const { data } = await axios.get('/api/withdrawal-methods')
    methods.value = data
  } catch (e) {
    console.error('Failed to load withdrawal methods', e)
  }
}

const fetchBoundAccounts = async () => {
  try {
    const { data } = await axios.get('/api/user/withdrawal-accounts')
    boundAccounts.value = data
  } catch (e) {
    console.error('Failed to load user bound accounts', e)
  }
}

const handleBind = async () => {
  submitting.value = true
  try {
    const { data } = await axios.post('/api/user/withdrawal-accounts', {
      withdrawal_method_id: selectedMethod.value.id,
      account_details: formDetails.value
    })
    toast.show(data.message || 'Account bound successfully!')
    selectedMethod.value = null
    formDetails.value = {}
    fetchBoundAccounts()
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to bind account credentials', 'error')
  } finally {
    submitting.value = false
  }
}

const handleUnbind = async (id) => {
  if (!confirm('Are you sure you want to unbind this account? You will not be able to withdraw via this method until you bind it again.')) {
    return
  }
  try {
    const { data } = await axios.delete(`/api/user/withdrawal-accounts/${id}`)
    toast.show(data.message || 'Account unbound successfully!')
    fetchBoundAccounts()
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to unbind account', 'error')
  }
}

onMounted(() => {
  fetchMethods()
  fetchBoundAccounts()
})
</script>

<style scoped>
.glow-card-cyan {
  background: rgba(18, 22, 35, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  backdrop-filter: blur(12px);
  box-shadow: 0 0 30px rgba(0, 242, 254, 0.02);
  transition: all 0.3s ease;
}
.glow-card-cyan:hover {
  border-color: rgba(0, 242, 254, 0.2);
  box-shadow: 0 0 40px rgba(0, 242, 254, 0.05);
}
</style>
