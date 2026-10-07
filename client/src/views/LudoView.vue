<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { initEcho } from '../plugins/echo'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import { ChevronLeft, Coins, Users, Trophy } from 'lucide-vue-next'
import axios from 'axios'
import LudoBoard from '../components/LudoBoard.vue'
import ConfirmModal from '../components/ConfirmModal.vue'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const echo = ref(null)
const channel = ref(null)

const gameState = ref('lobby')
const room = ref(null)
const roomCode = ref('')
const myColor = ref('')
const boardState = ref(null)
const currencySymbol = ref('Rs.')
const localDiceRoll = ref(null)
let localDiceTimer = null

const entryFee = ref(10)
const maxPlayers = ref(4)

// Leave confirmation modal
const leaveModal = ref(false)
const pendingLeaveAction = ref(null)

const fetchSettings = async () => {
    try {
        const { data } = await axios.get('/api/settings')
        if (data.currency_symbol) {
            currencySymbol.value = data.currency_symbol
        }
    } catch (e) {
        console.error("Failed to load settings", e)
    }
}

const joinLobby = async () => {
    try {
        const res = await axios.post('/api/ludo/join', {
            entry_fee: entryFee.value,
            max_players: maxPlayers.value
        })
        room.value = res.data.room
        roomCode.value = res.data.room_code
        gameState.value = room.value.status
        if (room.value.status === 'playing') {
            boardState.value = room.value.game_state
        }
        
        const me = room.value.players.find(p => p.user.id == auth.user.id)
        if (me) myColor.value = me.color
        
        setupEcho()
        auth.fetchUser()
        
        // Polling fallback for matching
        const matchingInterval = setInterval(async () => {
            if (gameState.value !== 'waiting') {
                clearInterval(matchingInterval)
                return
            }
            await syncRoomState()
        }, 3000)

    } catch (err) {
        console.error("Join Error:", err)
        toast.show(err.response?.data?.message || err.message || 'Error joining room', 'error')
    }
}

const cancelMatch = async () => {
    try {
        await axios.post('/api/ludo/cancel', { room_code: roomCode.value })
        gameState.value = 'lobby'
        cleanup()
        auth.fetchUser()
    } catch (err) {
        console.error("Cancel Error:", err)
        toast.show(err.response?.data?.message || err.message || 'Error cancelling match', 'error')
    }
}

const setupEcho = () => {
    const token = localStorage.getItem('token')
    echo.value = initEcho(token)
    
    channel.value = echo.value.join(`ludo-room.${room.value.id}`)
        .here(async (users) => {
            console.log("Joined Ludo channel", users)
            await syncRoomState()
        })
        .joining((user) => {})
    .leaving((user) => {})
    .listen('.ludo.player.joined', (e) => {
        console.log("PlayerJoinedLudo event:", e)
        if (!room.value.players.find(p => p.id === e.player.id)) {
            room.value.players.push(e.player)
            if (room.value.players.length === room.value.max_players) {
                console.log("Room full, syncing state to guarantee start...")
                setTimeout(syncRoomState, 1000)
            }
        }
    })
    .listen('.ludo.updated', (e) => {
        console.log("LudoBoardUpdated event:", e)
        if (e.gameState?.player_left) {
            room.value.players = room.value.players.filter(p => p.user.id != e.gameState.player_left)
            return
        }
        if (e.gameState) {
            gameState.value = 'playing'
            boardState.value = e.gameState
            if (e.gameState.players_map) {
                for (const [color, userId] of Object.entries(e.gameState.players_map)) {
                    if (userId == auth.user.id) {
                        myColor.value = color
                    }
                }
            }
        }
        if (e.gameState?.winner) {
            toast.show(`Game Over! Winner is ${e.gameState.winner}`)
            gameState.value = 'lobby'
            cleanup()
            auth.fetchUser() // Refresh balance
        }
    })
    .listen('.ludo.dice.rolled', (e) => {
        if (localDiceTimer) clearTimeout(localDiceTimer)
        localDiceRoll.value = e.diceValue
        localDiceTimer = setTimeout(() => {
            localDiceRoll.value = null
        }, 1800)
        if (e.turnColor !== myColor.value) {
            toast.show(`${e.turnColor.toUpperCase()} rolled a ${e.diceValue}`)
        }
    })
}

const rollDice = async () => {
    try {
        const { data } = await axios.post('/api/ludo/roll', { room_code: roomCode.value })
        if (data.dice) {
            if (localDiceTimer) clearTimeout(localDiceTimer)
            localDiceRoll.value = data.dice
            localDiceTimer = setTimeout(() => {
                localDiceRoll.value = null
            }, 1800)
        }
        if (data.game_state) {
            boardState.value = data.game_state
        }
        if (data.passed) {
            toast.show(`Rolled a ${data.dice}, but you have no valid moves! Turn passed.`)
        }
    } catch (err) {
        toast.show(err.response?.data?.message || err.message || 'Error rolling dice', 'error')
    }
}

const movePiece = async (index) => {
    try {
        const { data } = await axios.post('/api/ludo/move', { 
            room_code: roomCode.value,
            piece_index: index
        })
        if (data.game_state) {
            boardState.value = data.game_state
        }
    } catch (err) {
        toast.show(err.response?.data?.message || err.message || 'Invalid move', 'error')
    }
}

const syncRoomState = async () => {
    if (!roomCode.value) return
    try {
        const { data } = await axios.get(`/api/ludo/room/${roomCode.value}`)
        if (data.room && data.room.status === 'playing') {
            boardState.value = data.room.game_state
            gameState.value = 'playing'
            if (boardState.value.players_map) {
                for (const [color, userId] of Object.entries(boardState.value.players_map)) {
                    if (userId == auth.user.id) {
                        myColor.value = color
                    }
                }
            }
        }
    } catch (e) {
        console.error("Failed to sync room state", e)
    }
}

const cleanup = () => {
    if (channel.value && echo.value) {
        echo.value.leave(`ludo-room.${room.value.id}`)
    }
}

const handleBack = async () => {
    if (gameState.value === 'playing') {
        pendingLeaveAction.value = async () => {
            try {
                await axios.post('/api/ludo/leave')
                gameState.value = 'lobby'
                cleanup()
                auth.fetchUser()
                router.push('/')
            } catch (e) {
                toast.show('Error leaving match', 'error')
                router.push('/')
            }
        }
        leaveModal.value = true
    } else if (gameState.value === 'waiting') {
        cancelMatch()
        router.push('/')
    } else {
        router.push('/')
    }
}

onMounted(async () => {
    auth.fetchUser()
    fetchSettings()
    try {
        const res = await axios.get('/api/ludo/current')
        if (res.data.room) {
            room.value = res.data.room
            roomCode.value = res.data.room_code
            gameState.value = room.value.status
            if (room.value.status === 'playing') {
                boardState.value = room.value.game_state
            }
            const me = room.value.players.find(p => p.user.id == auth.user.id)
            if (me) myColor.value = me.color
            
            setupEcho()
            toast.show("Reconnected to active game!")
        }
    } catch (err) {
        // ignore
    }
})

onUnmounted(() => {
    cleanup()
    if (localDiceTimer) clearTimeout(localDiceTimer)
})
</script>

<template>
  <div class="min-h-screen bg-[#090a0f] text-white flex flex-col p-4 font-sans relative overflow-hidden">
    <!-- Ambient Background Glows -->
    <div class="absolute w-[500px] h-[500px] rounded-full bg-neon-cyan/5 blur-[120px] -top-40 -left-40 animate-pulse pointer-events-none"></div>
    <div class="absolute w-[500px] h-[500px] rounded-full bg-neon-pink/5 blur-[120px] -bottom-40 -right-40 animate-pulse pointer-events-none"></div>
    
    <!-- Top Header -->
    <header class="fixed top-0 left-0 right-0 z-50 flex justify-between items-center p-4 bg-[#090a0f]/80 backdrop-blur-md border-b border-white/5 shadow-lg">
      <button @click="handleBack" class="p-3 bg-white/5 hover:bg-white/10 rounded-full border border-white/5 active:scale-95 transition-all text-slate-300 hover:text-white cursor-pointer">
        <ChevronLeft size="20" />
      </button>
      <div class="bg-[#121623] px-5 py-2 rounded-2xl border border-white/5 flex items-center gap-2">
        <Coins size="16" class="text-neon-pink" />
        <span class="text-white font-black text-sm">{{ currencySymbol }} {{ Number(auth.user?.balance || 0).toFixed(2) }}</span>
      </div>
    </header>

    <div class="flex-1 relative z-10 flex flex-col mt-20">
      <!-- LOBBY -->
      <div v-if="gameState === 'lobby'" class="flex-1 flex flex-col items-center justify-center p-4">
        <div class="bg-[#121623]/60 border border-white/10 backdrop-blur-md p-8 rounded-3xl w-full max-w-sm shadow-2xl relative overflow-hidden mb-6">
          <div class="absolute -right-6 -top-6 opacity-5 rotate-12 text-neon-pink">
              <Trophy size="120" />
          </div>
          
          <h1 class="text-3xl font-black mb-8 bg-gradient-to-r from-neon-cyan to-neon-pink text-transparent bg-clip-text italic text-center relative z-10 uppercase tracking-widest">Ludo Pro</h1>
          
          <div class="mb-6 relative z-10">
            <label class="block mb-2 text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2"><Coins size="14" class="text-neon-pink"/> Entry Fee (Custom)</label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-neon-cyan font-black">{{ currencySymbol }}</span>
                <input type="number" v-model="entryFee" min="10" class="bg-[#090a0f] border border-white/10 w-full pl-10 pr-4 py-3.5 rounded-xl font-bold outline-none focus:border-neon-cyan/50 focus:shadow-[0_0_15px_rgba(0,242,254,0.15)] text-neon-cyan transition text-sm">
            </div>
          </div>
          
          <div class="mb-8 relative z-10">
            <label class="block mb-2 text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2"><Users size="14" class="text-neon-cyan"/> Mode</label>
            <div class="flex gap-2">
                <button @click="maxPlayers = 2" :class="['flex-1 py-3 rounded-xl font-black uppercase text-xs border transition cursor-pointer', maxPlayers === 2 ? 'bg-neon-cyan/20 border-neon-cyan text-neon-cyan shadow-[0_0_10px_rgba(0,242,254,0.15)]' : 'bg-[#090a0f] border-white/10 text-slate-400']">2 Players</button>
                <button @click="maxPlayers = 4" :class="['flex-1 py-3 rounded-xl font-black uppercase text-xs border transition cursor-pointer', maxPlayers === 4 ? 'bg-neon-cyan/20 border-neon-cyan text-neon-cyan shadow-[0_0_10px_rgba(0,242,254,0.15)]' : 'bg-[#090a0f] border-white/10 text-slate-400']">4 Players</button>
            </div>
          </div>

          <button @click="joinLobby" class="bg-neon-pink text-white border border-neon-pink/30 w-full py-4 rounded-2xl font-black text-lg active:scale-95 transition shadow-[0_0_25px_rgba(255,0,127,0.35)] hover:scale-[1.01] relative z-10 cursor-pointer tracking-wider uppercase italic">
            FIND MATCH
          </button>
        </div>

        <!-- Rules & Guidelines Card -->
        <div class="w-full max-w-sm bg-[#121623]/60 border border-white/10 rounded-3xl p-5 space-y-4 shadow-2xl animate-fade-in text-left">
            <div class="flex items-center gap-2 text-neon-cyan">
                <Trophy class="w-4 h-4 text-neon-cyan animate-pulse" />
                <span class="text-xs font-black uppercase tracking-wider">Tournament Rules & Guidelines</span>
            </div>
            <ul class="space-y-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-relaxed">
                <li class="flex gap-2">
                    <span class="text-neon-pink">★</span>
                    <span><strong>Goal</strong>: Race all 4 of your tokens home before your opponents. First player to do so wins the entire pot!</span>
                </li>
                <li class="flex gap-2">
                    <span class="text-neon-pink">★</span>
                    <span><strong>Token Launch</strong>: A roll of <strong>6</strong> is required to launch a token from the starting zone onto the board track.</span>
                </li>
                <li class="flex gap-2">
                    <span class="text-neon-pink">★</span>
                    <span><strong>Captures & Extra Rolls</strong>: Capturing an opponent's token by landing on their space, or rolling a 6, awards you an **extra roll**!</span>
                </li>
                <li class="flex gap-2">
                    <span class="text-neon-pink">★</span>
                    <span><strong>Surrender Limit</strong>: Leaving an active match results in an automatic forfeit and surrender of your bet amount.</span>
                </li>
            </ul>
        </div>
      </div>

      <!-- WAITING ROOM -->
      <div v-else-if="gameState === 'waiting'" class="flex-1 flex flex-col items-center justify-center">
        <div class="bg-[#121623]/60 border border-white/10 backdrop-blur-md p-8 rounded-3xl w-full max-w-sm text-center shadow-2xl">
          <div class="w-16 h-16 border-4 border-t-neon-cyan border-r-neon-cyan border-b-white/5 border-l-white/5 rounded-full animate-spin mx-auto mb-6 shadow-[0_0_15px_rgba(0,242,254,0.2)]"></div>
          <h2 class="text-xl font-black uppercase tracking-wider mb-2 italic">Waiting for players...</h2>
          <p class="mb-6 text-slate-400 text-xs uppercase tracking-wider">Room Code: <span class="font-black text-neon-cyan bg-[#090a0f] border border-white/5 px-2.5 py-1 rounded-lg font-mono">{{ roomCode }}</span></p>
          
          <div class="flex justify-center gap-3 mb-6">
            <div v-for="p in room.players" :key="p.id" class="w-14 h-14 rounded-full flex items-center justify-center font-black uppercase text-xl shadow-lg border-2 border-white/10 relative" :style="{ backgroundColor: p.color, color: p.color === 'yellow' ? 'black' : 'white' }">
              {{ p.user.username.substring(0,2) }}
              <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-zinc-950"></div>
            </div>
          </div>
          
          <p class="font-black text-neon-cyan mb-6 uppercase tracking-widest text-sm animate-pulse">{{ room.players.length }} / {{ room.max_players }} JOINED</p>

          <button @click="cancelMatch" class="bg-[#090a0f] border border-rose-500/30 text-rose-400 w-full py-3 rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-rose-500/10 transition cursor-pointer">
            CANCEL & REFUND
          </button>
        </div>
      </div>

      <!-- PLAYING -->
      <div v-else-if="gameState === 'playing' && boardState" class="flex-1 flex flex-col w-full max-w-4xl mx-auto justify-center">
          
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center w-full">
              
              <!-- Left Column: Ludo Board -->
              <div class="lg:col-span-7 flex justify-center w-full">
                  <LudoBoard :boardState="boardState" :myColor="myColor" @movePiece="movePiece" />
              </div>

              <!-- Right Column: Scores, Turn, Dice Controls -->
              <div class="lg:col-span-5 flex flex-col gap-4 w-full">
                  
                  <!-- Player Turn Information -->
                  <div class="flex justify-between items-center bg-[#121623]/60 p-4 rounded-2xl border border-white/5 backdrop-blur-md w-full">
                      <div>
                        <p class="text-[9px] text-slate-400 uppercase font-black tracking-wider mb-1">Your Color</p>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded-full shadow-inner border border-white/10" :style="{ backgroundColor: myColor }"></div>
                            <span class="font-black uppercase tracking-wider text-xs">{{ myColor }}</span>
                        </div>
                      </div>
                      <div class="text-right">
                          <p class="text-[9px] text-slate-400 uppercase font-black tracking-wider mb-1">Current Turn</p>
                          <div class="flex items-center gap-2 justify-end">
                              <span class="font-black uppercase tracking-wider text-xs" :style="{ color: boardState.turn_color }">{{ boardState.turn_color }}</span>
                              <div class="w-4 h-4 rounded-full animate-pulse shadow-md" :style="{ backgroundColor: boardState.turn_color }"></div>
                          </div>
                      </div>
                  </div>

                  <!-- Turn & Dice -->
                  <div class="bg-[#121623]/60 backdrop-blur-md p-6 rounded-3xl text-center border border-white/10 shadow-lg w-full flex flex-col items-center justify-center min-h-[220px]">
                    <div v-if="localDiceRoll || boardState.dice_value" class="text-5xl font-black mb-6 bg-[#090a0f] text-white inline-flex w-24 h-24 items-center justify-center rounded-2xl shadow-inner border border-white/10 shadow-[0_0_15px_rgba(0,242,254,0.15)] animate-bounce">
                        {{ localDiceRoll || boardState.dice_value }}
                    </div>
                    <div v-else class="h-24 mb-6 flex items-center justify-center animate-pulse">
                        <span class="text-slate-500 font-black uppercase text-xs tracking-wider">Waiting for roll...</span>
                    </div>

                    <button 
                        v-if="boardState.turn_color === myColor && !boardState.dice_value" 
                        @click="rollDice" 
                        class="bg-neon-pink text-white border border-neon-pink/30 px-8 py-4 rounded-2xl font-black text-lg active:scale-95 transition shadow-[0_0_20px_rgba(255,0,127,0.35)] w-full cursor-pointer uppercase italic tracking-wider hover:scale-[1.01]"
                    >
                        ROLL DICE
                    </button>
                  </div>

              </div>

          </div>

      </div>
    </div>

    <!-- Leave Game Confirmation Modal -->
    <ConfirmModal
      :visible="leaveModal"
      title="Surrender & Leave?"
      message="Leaving an active Ludo match will automatically forfeit your game. You will lose your entry bet. Are you sure?"
      confirmLabel="Surrender"
      cancelLabel="Keep Playing"
      accentColor="#f97316"
      accentColorEnd="#ef4444"
      @confirm="async () => { leaveModal = false; if (pendingLeaveAction) await pendingLeaveAction() }"
      @cancel="() => { leaveModal = false; pendingLeaveAction = null }"
    />
  </div>
</template>
