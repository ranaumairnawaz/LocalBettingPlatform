<template>
  <div class="min-h-screen text-slate-100 pb-10">
    <div class="flex items-center gap-4 mb-8">
      <button @click="$router.back()" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl transition-all">
        <ChevronLeft size="20" />
      </button>
      <div>
        <h2 class="text-xs uppercase tracking-widest text-slate-400 font-bold">Profile</h2>
        <h1 class="text-2xl font-black text-white italic tracking-wide">EDIT PROFILE</h1>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
      <!-- Edit Form -->
      <div class="bg-[#121623]/60 border border-white/5 p-6 md:p-8 rounded-[24px] backdrop-blur-md">
        <h3 class="text-lg font-black text-white italic tracking-wider mb-6 border-b border-white/5 pb-4">Personal Details</h3>
        
        <form @submit.prevent="handleUpdateProfile" class="space-y-6">
          <!-- Avatar Upload -->
          <div class="flex flex-col items-center gap-4">
            <div class="relative group cursor-pointer" @click="$refs.fileInput.click()">
              <div v-if="previewAvatar || auth.user?.avatar" class="w-24 h-24 rounded-2xl border-2 border-neon-cyan/40 shadow-[0_0_20px_rgba(0,242,254,0.15)] overflow-hidden bg-zinc-900">
                <img :src="previewAvatar || getAvatarUrl(auth.user?.avatar)" class="w-full h-full object-cover" />
              </div>
              <div v-else class="w-24 h-24 bg-neon-cyan/15 rounded-2xl border-2 border-neon-cyan/40 flex items-center justify-center font-black text-4xl text-neon-cyan shadow-[0_0_20px_rgba(0,242,254,0.15)]">
                {{ auth.user?.username?.charAt(0).toUpperCase() }}
              </div>
              
              <div class="absolute inset-0 bg-black/60 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <Camera class="text-white" size="24" />
              </div>
            </div>
            
            <input 
              type="file" 
              ref="fileInput" 
              class="hidden" 
              accept="image/*" 
              @change="handleFileChange"
            />
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Click to change avatar</span>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-xs font-black uppercase text-slate-400 tracking-wider mb-2">Display Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="Enter your display name" 
              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-neon-cyan/50 text-white placeholder:text-slate-600 transition-colors"
            />
          </div>

          <!-- Username -->
          <div>
            <label class="block text-xs font-black uppercase text-slate-400 tracking-wider mb-2">Username</label>
            <input 
              v-model="form.username" 
              type="text" 
              required
              placeholder="Choose a username" 
              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-neon-cyan/50 text-white placeholder:text-slate-600 transition-colors"
            />
          </div>

          <!-- Submit Button -->
          <button 
            type="submit" 
            :disabled="loading"
            class="w-full bg-neon-cyan hover:bg-neon-cyan/90 text-black font-black italic uppercase tracking-widest py-3.5 rounded-xl transition-all active:scale-95 disabled:opacity-50 disabled:pointer-events-none mt-4 shadow-[0_0_15px_rgba(0,242,254,0.2)]"
          >
            <span v-if="loading">Saving...</span>
            <span v-else>Save Changes</span>
          </button>
        </form>
      </div>

      <!-- Login History -->
      <div class="bg-[#121623]/60 border border-white/5 p-6 md:p-8 rounded-[24px] backdrop-blur-md">
        <div class="flex items-center justify-between border-b border-white/5 pb-4 mb-4">
          <h3 class="text-lg font-black text-white italic tracking-wider">Login History</h3>
          <History size="20" class="text-neon-cyan" />
        </div>

        <div v-if="loginsLoading" class="text-center py-8 text-slate-500 italic text-sm">
          Loading history...
        </div>
        <div v-else-if="logins.length === 0" class="text-center py-8 text-slate-500 italic text-sm">
          No login records found.
        </div>
        <div v-else class="space-y-3">
          <div 
            v-for="login in logins" 
            :key="login.id"
            class="bg-white/5 border border-white/5 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-white/10 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="p-2 bg-neon-cyan/15 rounded-lg border border-neon-cyan/20 text-neon-cyan">
                <Laptop size="16" v-if="login.name === 'auth_token' || login.name.toLowerCase().includes('web')" />
                <Smartphone size="16" v-else-if="login.name.toLowerCase().includes('mobile')" />
                <Globe size="16" v-else />
              </div>
              <div class="flex flex-col">
                <span class="text-sm font-bold text-slate-200">{{ login.name }}</span>
                <span class="text-[10px] uppercase text-slate-500 font-bold tracking-widest mt-0.5">Session Token</span>
              </div>
            </div>
            <div class="flex flex-col sm:items-end gap-1">
              <div class="flex items-center gap-1 text-[10px] font-bold text-slate-400">
                <Clock size="12" />
                <span>Created: {{ formatDate(login.created_at) }}</span>
              </div>
              <div class="flex items-center gap-1 text-[10px] font-bold" :class="login.last_used_at ? 'text-neon-cyan' : 'text-slate-500'">
                <CheckCircle2 size="12" />
                <span>Last Used: {{ login.last_used_at ? formatDate(login.last_used_at) : 'Never' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ChevronLeft, Camera, History, Clock, CheckCircle2, Laptop, Smartphone, Globe } from 'lucide-vue-next'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import axios from 'axios'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()

const form = ref({
  name: auth.user?.name || '',
  username: auth.user?.username || ''
})

const fileInput = ref(null)
const selectedFile = ref(null)
const previewAvatar = ref(null)

const loading = ref(false)
const loginsLoading = ref(true)
const logins = ref([])

const getAvatarUrl = (path) => {
  if (!path) return '';
  return path.startsWith('http') ? path : `http://localhost:8000/storage/${path}`;
}

const handleFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    if (file.size > 2 * 1024 * 1024) {
      toast.show('Image size should be less than 2MB', 'error')
      return
    }
    selectedFile.value = file
    previewAvatar.value = URL.createObjectURL(file)
  }
}

const handleUpdateProfile = async () => {
  loading.value = true
  try {
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('username', form.value.username)
    if (selectedFile.value) {
      formData.append('avatar', selectedFile.value)
    }

    const { data } = await axios.post('/api/user/profile', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    auth.user = data.user
    toast.show('Profile updated successfully!', 'success')
  } catch (e) {
    if (e.response?.data?.errors) {
      const firstError = Object.values(e.response.data.errors)[0][0]
      toast.show(firstError, 'error')
    } else {
      toast.show(e.response?.data?.message || 'Failed to update profile', 'error')
    }
  } finally {
    loading.value = false
  }
}

const fetchLogins = async () => {
  loginsLoading.value = true
  try {
    const { data } = await axios.get('/api/user/logins')
    logins.value = data.logins
  } catch (e) {
    console.error('Failed to load login history', e)
    toast.show('Failed to load login history', 'error')
  } finally {
    loginsLoading.value = false
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  const date = new Date(dateStr)
  return date.toLocaleDateString(undefined, { 
    month: 'short', 
    day: 'numeric',
    year: 'numeric'
  }) + ' ' + date.toLocaleTimeString(undefined, {
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  if (!auth.user) {
    router.push('/login')
  } else {
    fetchLogins()
  }
})
</script>
