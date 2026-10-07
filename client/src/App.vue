<template>
  <div class="min-h-screen bg-[#090a0f] text-slate-100 flex flex-col md:flex-row overflow-x-hidden">
    <ToastComponent />

    <aside 
      v-if="!['/login', '/register'].includes($route.path) && !isGamePage" 
      class="hidden md:flex flex-col justify-between w-64 bg-[#121623]/70 border-r border-white/5 h-screen fixed left-0 top-0 py-6 px-4 z-40 backdrop-blur-md"
    >
      <div class="space-y-6">
        <div class="flex items-center gap-3 px-2">
          <img v-if="siteLogo" :src="siteLogo" class="h-8 w-auto object-contain" />
          <div v-else class="w-8 h-8 rounded-lg bg-gradient-to-tr from-neon-pink to-neon-cyan flex items-center justify-center font-black italic text-sm text-black">666</div>
          <h1 class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-neon-cyan to-neon-pink italic">
            {{ siteName }}
          </h1>
        </div>

        <div class="mx-1 p-3 bg-white/5 border border-white/5 rounded-2xl flex items-center justify-between">
          <div class="flex flex-col">
            <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold">Balance</span>
            <span class="text-sm font-black text-neon-cyan">{{ currencySymbol }} {{ auth.user?.balance || '0.00' }}</span>
          </div>
          <button 
            @click="$router.push('/wallet')" 
            class="bg-neon-cyan text-black rounded-xl p-1.5 hover:scale-105 active:scale-95 transition-all shadow-[0_0_10px_rgba(0,242,254,0.3)] hover:shadow-[0_0_15px_rgba(0,242,254,0.5)] cursor-pointer"
          >
            <PlusIcon size="14" class="stroke-[3]" />
          </button>
        </div>

        <nav class="space-y-1.5">
          <router-link 
            v-for="item in navItems" 
            :key="item.path" 
            :to="item.path" 
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 hover:bg-white/5 group"
            :class="[
              $route.path === item.path 
                ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan' 
                : 'text-slate-400 hover:text-slate-100 border-l-2 border-transparent'
            ]"
          >
            <component :is="item.icon" size="18" class="group-hover:scale-105 transition-transform" />
            <span>{{ item.name }}</span>
          </router-link>
        </nav>
      </div>

      <div v-if="auth.user" class="pt-4 border-t border-white/5">
        <div class="flex items-center justify-between px-2">
          <div class="flex items-center gap-3">
            <div v-if="auth.user?.avatar" class="w-9 h-9 rounded-xl border border-neon-cyan/30 overflow-hidden shrink-0">
              <img :src="getAvatarUrl(auth.user.avatar)" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-9 h-9 rounded-xl bg-neon-cyan/20 border border-neon-cyan/30 flex items-center justify-center font-bold text-neon-cyan shrink-0">
              {{ auth.user.username?.charAt(0).toUpperCase() }}
            </div>
            <div class="flex flex-col">
              <span class="text-xs font-black text-slate-200 truncate max-w-[120px]">{{ auth.user.username }}</span>
              <span class="text-[9px] text-slate-400 uppercase tracking-widest">{{ auth.user.is_admin ? 'Admin' : 'Player' }}</span>
            </div>
          </div>
          <button 
            @click="handleLogout" 
            class="p-2 text-slate-400 hover:text-neon-pink hover:bg-neon-pink/10 rounded-xl transition-all cursor-pointer"
            title="Log Out"
          >
            <LogOutIcon size="16" />
          </button>
        </div>
      </div>
    </aside>

    <header 
      v-if="!['/login', '/register'].includes($route.path) && !isGamePage" 
      class="flex md:hidden items-center justify-between p-4 bg-[#121623]/70 border-b border-white/5 backdrop-blur-md sticky top-0 z-30"
    >
      <div class="flex items-center gap-2">
        <img v-if="siteLogo" :src="siteLogo" class="h-6 w-auto object-contain" />
        <div v-else class="w-6 h-6 rounded-md bg-gradient-to-tr from-neon-pink to-neon-cyan flex items-center justify-center font-black italic text-[10px] text-black shrink-0">666</div>
        <h1 class="text-lg font-black text-transparent bg-clip-text bg-gradient-to-r from-neon-cyan to-neon-pink italic">
          {{ siteName }}
        </h1>
      </div>
      <div class="bg-white/5 border border-white/5 px-3 py-1 rounded-full flex items-center gap-2">
        <span class="text-neon-cyan text-xs font-bold">{{ currencySymbol }}{{ auth.user?.balance || '0.00' }}</span>
        <button 
          @click="$router.push('/wallet')" 
          class="bg-neon-cyan text-black rounded-full p-0.5 shadow-md shadow-neon-cyan/20 cursor-pointer"
        >
          <PlusIcon size="12" class="stroke-[3]" />
        </button>
      </div>
    </header>

    <main 
      class="flex-1 min-h-screen w-full flex flex-col transition-all duration-300"
      :class="{
        'md:pl-64': !['/login', '/register'].includes($route.path) && !isGamePage,
        'pb-24 md:pb-6': !['/login', '/register'].includes($route.path) && !isGamePage
      }"
    >
      <div class="flex-1 w-full max-w-7xl mx-auto px-4 py-4 md:px-8 md:py-8">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" :key="$route.fullPath" />
          </transition>
        </router-view>
      </div>
    </main>

    <nav 
      v-if="!['/login', '/register'].includes($route.path) && !isGamePage" 
      class="fixed bottom-0 left-0 right-0 md:hidden bg-[#121623]/90 border-t border-white/5 flex justify-around py-3 z-50 backdrop-blur-md"
    >
      <router-link 
        to="/" 
        class="flex flex-col items-center gap-1 transition-all duration-200" 
        :class="[
          $route.path === '/' 
            ? 'text-neon-cyan scale-105' 
            : 'text-slate-400 hover:text-slate-200'
        ]"
      >
        <HomeIcon size="18" />
        <span class="text-[9px] font-bold">Home</span>
      </router-link>

      <router-link 
        to="/wallet" 
        class="flex flex-col items-center gap-1 transition-all duration-200" 
        :class="[
          $route.path === '/wallet' 
            ? 'text-neon-cyan scale-105' 
            : 'text-slate-400 hover:text-slate-200'
        ]"
      >
        <WalletIcon size="18" />
        <span class="text-[9px] font-bold">Wallet</span>
      </router-link>

      <router-link 
        to="/teams" 
        class="flex flex-col items-center gap-1 transition-all duration-200" 
        :class="[
          $route.path === '/teams' 
            ? 'text-neon-cyan scale-105' 
            : 'text-slate-400 hover:text-slate-200'
        ]"
      >
        <UsersIcon size="18" />
        <span class="text-[9px] font-bold">Team</span>
      </router-link>

      <button 
        @click="isMobileMenuOpen = !isMobileMenuOpen" 
        class="flex flex-col items-center gap-1 transition-all duration-200 cursor-pointer outline-none bg-transparent border-none text-slate-400 hover:text-slate-200" 
        :class="[
          isMobileMenuOpen 
            ? 'text-neon-cyan scale-105' 
            : 'text-slate-400 hover:text-slate-200'
        ]"
      >
        <MenuIcon size="18" />
        <span class="text-[9px] font-bold">Menu</span>
      </button>
    </nav>

    <transition name="fade">
      <div 
        v-if="isMobileMenuOpen" 
        class="fixed inset-0 bg-black/60 z-[100] backdrop-blur-sm transition-opacity duration-300 md:hidden"
        @click="isMobileMenuOpen = false"
      ></div>
    </transition>

    <aside 
      class="fixed top-0 left-0 bottom-0 w-64 bg-[#121623]/95 border-r border-white/5 h-screen py-6 px-4 z-[110] flex flex-col justify-between transform transition-transform duration-300 ease-out md:hidden backdrop-blur-lg shadow-2xl"
      :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="space-y-6">
        <div class="flex items-center justify-between px-2">
          <div class="flex items-center gap-3">
            <img v-if="siteLogo" :src="siteLogo" class="h-8 w-auto object-contain" />
            <div v-else class="w-8 h-8 rounded-lg bg-gradient-to-tr from-neon-pink to-neon-cyan flex items-center justify-center font-black italic text-sm text-black">666</div>
            <h1 class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-neon-cyan to-neon-pink italic">
              {{ siteName }}
            </h1>
          </div>
          <button 
            @click="isMobileMenuOpen = false" 
            class="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-white/5 active:scale-95 transition-all"
            aria-label="Close Menu"
          >
            <XIcon size="18" />
          </button>
        </div>

        <div class="mx-1 p-3 bg-white/5 border border-white/5 rounded-2xl flex items-center justify-between">
          <div class="flex flex-col">
            <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold">Balance</span>
            <span class="text-sm font-black text-neon-cyan">{{ currencySymbol }} {{ auth.user?.balance || '0.00' }}</span>
          </div>
          <button 
            @click="$router.push('/wallet')" 
            class="bg-neon-cyan text-black rounded-xl p-1.5 hover:scale-105 active:scale-95 transition-all cursor-pointer"
          >
            <PlusIcon size="14" class="stroke-[3]" />
          </button>
        </div>

        <nav class="space-y-1.5">
          <router-link 
            v-for="item in navItems" 
            :key="item.path" 
            :to="item.path" 
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 hover:bg-white/5 group"
            :class="[
              $route.path === item.path 
                ? 'text-neon-cyan bg-white/5 border-l-2 border-neon-cyan' 
                : 'text-slate-400 hover:text-slate-100 border-l-2 border-transparent'
            ]"
          >
            <component :is="item.icon" size="18" class="group-hover:scale-105 transition-transform" />
            <span>{{ item.name }}</span>
          </router-link>
        </nav>
      </div>

      <div v-if="auth.user" class="pt-4 border-t border-white/5">
        <div class="flex items-center justify-between px-2">
          <div class="flex items-center gap-3">
            <div v-if="auth.user?.avatar" class="w-9 h-9 rounded-xl border border-neon-cyan/30 overflow-hidden shrink-0">
              <img :src="getAvatarUrl(auth.user.avatar)" class="w-full h-full object-cover" />
            </div>
            <div v-else class="w-9 h-9 rounded-xl bg-neon-cyan/20 border border-neon-cyan/30 flex items-center justify-center font-bold text-neon-cyan animate-pulse shrink-0">
              {{ auth.user.username?.charAt(0).toUpperCase() }}
            </div>
            <div class="flex flex-col">
              <span class="text-xs font-black text-slate-200 truncate max-w-[120px]">{{ auth.user.username }}</span>
              <span class="text-[9px] text-slate-400 uppercase tracking-widest font-semibold">{{ auth.user.is_admin ? 'Admin' : 'Player' }}</span>
            </div>
          </div>
          <button 
            @click="handleLogout" 
            class="p-2 text-slate-400 hover:text-neon-pink hover:bg-neon-pink/10 rounded-xl transition-all cursor-pointer"
            title="Log Out"
          >
            <LogOutIcon size="16" />
          </button>
        </div>
      </div>
    </aside>

    <transition name="fade">
      <div 
        v-if="showInstallPopup" 
        class="fixed bottom-20 right-4 left-4 md:left-auto md:w-96 z-[200] p-5 md:p-6 bg-[#121623]/95 border border-neon-cyan/20 rounded-3xl shadow-[0_0_35px_rgba(0,242,254,0.18)] flex flex-col gap-4 backdrop-blur-lg animate-fade-in"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-neon-cyan/10 border border-neon-cyan/20 flex items-center justify-center text-neon-cyan relative">
              <BellIcon size="20" class="animate-pulse" />
              <span class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-neon-pink shadow-[0_0_6px_#ff007f] animate-ping"></span>
            </div>
            <div class="flex flex-col text-left">
              <h4 class="text-sm font-black uppercase text-white tracking-wider leading-none">Install App &amp; Alert</h4>
              <span class="text-[9px] text-neon-cyan font-bold tracking-widest uppercase mt-1">Get Real-Time Updates</span>
            </div>
          </div>
          <button 
            @click="dismissInstall" 
            class="p-1.5 text-slate-400 hover:text-white rounded-xl hover:bg-white/5 active:scale-95 transition-all"
            aria-label="Close Install Prompt"
          >
            <XIcon size="14" />
          </button>
        </div>

        <p class="text-[11px] sm:text-xs font-semibold leading-relaxed text-slate-400 text-left">
          Install <strong class="text-white italic">666EGame</strong> directly to your home screen. Enable live alerts for approved deposits, completed withdrawals, downline team registrations, and progressive Mega Jackpots!
        </p>

        <div class="flex gap-3 mt-1.5">
          <button 
            @click="dismissInstall" 
            class="flex-1 py-2.5 bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/10 active:scale-95 transition-all text-xs font-black uppercase tracking-wider rounded-xl cursor-pointer text-slate-300 hover:text-white"
          >
            Later
          </button>
          <button 
            @click="installApp" 
            class="flex-1 py-2.5 bg-neon-cyan hover:scale-[1.02] active:scale-95 transition-all text-xs font-black uppercase tracking-wider text-black rounded-xl cursor-pointer shadow-[0_0_15px_rgba(0,242,254,0.3)]"
          >
            Install Now
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { 
  Home as HomeIcon, 
  Gamepad2 as GameIcon,
  Activity as ActivityIcon, 
  Wallet as WalletIcon, 
  User as UserIcon, 
  Trophy as TrophyIcon,
  Plus as PlusIcon,
  LogOut as LogOutIcon,
  Users as UsersIcon,
  Menu as MenuIcon,
  X as XIcon,
  Bell as BellIcon
} from 'lucide-vue-next'
import { useAuthStore } from './store/auth'
import { useAudioStore } from './store/audio'
import ToastComponent from './components/ToastComponent.vue'
import axios from 'axios'

const auth = useAuthStore()
const audio = useAudioStore()
const router = useRouter()
const route = useRoute()

const isGamePage = computed(() => {
  const gamePaths = [
    '/games/chicken',
    '/games/dino-cross',
    '/game/chicken-fight',
    '/game/aviator',
    '/game/tom-jerry',
    '/game/three-patti',
    '/game/chicken-jump',
    '/ludo',
    '/cricket',
    '/cricket3d',
    '/carrom'
  ];
  return gamePaths.includes(route.path) || route.path.startsWith('/pool/');
});

const isMobileMenuOpen = ref(false)

watch(() => route.path, () => {
  isMobileMenuOpen.value = false
  if (isGamePage.value || ['/login', '/register'].includes(route.path)) {
    audio.stopBGM()
  } else {
    audio.playBGM()
  }
})

const siteName = ref('666EGame')
const siteLogo = ref('')
const currencySymbol = ref('Rs.')

const navItems = computed(() => {
  const items = [
    { name: 'Home', path: '/', icon: HomeIcon },
    { name: 'Games', path: '/games', icon: GameIcon },
    { name: 'Tournaments', path: '/tournaments', icon: TrophyIcon },
    { name: 'Activity', path: '/activity', icon: ActivityIcon },
    { name: 'Rankings', path: '/rankings', icon: TrophyIcon },
    { name: 'Teams', path: '/teams', icon: UsersIcon },
    { name: 'Wallet', path: '/wallet', icon: WalletIcon },
    { name: 'Profile', path: '/profile', icon: UserIcon }
  ]
  if (auth.user?.is_super_agent) {
    items.push({ name: 'Super Agent', path: '/super-agent/dashboard', icon: TrophyIcon })
  } else {
    items.push({ name: '⚡ Become SA', path: '/super-agent/apply', icon: TrophyIcon })
  }
  return items
})

const fetchSettings = async () => {
  try {
    const { data } = await axios.get('/api/settings')
    if (data.site_name) {
      siteName.value = data.site_name
    }
    if (data.site_logo) {
      siteLogo.value = `http://localhost:8000/storage/${data.site_logo}`
    }
    if (data.currency_symbol) {
      currencySymbol.value = data.currency_symbol
    }
  } catch (e) {
    console.error('Failed to load site settings', e)
  }
}

const getAvatarUrl = (path) => {
  if (!path) return '';
  return path.startsWith('http') ? path : `http://localhost:8000/storage/${path}`;
}

const handleLogout = async () => {
  await auth.logout()
  router.push('/login')
}

const showInstallPopup = ref(false)
let deferredPrompt = null

onMounted(() => {
  fetchSettings()
  if (auth.isAuthenticated) {
    auth.fetchUser()
  }

  window.addEventListener('click', () => {
    audio.playClick()
  })

  // Play BGM initially if not on game page or login/register
  if (!isGamePage.value && !['/login', '/register'].includes(route.path)) {
    audio.playBGM()
  }

  // Handle PWA custom installation prompts
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt = e
    const dismissed = localStorage.getItem('install_dismissed') === 'true'
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
    if (!dismissed && !isStandalone) {
      showInstallPopup.value = true
    }
  })

  // Register Service Worker & WebPush subscription
  if ('serviceWorker' in navigator && 'PushManager' in window) {
    navigator.serviceWorker.register('/sw.js').then(async (reg) => {
      // Only subscribe if user is logged in
      if (auth.isAuthenticated) {
        try {
          const existingSub = await reg.pushManager.getSubscription()
          if (!existingSub) {
            // Check notification permission
            const permission = Notification.permission
            if (permission === 'granted') {
              await subscribeToPush(reg)
            }
          }
        } catch (e) {
          console.warn('Push subscription check failed:', e)
        }
      }
    }).catch(e => console.warn('SW registration failed:', e))
  }
})

const subscribeToPush = async (reg) => {
  try {
    // Get VAPID public key from server
    const { data } = await axios.get('/api/push/vapid-key')
    const vapidKey = data.vapid_public_key
    if (!vapidKey) return

    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(vapidKey)
    })
    await axios.post('/api/push/subscribe', sub.toJSON())
    console.log('Push subscription registered')
  } catch (e) {
    console.warn('Failed to subscribe to push:', e)
  }
}

const urlBase64ToUint8Array = (base64String) => {
  const padding = '='.repeat((4 - base64String.length % 4) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = window.atob(base64)
  return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)))
}

const installApp = async () => {
  if (!deferredPrompt) return
  showInstallPopup.value = false
  deferredPrompt.prompt()
  const { outcome } = await deferredPrompt.userChoice
  console.log(`PWA Install outcome: ${outcome}`)
  deferredPrompt = null

  // Trigger web notifications permission request immediately
  if ('Notification' in window) {
    const permission = await Notification.requestPermission()
    if (permission === 'granted') {
      new Notification('666EGame Alerts Activated!', {
        body: 'You will now receive instant push alerts for approved transactions, downline team registrations, and mega jackpot increments.',
        icon: siteLogo.value || '/favicon.ico'
      })
      // Register push subscription
      if ('serviceWorker' in navigator && 'PushManager' in window) {
        const reg = await navigator.serviceWorker.ready
        await subscribeToPush(reg)
      }
    }
  }
}

const dismissInstall = () => {
  showInstallPopup.value = false
  localStorage.setItem('install_dismissed', 'true')
}

watch(() => auth.token, (newToken) => {
  if (newToken) {
    auth.fetchUser()
  }
})
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(4px);
}
</style>
