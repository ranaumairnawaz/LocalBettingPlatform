<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRouter, onBeforeRouteLeave } from 'vue-router'
import { initEcho } from '../plugins/echo'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import { ChevronLeft, Coins, Users, Trophy, Star, AlertCircle } from 'lucide-vue-next'
import axios from 'axios'
import stadiumBg from '../assets/cricket/stadium_new.png'
import batsmanBodyImg from '../assets/cricket/batsman_body.png'
import batsmanBatImg from '../assets/cricket/batsman_bat.png'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const echo = ref(null)
const channel = ref(null)

const gameState = ref('lobby') // lobby, waiting, playing, finished
const match = ref(null)
const roomCode = ref('')
const betAmount = ref(100)
const processing = ref(false)

// Timing Bar State (Using Percentage 0-100 for consistency)
const ballPosition = ref(0) 
const ballDirection = ref(1)
const ballSpeed = ref(2.5) // Speed in percentage per frame
const isSwinging = ref(false)
const swingResult = ref(null)
let animationFrame = null

const currentInnings = computed(() => {
    if (!match.value || !match.value.game_state) return null
    return match.value.game_state.innings[match.value.game_state.current_innings_index]
})

const isMyTurn = computed(() => {
    if (!currentInnings.value || !auth.user) return false
    return currentInnings.value.user_id == auth.user.id
})

const opponentInnings = computed(() => {
    if (!match.value || !match.value.game_state) return null
    return match.value.game_state.innings.find(i => i.user_id != auth.user?.id)
})

const myInnings = computed(() => {
    if (!match.value || !match.value.game_state) return null
    return match.value.game_state.innings.find(i => i.user_id == auth.user?.id)
})

const matchingTimeElapsed = ref(0)
let matchingTimerInterval = null

const startMatchingTimer = () => {
    matchingTimeElapsed.value = 0
    if (matchingTimerInterval) clearInterval(matchingTimerInterval)
    matchingTimerInterval = setInterval(() => {
        if (gameState.value === 'waiting') {
            matchingTimeElapsed.value++
        } else {
            clearInterval(matchingTimerInterval)
        }
    }, 1000)
}

const cancelling = ref(false)
const cancelMatchmaking = async () => {
    cancelling.value = true
    try {
        await axios.post('/api/cricket/cancel-match')
        if (pollInterval) clearInterval(pollInterval)
        if (matchingTimerInterval) clearInterval(matchingTimerInterval)
        
        gameState.value = 'lobby'
        match.value = null
        roomCode.value = ''
        matchingTimeElapsed.value = 0
        
        toast.show('Matchmaking cancelled. Bet amount refunded!', 'info')
        auth.fetchUser()
    } catch (err) {
        toast.show(err.response?.data?.message || 'Error cancelling matchmaking', 'error')
    } finally {
        cancelling.value = false
    }
}

const joinMatch = async () => {
    if (betAmount.value < 10) {
        toast.show('Minimum bet is Rs. 10', 'warning')
        return
    }
    processing.value = true
    try {
        const res = await axios.post('/api/cricket/join', { bet_amount: betAmount.value })
        match.value = res.data.match
        roomCode.value = res.data.room_code
        gameState.value = match.value.status === 'waiting' ? 'waiting' : 'playing'
        setupEcho()
        auth.fetchUser()
        if (gameState.value === 'playing') {
            startTimer()
        } else {
            startPolling()
            startMatchingTimer()
        }
    } catch (err) {
        toast.show(err.response?.data?.message || 'Error joining match', 'error')
    } finally {
        processing.value = false
    }
}

const checkActiveMatch = async () => {
    try {
        const { data } = await axios.get('/api/cricket/active-match')
        if (data.match) {
            match.value = data.match
            roomCode.value = data.match.room_code
            gameState.value = data.match.status === 'waiting' ? 'waiting' : 'playing'
            setupEcho()
            if (gameState.value === 'playing') {
                startTimer()
            } else if (gameState.value === 'waiting') {
                startPolling()
                startMatchingTimer()
            }
        }
    } catch (e) {}
}

let pollInterval = null
const startPolling = () => {
    if (pollInterval) clearInterval(pollInterval)
    pollInterval = setInterval(async () => {
        try {
            const { data } = await axios.get(`/api/cricket/match/${roomCode.value}`)
            match.value = data
            if (data.status === 'playing') {
                gameState.value = 'playing'
                clearInterval(pollInterval)
                if (matchingTimerInterval) clearInterval(matchingTimerInterval)
                startTimer()
                setupEcho()
            }
        } catch (e) {}
    }, 3000)
}

let opponentTurnPollInterval = null
const startOpponentTurnPolling = () => {
    if (opponentTurnPollInterval) clearInterval(opponentTurnPollInterval)
    opponentTurnPollInterval = setInterval(async () => {
        if (gameState.value !== 'playing' || isMyTurn.value) {
            clearInterval(opponentTurnPollInterval)
            return
        }
        try {
            const { data } = await axios.get(`/api/cricket/match/${roomCode.value}`)
            if (data) {
                const oldInnings = match.value?.game_state?.current_innings_index
                match.value = data
                
                if (data.game_state.status === 'finished') {
                    gameState.value = 'finished'
                    stopBallMovement()
                    auth.fetchUser()
                    clearInterval(opponentTurnPollInterval)
                } else {
                    startTimer()
                    if (oldInnings !== undefined && oldInnings !== data.game_state.current_innings_index) {
                        toast.show("Innings Change! Your Turn.", "info")
                        clearInterval(opponentTurnPollInterval)
                        nextTick(() => { if (isMyTurn.value) startBallMovement() })
                    }
                }
            }
        } catch (e) {
            console.error("Error polling opponent turn:", e)
        }
    }, 2000)
}
const remainingTime = ref(10)
let timerInterval = null
const startTimer = () => {
    if (timerInterval) clearInterval(timerInterval)
    
    // Only run timer if it's my turn and I haven't started playing my innings yet (balls_faced == 0)
    if (!isMyTurn.value || !myInnings.value || myInnings.value.balls_faced > 0) {
        remainingTime.value = 10
        return
    }

    const lastAction = match.value?.game_state?.last_action_at || Math.floor(Date.now() / 1000)
    const elapsed = Math.floor(Math.floor(Date.now() / 1000) - lastAction)
    remainingTime.value = Math.max(0, 10 - elapsed)
    
    timerInterval = setInterval(async () => {
        if (gameState.value !== 'playing') return
        // Stop timer immediately if we start playing (balls_faced > 0)
        if (!myInnings.value || myInnings.value.balls_faced > 0) {
            clearInterval(timerInterval)
            return
        }

        remainingTime.value--
        if (remainingTime.value <= 0) {
            clearInterval(timerInterval)
            if (isMyTurn.value) {
                toast.show("Forfeit due to inactivity!", "error")
                const { data } = await axios.get(`/api/cricket/match/${roomCode.value}`)
                match.value = data
            }
        }
    }, 1000)
}

const setupEcho = () => {
    if (!match.value || echo.value) return
    const token = localStorage.getItem('token')
    echo.value = initEcho(token)
    channel.value = echo.value.private(`cricket.${match.value.id}`)
        .listen('.cricket.updated', (e) => {
            console.log("Live Update Received:", e)
            const oldInnings = match.value?.game_state?.current_innings_index
            
            // Critical: Force deep update of the match ref
            match.value = JSON.parse(JSON.stringify({ ...match.value, game_state: e.gameState }))
            
            if (e.gameState.status === 'finished') {
                gameState.value = 'finished'
                stopBallMovement()
                auth.fetchUser()
            } else {
                gameState.value = 'playing'
                startTimer()
                
                // If it's now my turn and ball isn't moving, start it immediately
                if (isMyTurn.value && !animationFrame) {
                    startBallMovement()
                }

                if (oldInnings !== undefined && oldInnings !== e.gameState.current_innings_index) {
                    toast.show("Innings Change! Your Turn.", "info")
                    nextTick(() => { if (isMyTurn.value) startBallMovement() })
                }
            }
        })
}

const startBallMovement = () => {
    if (!isMyTurn.value || gameState.value !== 'playing') return
    stopBallMovement()
    ballPosition.value = 0
    ballDirection.value = 1
    const animate = () => {
        ballPosition.value += ballSpeed.value * ballDirection.value
        if (ballPosition.value >= 100) { ballPosition.value = 100; ballDirection.value = -1; }
        if (ballPosition.value <= 0) { ballPosition.value = 0; ballDirection.value = 1; }
        animationFrame = requestAnimationFrame(animate)
    }
    animationFrame = requestAnimationFrame(animate)
}

const stopBallMovement = () => {
    if (animationFrame) cancelAnimationFrame(animationFrame)
    animationFrame = null
}

const calculateRunsLocal = (timing) => {
    const isOut = (timing < 0.08 || timing > 0.92);
    if (isOut) return 'OUT';
    
    const diff = Math.abs(0.5 - timing);
    if (diff < 0.05) return 6;
    if (diff < 0.15) return 4;
    if (diff < 0.25) return 2;
    if (diff < 0.35) return 1;
    if (diff > 0.45) return 0;
    return 0;
}

const swing = async () => {
    if (isSwinging.value || !isMyTurn.value) return
    isSwinging.value = true
    stopBallMovement()
    
    // Stop the warning timer immediately upon the first swing
    if (timerInterval) clearInterval(timerInterval)

    // Timing is exact percentage / 100
    const timing = ballPosition.value / 100

    // Instant local prediction for 0 delay visual feedback
    const localResult = calculateRunsLocal(timing)
    swingResult.value = localResult
    setTimeout(() => {
        if (swingResult.value === localResult) {
            swingResult.value = null
        }
    }, 2000)

    try {
        const { data } = await axios.post(`/api/cricket/swing/${roomCode.value}`, { timing })
        
        match.value = JSON.parse(JSON.stringify(data.match))
        
        if (data.match.game_state.status === 'finished') {
            gameState.value = 'finished'
            auth.fetchUser()
        } else {
            setTimeout(() => {
                isSwinging.value = false
                if (isMyTurn.value) startBallMovement()
            }, 400)
        }
    } catch (err) {
        toast.show(err.response?.data?.message || 'Swing error', 'error')
        isSwinging.value = false
        startBallMovement()
    }
}

watch([isMyTurn, gameState], ([newTurn, newGameState]) => {
    if (newGameState === 'playing') {
        if (newTurn) {
            stopBallMovement()
            startBallMovement()
            if (opponentTurnPollInterval) clearInterval(opponentTurnPollInterval)
        } else {
            stopBallMovement()
            startOpponentTurnPolling()
        }
    } else {
        stopBallMovement()
        if (opponentTurnPollInterval) clearInterval(opponentTurnPollInterval)
    }
}, { immediate: true })

const triggerForfeit = async () => {
    if (gameState.value === 'playing' && roomCode.value) {
        try {
            await axios.post(`/api/cricket/forfeit/${roomCode.value}`)
        } catch (e) {
            console.error("Error triggering forfeit:", e)
        }
    }
}

const goBack = async () => {
    if (gameState.value === 'playing') {
        const confirmLeave = confirm("Leaving this page will forfeit the match and you will lose your bet! Are you sure you want to go back?")
        if (confirmLeave) {
            await triggerForfeit()
            router.push('/')
        }
    } else {
        router.push('/')
    }
}

onBeforeRouteLeave(async (to, from, next) => {
    if (gameState.value === 'playing') {
        const confirmLeave = confirm("Leaving this page will forfeit the match. Are you sure you want to leave?")
        if (confirmLeave) {
            await triggerForfeit()
            next()
        } else {
            next(false)
        }
    } else {
        next()
    }
})

const handleBeforeUnload = (event) => {
    if (gameState.value === 'playing' && roomCode.value) {
        navigator.sendBeacon(`/api/cricket/forfeit/${roomCode.value}`)
    }
}

onMounted(() => { 
    auth.fetchUser()
    checkActiveMatch()
    window.addEventListener('beforeunload', handleBeforeUnload)
})

onUnmounted(() => { 
    stopBallMovement()
    window.removeEventListener('beforeunload', handleBeforeUnload)
    if (pollInterval) clearInterval(pollInterval)
    if (timerInterval) clearInterval(timerInterval)
    if (opponentTurnPollInterval) clearInterval(opponentTurnPollInterval)
    if (matchingTimerInterval) clearInterval(matchingTimerInterval)
    if (channel.value) channel.value.unsubscribe() 
})
</script>

<template>
    <div class="min-h-screen bg-[#090a0f] text-white flex flex-col font-outfit relative overflow-hidden">
        <!-- Ambient Background Glows -->
        <div class="absolute w-[500px] h-[500px] rounded-full bg-neon-cyan/5 blur-[120px] -top-40 -left-40 animate-pulse pointer-events-none"></div>
        <div class="absolute w-[500px] h-[500px] rounded-full bg-neon-pink/5 blur-[120px] -bottom-40 -right-40 animate-pulse pointer-events-none"></div>

        <!-- Navbar -->
        <div class="p-6 flex items-center justify-between fixed top-0 left-0 right-0 z-[100] bg-[#121623]/80 backdrop-blur-md border-b border-white/5 h-[80px]">
            <button @click="goBack" class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 group transition-all">
                <ChevronLeft class="w-6 h-6 group-hover:-translate-x-1 transition-transform" />
            </button>
            <div class="flex flex-col items-center">
                <h1 class="text-xl font-black italic tracking-tighter uppercase text-neon-cyan">Cricket Pro</h1>
                <p class="text-[10px] text-slate-400 font-bold tracking-[0.3em] uppercase">Super Over League</p>
            </div>
            <div class="bg-[#121623] px-5 py-2 rounded-2xl border border-white/5 flex items-center gap-3">
                <Coins class="w-4 h-4 text-neon-pink" />
                <span class="font-black text-base">{{ Number(auth.user?.balance || 0).toFixed(2) }}</span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 relative z-10 flex flex-col pt-[80px]">
            <!-- Lobby -->
            <div v-if="gameState === 'lobby'" class="flex-1 flex flex-col items-center justify-center p-6 space-y-10 animate-fade-in">
                <div class="text-center space-y-4">
                    <div class="w-28 h-28 bg-neon-pink/15 rounded-[2.5rem] flex items-center justify-center mx-auto border border-neon-pink/20 shadow-[0_0_40px_rgba(255,0,127,0.15)] animate-pulse">
                        <Star class="w-14 h-14 text-neon-pink" />
                    </div>
                    <h2 class="text-4xl font-black italic uppercase tracking-wider text-white">Enter the Arena</h2>
                </div>
                <div class="w-full max-w-sm space-y-6">
                    <div class="relative group">
                        <span class="absolute left-8 top-1/2 -translate-y-1/2 text-neon-cyan font-black text-2xl">Rs.</span>
                        <input type="number" v-model="betAmount" class="w-full bg-[#121623]/60 border-2 border-white/10 rounded-[2.5rem] py-8 pl-24 text-4xl font-black focus:border-neon-cyan/50 focus:shadow-[0_0_20px_rgba(0,242,254,0.15)] text-neon-cyan outline-none transition-all">
                    </div>
                    <button @click="joinMatch" :disabled="processing" class="w-full bg-neon-pink text-white border border-neon-pink/30 hover:scale-[1.02] shadow-[0_0_25px_rgba(255,0,127,0.35)] shadow-[0_10px_0_#99004d] active:translate-y-2 active:shadow-none transition-all font-black py-7 rounded-[2.5rem] text-2xl uppercase italic cursor-pointer tracking-wider">
                        {{ processing ? 'Finding Player...' : 'Join Tournament' }}
                    </button>

                    <!-- Rules & Guidelines Card -->
                    <div class="w-full bg-[#121623]/60 border border-white/10 backdrop-blur-md rounded-3xl p-5 space-y-4 shadow-2xl animate-fade-in text-left">
                        <div class="flex items-center gap-2 text-neon-cyan">
                            <Trophy class="w-4 h-4 text-neon-cyan animate-pulse" />
                            <span class="text-xs font-black uppercase tracking-wider">Tournament Rules & Guidelines</span>
                        </div>
                        <ul class="space-y-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-relaxed">
                            <li class="flex gap-2">
                                <span class="text-neon-pink">★</span>
                                <span><strong>Super Over Format</strong>: 1 Innings of 6 balls per player. Higher score wins the tournament!</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-neon-pink">★</span>
                                <span><strong>Batting Mechanic</strong>: Press "Swing Bat" at the exact moment the ball crosses the timing circle for maximum runs (4s & 6s).</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-neon-pink">★</span>
                                <span><strong>Wickets</strong>: Each wicket lost deducts 5 runs from your total score. Protect your wickets!</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-neon-pink">★</span>
                                <span><strong>Timeout limit</strong>: You have 40 seconds per ball. Failing to play a ball results in an automatic forfeit.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Waiting -->
            <div v-else-if="gameState === 'waiting'" class="flex-1 flex flex-col items-center justify-center p-6 space-y-10 animate-fade-in">
                <div class="w-40 h-40 bg-neon-cyan/15 rounded-[3rem] flex items-center justify-center border border-neon-cyan/20 relative animate-pulse shadow-[0_0_40px_rgba(0,242,254,0.15)]">
                    <Users class="w-20 h-20 text-neon-cyan" />
                </div>
                <div class="text-center space-y-2">
                    <h2 class="text-3xl font-black uppercase italic tracking-wider">Searching...</h2>
                    <p class="text-neon-cyan font-bold text-xs uppercase tracking-widest">Room: {{ roomCode }}</p>
                    <p class="text-slate-400 font-bold text-xs uppercase tracking-widest font-mono">Time elapsed: {{ matchingTimeElapsed }}s</p>
                </div>
                
                <!-- Cancel / Go Back Option after 20 seconds -->
                <div v-if="matchingTimeElapsed >= 20" class="w-full max-w-sm space-y-4 animate-fade-in">
                    <div class="text-center text-rose-400 font-bold text-sm bg-rose-500/10 p-4 rounded-2xl border border-rose-500/10">
                        ⚠️ Taking longer than usual to find an opponent.
                    </div>
                    <button @click="cancelMatchmaking" :disabled="cancelling" class="w-full bg-[#090a0f] border border-rose-500/30 text-rose-400 font-black py-6 rounded-[2.5rem] text-xl shadow-[0_0_20px_rgba(239,68,68,0.1)] active:translate-y-2 active:shadow-none transition-all uppercase italic cursor-pointer">
                        {{ cancelling ? 'Refunding...' : 'Cancel & Go Back' }}
                    </button>
                </div>
            </div>

            <!-- Playing -->
            <div v-else-if="gameState === 'playing'" class="flex-1 flex flex-col lg:grid lg:grid-cols-12 gap-6 items-center justify-center max-w-6xl w-full mx-auto p-4 overflow-hidden">
                <!-- LEFT COLUMN: Game Screen -->
                <div class="lg:col-span-7 flex flex-col gap-6 w-full">
                    <!-- Game Screen -->
                    <div class="relative w-full aspect-video bg-[#121623] rounded-2xl overflow-hidden border-2 border-white/10 shadow-2xl flex-shrink-0">
                        <!-- Stadium BG -->
                        <img :src="stadiumBg" class="absolute inset-0 w-full h-full object-cover opacity-80" alt="Stadium">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/30"></div>

                        <!-- Character Container (Layered) -->
                        <div class="absolute left-[6%] bottom-[-3px] h-[30%] z-10 transition-all duration-500 hover:scale-105">
                            
                            <!-- 1. Batsman Body Layer -->
                            <img :src="batsmanBodyImg" class="h-full w-auto drop-shadow-2xl transition-all duration-500 relative z-10"
                                 :class="isSwinging ? 'animate-body-lean' : ''" alt="Player Body">
                            
                            <!-- 2. Cricket Bat Layer -->
                            <img :src="batsmanBatImg" class="absolute bottom-[0%] left-[0%] h-[100%] w-auto z-20 transition-all duration-500"
                                 :class="isSwinging ? 'animate-bat-swing' : ''"
                                 style="transform-origin: 48% 25%;"
                                 alt="Cricket Bat">
                            
                            <!-- Swoosh Trail Effect -->
                            <div v-if="isSwinging" class="absolute top-[20%] left-[80%] w-32 h-12 bg-gradient-to-r from-neon-cyan/80 to-transparent rounded-full blur-sm rotate-[30deg] animate-swoosh-trail pointer-events-none z-30"></div>
                            
                            <div v-if="isSwinging" class="absolute top-[40%] right-[-50%] w-48 h-48 bg-white/20 rounded-full blur-3xl animate-hit-flash pointer-events-none z-30"></div>
                        </div>

                        <!-- Swing Result Text -->
                        <div v-if="swingResult !== null" class="absolute inset-0 flex items-center justify-center z-50 pointer-events-none">
                            <span class="text-6xl md:text-8xl font-black italic uppercase animate-score-pop text-neon-pink drop-shadow-[0_0_30px_rgba(255,0,127,0.8)]"
                                  :class="swingResult === 'OUT' ? 'text-rose-500' : 'text-neon-cyan'">
                                {{ swingResult }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Scores HUD & Timing Controls -->
                <div class="lg:col-span-5 flex flex-col gap-6 w-full justify-center">
                    <!-- Scores HUD -->
                    <div class="bg-[#121623]/85 backdrop-blur-xl border border-white/10 p-3 rounded-2xl flex items-center justify-between shadow-2xl flex-shrink-0">
                        <div class="flex items-center gap-4">
                            <!-- Player Stats -->
                            <div class="flex flex-col">
                                <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest">YOU</span>
                                <span class="text-3xl font-black text-neon-cyan font-mono italic leading-none">{{ myInnings?.total || 0 }}</span>
                            </div>
                            <div class="h-8 w-[2px] bg-white/10"></div>
                            <div class="flex flex-col">
                                <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest">BALLS</span>
                                <span class="text-xl font-black font-mono italic leading-none text-white">{{ myInnings?.balls_faced || 0 }}/6</span>
                            </div>
                        </div>

                        <!-- Opponent Stats -->
                        <div class="flex items-center gap-4">
                            <div class="flex flex-col items-end">
                                <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest">OPPONENT</span>
                                <span class="text-xl font-black text-neon-pink font-mono italic leading-none">
                                    {{ opponentInnings?.is_done ? opponentInnings.total : 'PLAYING...' }}
                                </span>
                            </div>
                            <div v-if="isMyTurn && myInnings?.balls_faced === 0" class="h-8 w-[2px] bg-white/10"></div>
                            <div v-if="isMyTurn && myInnings?.balls_faced === 0" class="flex flex-col">
                                <span class="text-[9px] text-rose-400 font-black uppercase tracking-widest">TIMEOUT</span>
                                <span class="text-xl font-black text-rose-400 font-mono italic leading-none animate-pulse">{{ remainingTime }}s</span>
                            </div>
                        </div>
                    </div>

                    <!-- Controls Card -->
                    <div class="bg-[#121623]/60 backdrop-blur-xl border border-white/10 p-4 rounded-2xl shadow-xl flex flex-col gap-3 flex-shrink-0">
                        <!-- First Shot Warning Banner -->
                        <div v-if="isMyTurn && myInnings?.balls_faced === 0" class="bg-rose-500/10 border border-rose-500/20 px-3 py-2 rounded-xl flex items-center gap-2 text-rose-400 text-xs font-black animate-pulse">
                            <AlertCircle class="w-4 h-4 flex-shrink-0" />
                            <span class="leading-tight">Play your first shot within {{ remainingTime }} seconds to avoid forfeit!</span>
                        </div>

                        <div v-if="isMyTurn" class="space-y-3">
                            <!-- Timing Bar -->
                            <div class="space-y-1">
                                <div class="flex justify-between text-[8px] font-black text-slate-500 uppercase tracking-widest px-2">
                                    <span class="text-rose-400">Wicket</span>
                                    <span class="text-neon-pink">Sweet Spot (6)</span>
                                    <span class="text-rose-400">Wicket</span>
                                </div>
                                <div class="relative h-6 bg-[#090a0f] rounded-full border border-white/10 overflow-hidden shadow-inner">
                                    <div class="absolute left-[45%] right-[45%] top-0 bottom-0 bg-neon-pink/30 blur-sm"></div>
                                    <div class="absolute left-1/2 top-0 bottom-0 w-[2px] -translate-x-1/2 bg-neon-pink/50"></div>
                                    
                                    <!-- Ball Cursor -->
                                    <div class="absolute top-0 bottom-0 w-6 bg-white shadow-[0_0_20px_#00f2fe] border-2 border-neon-cyan rounded-full transition-all duration-75"
                                         :style="{ left: ballPosition + '%' }"></div>
                                </div>
                            </div>

                            <!-- Swing Button -->
                            <button @click="swing" :disabled="isSwinging"
                                    class="w-full bg-neon-pink text-white border border-neon-pink/30 font-black py-4.5 rounded-xl text-2xl shadow-[0_6px_0_#99004d] shadow-[0_0_20px_rgba(255,0,127,0.3)] active:translate-y-1 active:shadow-none transition-all uppercase italic cursor-pointer tracking-wider">
                                HIT BUTTON
                            </button>
                        </div>
                        <div v-else class="text-center py-5">
                            <p class="text-slate-500 font-black uppercase tracking-[0.2em] animate-pulse text-xs">Opponent's Turn... Watching Replays</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finished -->
            <div v-else-if="gameState === 'finished'" class="absolute inset-0 bg-[#090a0f]/95 z-[200] flex items-center justify-center p-6 backdrop-blur-3xl">
                <div class="w-full max-w-md bg-[#121623]/90 border border-white/10 rounded-[4rem] p-12 text-center space-y-10 shadow-[0_0_50px_rgba(0,0,0,0.8)]">
                    <h2 class="text-6xl font-black italic uppercase tracking-wider"
                        :class="match?.game_state?.winner_id == auth.user?.id ? 'text-neon-cyan shadow-[0_0_30px_rgba(0,242,254,0.3)]' : 'text-neon-pink shadow-[0_0_30px_rgba(255,0,127,0.3)]'">
                        {{ match?.game_state?.winner_id == auth.user?.id ? 'VICTORY' : 'GAME OVER' }}
                    </h2>

                    <div class="bg-black/40 rounded-[2.5rem] p-8 border border-white/5 space-y-6">
                        <div class="flex justify-between items-center">
                            <div class="text-left">
                                <p class="text-[10px] text-slate-500 uppercase font-black">You</p>
                                <p class="text-4xl font-black font-mono italic">{{ myInnings?.total }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-slate-500 uppercase font-black">Opponent</p>
                                <p class="text-4xl font-black text-slate-400 font-mono italic">{{ opponentInnings?.total }}</p>
                            </div>
                        </div>
                        <div v-if="match?.game_state?.winner_id == auth.user?.id || match?.game_state?.is_draw" class="pt-6 border-t border-white/5">
                            <div class="text-5xl font-black text-neon-cyan font-mono italic">Rs. {{ Number(match?.game_state?.payout || 0).toFixed(2) }}</div>
                        </div>
                    </div>
                    <button @click="router.push('/')" class="w-full bg-neon-cyan text-black py-7 rounded-[2.5rem] font-black text-2xl uppercase italic tracking-wider cursor-pointer shadow-[0_0_20px_rgba(0,242,254,0.3)] hover:scale-105 transition-all">Exit Home</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap');
.font-outfit { font-family: 'Outfit', sans-serif; }
.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes body-lean {
    0% {
        transform: scale(1) rotate(0deg) translateX(0px);
    }
    15% {
        /* Backlift wind-up: shift weight back slightly */
        transform: scale(0.97) rotate(-3deg) translateX(-8px) translateY(3px);
    }
    35% {
        /* Impact: Lean forward for power */
        transform: scale(1.05) rotate(5deg) translateX(15px) translateY(-2px);
    }
    55% {
        /* Follow through hold */
        transform: scale(1.04) rotate(6deg) translateX(18px) translateY(-1px);
    }
    100% {
        /* Return to original stance */
        transform: scale(1) rotate(0deg) translateX(0px) translateY(0px);
    }
}
@keyframes bat-swing {
    0% {
        transform: rotate(0deg) translate(0px, 0px) scale(1);
    }
    15% {
        /* Backlift / Wind-up: Lift and pull backward (clockwise pullback) */
        transform: rotate(20deg) translate(15px, -10px) scale(0.98);
    }
    35% {
        /* Explosive Impact: The powerful down-swing (anticlockwise swing) */
        transform: rotate(-65deg) translate(-15px, 10px) scale(1.02);
    }
    55% {
        /* Follow-through holding pose */
        transform: rotate(-60deg) translate(-12px, 8px) scale(1.01);
    }
    100% {
        /* Return to stance */
        transform: rotate(0deg) translate(0px, 0px) scale(1);
    }
}


@keyframes hit-flash { 0% { transform: scale(0); opacity: 1; } 100% { transform: scale(3); opacity: 0; } }
@keyframes score-pop { 0% { transform: scale(0.5); opacity: 0; } 30% { transform: scale(1.2); opacity: 1; } 100% { transform: scale(1) translateY(-250px); opacity: 0; } }
@keyframes swoosh-trail {
    0% {
        transform: scaleX(0) translateX(0);
        opacity: 0;
    }
    20% {
        transform: scaleX(0) translateX(0);
        opacity: 0;
    }
    35% {
        transform: scaleX(1.5) translateX(50px);
        opacity: 1;
    }
    70% {
        transform: scaleX(2) translateX(100px);
        opacity: 0;
    }
    100% {
        opacity: 0;
    }
}

.animate-bat-swing { animation: bat-swing 0.8s cubic-bezier(0.25, 1, 0.5, 1) forwards; }
.animate-hit-flash { animation: hit-flash 0.4s ease-out forwards; }
.animate-score-pop { animation: score-pop 1.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
.animate-swoosh-trail { animation: swoosh-trail 0.8s ease-out forwards; }
::-webkit-scrollbar { display: none; }
</style>
