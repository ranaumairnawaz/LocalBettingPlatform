<script setup>
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import { ArrowLeft, Plus, Coins, Trophy, ChevronRight, AlertCircle, Timer, Gamepad2 } from 'lucide-vue-next'
import axios from 'axios'
import { checkBarrierCollision, checkDinoCollision, BARRIER_Y, DINO_Y } from '../utils/collision'
import { createRenderer } from '@/pixi/renderer'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const gameTitle = 'Dino Road Cross'
const betAmount = ref(100)
const gameActive = ref(false)
const currentLane = ref(0)
const visualLane = ref(0) // Tracks Dino position for immediate UI feedback
const multipliers = [
  1.15, 1.25, 1.38, 1.52, 1.68, 1.86, 2.05, 2.28, 2.54, 2.85,
  3.20, 3.60, 4.10, 4.70, 5.45, 6.35, 7.45, 8.85, 10.60, 12.85,
  15.85, 20.00, 30.00, 50.00
]

const pixiRenderer = ref(null)
const logId = ref(null)
const isCrashed = ref(false)
const pendingDeath = ref(false) // Triggered by server crash, removes barrier to allow collision
const crashingLane = ref(null)
const safeBarriers = ref([]) // Tracks lanes where barriers appear
const processing = ref(false)

// Traffic System (Bottom-to-Top)
const laneCars = ref({}) // { laneIndex: [cars] }
let trafficLoop = null

const spawnCar = (laneIndex) => {
  if (!laneCars.value[laneIndex]) laneCars.value[laneIndex] = []
  
  if (laneCars.value[laneIndex].length < 2) {
    laneCars.value[laneIndex].push({
      id: Math.random(),
      y: 120, // Start below screen
      speed: 0.8 + Math.random() * 0.5,
      type: Math.random() > 0.5 ? 'sedan' : 'bus',
      colorIndex: Math.floor(Math.random() * 4)
    })
  }
}

const updateTraffic = () => {
  // CRITICAL FIX: If the component unmounts or Pixi is cleared, exit loop immediately
  if (!pixiRenderer.value) {
    if (trafficLoop) cancelAnimationFrame(trafficLoop)
    return
  }

  if (!gameActive.value && !isCrashed.value && !pendingDeath.value) {
    trafficLoop = requestAnimationFrame(updateTraffic)
    return
  }

  // Unique active lanes to avoid double-processing
  const activeLanes = [...new Set([visualLane.value, crashingLane.value].filter(l => l > 0))]
  
  activeLanes.forEach(laneIdx => {
    if (!laneCars.value[laneIdx]) {
      laneCars.value[laneIdx] = []
      spawnCar(laneIdx)
    }

    const barrierActive = safeBarriers.value.includes(laneIdx) && !isCrashed.value
    
    laneCars.value[laneIdx].forEach(car => {
      const oldY = car.y
      const collision = checkBarrierCollision(car.y, car.speed, barrierActive)
      car.y = collision.nextY

      // Check collision with Dino if pending death
      if (laneIdx === crashingLane.value && pendingDeath.value && !isCrashed.value) {
        if (checkDinoCollision(oldY, car.y, DINO_Y, false)) {
          console.log("Collision detected at Y:", car.y)
          isCrashed.value = true 
          pendingDeath.value = false
          startAnimation('dead')
        }
      }
    })

    // Remove off-screen cars and spawn new ones
    laneCars.value[laneIdx] = laneCars.value[laneIdx].filter(car => car.y > -20)
    if (laneCars.value[laneIdx].length < 2 && Math.random() < 0.02) {
      spawnCar(laneIdx)
    }
  })

  // Sync with Pixi layout context safely
  if (pixiRenderer.value) {
    pixiRenderer.value.syncCars(laneCars.value)
    pixiRenderer.value.syncBarriers(safeBarriers.value)
    trafficLoop = requestAnimationFrame(updateTraffic)
  }
}

// Game Layout Constants
const VISIBLE_LANES = 5
const LANE_WIDTH_SCREEN = 100 / VISIBLE_LANES // 20%
const TOTAL_COLUMNS = 25 // 1 Start + 24 Lanes

const roadScrollOffset = ref(0)
const dinoScreenX = computed(() => {
  const trackPosition = (visualLane.value * LANE_WIDTH_SCREEN) + (LANE_WIDTH_SCREEN / 2)
  return trackPosition - roadScrollOffset.value
})

// Keep Pixi Dino sprite in sync with visual position and handle scroll offset
watch(visualLane, (lane) => {
  roadScrollOffset.value = Math.max(0, (lane - 2) * LANE_WIDTH_SCREEN)
  if (pixiRenderer.value) {
    pixiRenderer.value.setDinoPosition(lane)
  }
})

watch(isCrashed, (val) => {
  if (val) {
    safeBarriers.value = []
    if (pixiRenderer.value) {
      pixiRenderer.value.syncBarriers([])
      pixiRenderer.value.triggerDeath()
    }
  }
})

watch(pendingDeath, (val) => {
  if (pixiRenderer.value) {
    pixiRenderer.value.setPendingDeath(val, crashingLane.value)
  }
})

// Dino Animation State
const dinoState = ref('idle') 
const dinoFrame = ref(1)
let animationInterval = null

const animations = {
  idle: { frames: 10, folder: 'idle', prefix: 'Idle' },
  jump: { frames: 12, folder: 'jump', prefix: 'Jump' },
  dead: { frames: 8, folder: 'dead', prefix: 'Dead' }
}

const getDinoImage = () => {
  const anim = animations[dinoState.value]
  return `/assets/dino/${anim.folder}/${anim.prefix} (${dinoFrame.value}).png`
}

const startAnimation = (state) => {
  if (animationInterval) clearInterval(animationInterval)
  dinoState.value = state
  dinoFrame.value = 1
  
  const speed = state === 'jump' ? 40 : 80
  animationInterval = setInterval(() => {
    const anim = animations[dinoState.value]
    dinoFrame.value++
    if (dinoFrame.value > anim.frames) {
      if (dinoState.value === 'dead') {
        dinoFrame.value = anim.frames 
        clearInterval(animationInterval)
      } else if (dinoState.value === 'jump') {
        startAnimation('idle') 
      } else {
        dinoFrame.value = 1
      }
    }
  }, speed)
}

const startGame = async () => {
  if (betAmount.value < 10) {
    toast.show('Minimum bet is Rs. 10', 'warning')
    return
  }
  if (auth.user.balance < betAmount.value) {
    toast.show('Insufficient balance', 'error')
    return
  }

  processing.value = true
  try {
    const { data } = await axios.post('/api/games/dino/start', { amount: betAmount.value })
    logId.value = data.log_id
    gameActive.value = true
    currentLane.value = 0
    visualLane.value = 0
    isCrashed.value = false
    pendingDeath.value = false
    crashingLane.value = null
    safeBarriers.value = []
    laneCars.value = {}
    roadScrollOffset.value = 0
    auth.fetchUser()
    startAnimation('idle')
    if (pixiRenderer.value) {
      pixiRenderer.value.resetGame()
    }
    if (trafficLoop) cancelAnimationFrame(trafficLoop)
    updateTraffic()
  } catch (e) {
    toast.show(e.response?.data?.message || 'Error starting game', 'error')
  } finally {
    processing.value = false
  }
}

const moveDino = async () => {
  if (!gameActive.value || processing.value || isCrashed.value) return

  processing.value = true
  visualLane.value++
  startAnimation('jump')
  
  try {
    const { data } = await axios.post('/api/games/dino/move', { log_id: logId.value })
    
    if (data.status === 'success') {
      currentLane.value = data.current_lane
      safeBarriers.value.push(data.current_lane)
      if (visualLane.value !== currentLane.value) {
        visualLane.value = currentLane.value
      }
    } else if (data.status === 'crash') {
      crashingLane.value = data.current_lane + 1
      visualLane.value = crashingLane.value 
      gameActive.value = false
      pendingDeath.value = true 
      
      if (!laneCars.value[crashingLane.value]) laneCars.value[crashingLane.value] = []
      laneCars.value[crashingLane.value].push({
        id: 'executioner-' + Date.now(),
        y: DINO_Y + 15, 
        speed: 2.5,     
        type: 'bus',
        colorIndex: 0
      })
      
      toast.show('Oh no! Watch out!', 'error')
    }
  } catch (e) {
    toast.show(e.response?.data?.message || 'Error moving', 'error')
    visualLane.value = currentLane.value 
    startAnimation('idle')
  } finally {
    processing.value = false
  }
}

const cashout = async () => {
  if (!gameActive.value || processing.value || currentLane.value === 0) return

  processing.value = true
  try {
    const { data } = await axios.post('/api/games/dino/cashout', { log_id: logId.value })
    gameActive.value = false
    visualLane.value = currentLane.value
    toast.show(`YAY! Dino cashed out Rs. ${data.payout}!`, 'success')
    auth.fetchUser()
    startAnimation('idle')
  } catch (e) {
    toast.show(e.response?.data?.message || 'Error cashing out', 'error')
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  startAnimation('idle')
  pixiRenderer.value = createRenderer('pixiContainer')
  pixiRenderer.value.onCrash = () => {
    isCrashed.value = true
    gameActive.value = false
    startAnimation('dead')
  }
  updateTraffic() 
})

onUnmounted(() => {
  // Clear game loops explicitly to avoid canvas lockup errors
  if (animationInterval) clearInterval(animationInterval)
  if (trafficLoop) {
    cancelAnimationFrame(trafficLoop)
    trafficLoop = null
  }
  if (pixiRenderer.value) {
    if (typeof pixiRenderer.value.destroy === 'function') {
      pixiRenderer.value.destroy(true)
    }
    pixiRenderer.value = null
  }
  // Clear states
  gameActive.value = false
  isCrashed.value = false
  pendingDeath.value = false
})
</script>

<template>
  <div class="min-h-screen bg-[#87CEEB] text-zinc-900 flex flex-col font-outfit overflow-x-hidden pb-12 relative">
    <div class="absolute top-20 left-10 w-24 h-12 bg-white rounded-full opacity-60 blur-sm animate-[float_8s_ease-in-out_infinite]"></div>
    <div class="absolute top-40 right-20 w-32 h-16 bg-white rounded-full opacity-40 blur-sm animate-[float_12s_ease-in-out_infinite_reverse]"></div>
    <div class="absolute top-10 right-1/4 w-20 h-10 bg-white rounded-full opacity-50 blur-sm animate-[float_10s_ease-in-out_infinite]"></div>

    <header class="w-full bg-white/90 backdrop-blur-md border-b-4 border-emerald-500 py-4 px-6 flex justify-between items-center fixed top-0 left-0 right-0 z-50 shadow-lg">
      <div class="flex items-center gap-4">
        <button @click="router.push('/')" class="p-2.5 bg-emerald-100 hover:bg-emerald-200 rounded-2xl transition-all text-emerald-700 group border-b-4 border-emerald-300 active:border-b-0 active:translate-y-1">
          <ArrowLeft size="24" class="group-hover:-translate-x-1 transition-transform" stroke-width="3" />
        </button>
        <div class="flex flex-col">
          <h1 class="text-2xl font-black italic tracking-tighter text-emerald-600 uppercase drop-shadow-sm">{{ gameTitle }}</h1>
          <span class="text-[10px] text-emerald-500 font-bold uppercase tracking-[0.2em]">Cartoon Dino Adventure</span>
        </div>
      </div>

      <div class="bg-yellow-100 border-4 border-yellow-400 px-5 py-2 rounded-2xl flex items-center gap-4 shadow-md">
        <div class="flex flex-col items-end">
          <span class="text-[10px] text-yellow-700 font-black uppercase tracking-wider">YOUR COINS</span>
          <span class="text-yellow-600 font-black text-xl">Rs. {{ parseFloat(auth.user?.balance || 0).toFixed(2) }}</span>
        </div>
        <button @click="router.push('/wallet')" class="bg-yellow-400 text-yellow-900 hover:bg-yellow-300 rounded-xl p-2 transition-all border-b-4 border-yellow-600 active:border-b-0 active:translate-y-1">
          <Plus size="24" stroke-width="3" />
        </button>
      </div>
    </header>

    <div class="max-w-6xl w-full mx-auto p-4 flex-1 flex flex-col gap-8 mt-24">
      <div class="w-full flex flex-col lg:grid lg:grid-cols-12 gap-8 items-start">
        
        <div class="lg:col-span-8 flex flex-col gap-6 w-full">
          <div class="relative w-full bg-[#556B2F] rounded-[4rem] p-4 md:p-10 border-[12px] border-[#3E4D22] shadow-[0_20px_0_#2A3517] overflow-hidden">
            <div class="w-full relative overflow-hidden h-80 sm:h-[28rem] rounded-[3rem] bg-[#a8e063] border-8 border-[#3E4D22] shadow-inner">
              
              <div 
                class="relative flex items-stretch h-full transition-transform duration-[400ms] ease-[cubic-bezier(0.34,1.56,0.64,1)] w-[500%]"
                :style="{ 
                  transform: `translateX(${-roadScrollOffset / 5}%)`,
                  backgroundImage: 'url(/assets/road.jpg)',
                  backgroundRepeat: 'repeat-x',
                  backgroundSize: 'auto 150%'
                }"
              >
                <div class="w-[4%] bg-[#7CFC00] border-r-8 border-dashed border-white/40 relative flex flex-col items-center justify-center">
                  <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/grass.png')]"></div>
                  <div class="absolute top-10 left-4 w-4 h-4 bg-pink-400 rounded-full border-2 border-pink-600 animate-pulse"></div>
                  <div class="absolute bottom-20 right-4 w-5 h-5 bg-yellow-400 rounded-full border-2 border-yellow-600 animate-pulse" style="animation-delay: 0.5s"></div>
                  <span class="text-emerald-800 font-black tracking-[0.5em] -rotate-90 uppercase whitespace-nowrap text-lg drop-shadow-sm">START</span>
                </div>

                <div 
                  v-for="(mult, index) in multipliers" 
                  :key="index"
                  class="w-[4%] border-r-8 border-[#2f2f2f] relative flex flex-col items-center justify-between py-12 overflow-hidden"
                  :class="[
                    currentLane > index + 1 ? 'bg-black/50' : 
                    currentLane === index + 1 ? 'bg-transparent' : 'bg-black/25'
                  ]"
                >
                  <div class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-2 flex flex-col justify-around gap-12 opacity-50">
                    <div v-for="i in 10" :key="i" class="w-2 h-16 bg-white rounded-full"></div>
                  </div>

                  <div 
                    class="w-16 h-16 rounded-[1.5rem] flex flex-col items-center justify-center z-30 transition-all duration-500 relative group/pad"
                    :class="[
                      currentLane > index + 1 ? 'bg-emerald-600 border-b-4 border-emerald-800 text-emerald-100' :
                      currentLane === index + 1 ? 'bg-yellow-400 border-b-8 border-yellow-600 text-yellow-900 scale-125 shadow-[0_0_40px_rgba(255,215,0,0.6)]' :
                      'bg-zinc-700 border-b-4 border-zinc-900 text-zinc-400'
                    ]"
                  >
                    <span class="text-sm font-black">{{ mult }}x</span>
                    <div v-if="currentLane === index + 1" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-white animate-ping"></div>
                  </div>

                  <div 
                    v-if="crashingLane === index + 1"
                    class="absolute inset-0 flex items-center justify-center z-40"
                  >
                    <div class="relative">
                      <div class="w-24 h-24 bg-orange-500 rounded-full animate-ping opacity-50"></div>
                      <div class="absolute inset-0 flex items-center justify-center text-5xl animate-bounce">💥</div>
                    </div>
                  </div>
                </div>

                <div id="pixiContainer" class="absolute inset-0 w-full h-full pointer-events-none z-20"></div>
              </div>

            </div>
          </div>
        </div>

        <div class="lg:col-span-4 flex flex-col gap-6 w-full">
          <div class="bg-white border-b-8 border-zinc-200 rounded-[3.5rem] p-8 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-400/10 rounded-full -mr-16 -mt-16"></div>
            
            <div v-if="!gameActive && !isCrashed" class="flex items-center gap-4 mb-8 relative z-10">
              <div class="p-3.5 bg-yellow-400 rounded-2xl border-b-4 border-yellow-600">
                <Coins class="w-7 h-7 text-yellow-900" />
              </div>
              <h2 class="text-2xl font-black italic uppercase text-zinc-800 tracking-tighter">GAME SETUP</h2>
            </div>

            <div class="space-y-8 relative z-10">
              <div v-if="!gameActive && !isCrashed">
                <label class="text-xs font-black text-zinc-400 uppercase tracking-[0.2em] mb-4 block ml-2">STAKE AMOUNT</label>
                <div class="relative">
                  <span class="absolute left-6 top-1/2 -translate-y-1/2 text-yellow-600 font-black text-xl">Rs.</span>
                  <input 
                    type="number" 
                    v-model="betAmount" 
                    :disabled="gameActive"
                    class="w-full bg-zinc-100 border-4 border-zinc-200 rounded-3xl py-6 pl-16 pr-6 text-3xl font-black text-zinc-800 focus:border-yellow-400 transition-all outline-none"
                  />
                </div>
                <div class="flex flex-wrap gap-2 mt-4">
                  <button 
                    v-for="val in [100, 500, 1000, 5000]" 
                    :key="val"
                    @click="betAmount = val"
                    :disabled="gameActive"
                    class="flex-1 min-w-[70px] py-3 rounded-2xl border-b-4 border-zinc-300 bg-zinc-100 text-zinc-600 font-black hover:bg-zinc-200 transition-all text-sm active:border-b-0 active:translate-y-1"
                  >
                    {{ val }}
                  </button>
                </div>
              </div>

              <div class="pt-4 space-y-4">
                <button 
                  v-if="!gameActive"
                  @click="startGame" 
                  :disabled="processing"
                  class="w-full bg-emerald-500 text-white py-7 rounded-[2rem] font-black text-2xl uppercase italic tracking-wider hover:bg-emerald-400 border-b-8 border-emerald-700 active:border-b-0 active:translate-y-2 transition-all shadow-xl shadow-emerald-500/20 disabled:opacity-50"
                >
                  {{ processing ? '...' : (isCrashed ? 'PLAY AGAIN' : 'PLAY NOW!') }}
                </button>

                <template v-else>
                  <button 
                    @click="moveDino" 
                    :disabled="processing || isCrashed"
                    class="w-full bg-yellow-400 text-yellow-900 py-7 rounded-[2rem] font-black text-2xl uppercase italic tracking-wider hover:bg-yellow-300 border-b-8 border-yellow-600 active:border-b-0 active:translate-y-2 transition-all shadow-xl shadow-yellow-400/20 disabled:opacity-50 flex items-center justify-center gap-4"
                  >
                    <span>HOP FORWARD!</span>
                    <ChevronRight class="w-8 h-8" stroke-width="4" />
                  </button>

                  <button 
                    @click="cashout" 
                    :disabled="processing || currentLane === 0"
                    class="w-full bg-white text-emerald-600 border-4 border-emerald-500 py-6 rounded-[2rem] font-black text-xl uppercase italic tracking-wider hover:bg-emerald-50 active:translate-y-1 transition-all disabled:opacity-50"
                  >
                    CASHOUT {{ multipliers[currentLane - 1] || 1 }}x
                  </button>
                </template>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="bg-white border-b-8 border-zinc-200 rounded-3xl p-5 text-center">
              <span class="text-[10px] font-black text-zinc-400 uppercase tracking-widest block mb-1">HOPS</span>
              <span class="text-3xl font-black text-zinc-800">{{ currentLane }}<span class="text-lg text-zinc-400">/24</span></span>
            </div>
            <div class="bg-white border-b-8 border-zinc-200 rounded-3xl p-5 text-center">
              <span class="text-[10px] font-black text-zinc-400 uppercase tracking-widest block mb-1">PRIZE</span>
              <span class="text-3xl font-black text-emerald-500">{{ multipliers[currentLane - 1] || '1.00' }}x</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateX(0) translateY(0); }
  50% { transform: translateX(20px) translateY(-10px); }
}

@keyframes traffic-down {
  0% { transform: translateY(-150%); }
  100% { transform: translateY(150%); }
}

@keyframes traffic-up {
  0% { transform: translateY(150%); }
  100% { transform: translateY(-150%); }
}

.animate-traffic-down {
  animation: traffic-down linear infinite;
}

.animate-traffic-up {
  animation: traffic-up linear infinite;
}

.animate-fade-out {
  animation: fadeOut 0.5s forwards;
}

@keyframes fadeOut {
  to { opacity: 0; transform: translateY(-20px); }
}

.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>