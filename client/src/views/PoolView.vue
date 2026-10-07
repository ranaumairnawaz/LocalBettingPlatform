<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { initEcho } from '../plugins/echo'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import { ChevronLeft, Coins, Users, Trophy, Star, AlertCircle, Play, ArrowRight, Sliders } from 'lucide-vue-next'
import axios from 'axios'
import Matter from 'matter-js'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const echo = ref(null)
const channel = ref(null)

const gameType = computed(() => route.params.slug || 'pool_1v1')
const gameName = computed(() => gameType.value === 'pool_snooker' ? 'Championship Snooker' : '8 Ball Pool 1v1')

const gameState = ref('lobby') // lobby, waiting, playing, finished
const match = ref(null)
const roomCode = ref('')
const betAmount = ref(100)
const processing = ref(false)

// Matter.js & Canvas Variables
const canvasRef = ref(null)
let engine = null
let world = null
let runner = null
let renderLoop = null

// Game Bodies
let cueBallBody = null
let ballBodies = []
let pocketSensors = []

// Cue Aiming Mechanics
const isAiming = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const dragCurrent = ref({ x: 0, y: 0 })
const maxDragDistance = 120
const isPhysicsRunning = ref(false)
const pocketedThisTurn = ref([])
const aimAngle = ref(0)
const powerPosition = ref(0)
const hasPlayedThisTurn = ref(false)
const isOriginalShooter = ref(false)

// Queued ball positions to apply when physics stops (set when final state arrives during simulation)
let pendingBallsSync = null

// Matchmaking Queue & Snooker Countdown
const matchingTimeElapsed = ref(0)
const currencySymbol = ref('Rs.')
let matchingTimerInterval = null
const countdownTime = ref(0)
let countdownInterval = null

const fetchSettings = async () => {
    try {
        const { data } = await axios.get('/api/settings')
        if (data.currency_symbol) {
            currencySymbol.value = data.currency_symbol
        }
    } catch (e) {
        console.error('Failed to fetch settings:', e)
    }
}

const startMatchingTimer = () => {
    matchingTimeElapsed.value = 0
    if (matchingTimerInterval) clearInterval(matchingTimerInterval)
    matchingTimerInterval = setInterval(() => {
        if (gameState.value === 'waiting') {
            matchingTimeElapsed.value++
            // Check if there is a countdown active in match game_state
            if (match.value?.game_state?.countdown_expires_at) {
                const nowSec = Math.floor(Date.now() / 1000)
                const exp = match.value.game_state.countdown_expires_at
                countdownTime.value = Math.max(0, exp - nowSec)
                
                if (countdownTime.value <= 0) {
                    // Trigger countdown check via polling
                    fetchMatchState()
                }
            } else {
                countdownTime.value = 0
            }
        } else {
            clearInterval(matchingTimerInterval)
        }
    }, 1000)
}

const cancelling = ref(false)
const cancelMatchmaking = async () => {
    cancelling.value = true
    try {
        await axios.post('/api/pool/cancel-match')
        if (pollInterval) clearInterval(pollInterval)
        if (matchingTimerInterval) clearInterval(matchingTimerInterval)
        
        gameState.value = 'lobby'
        match.value = null
        roomCode.value = ''
        matchingTimeElapsed.value = 0
        countdownTime.value = 0
        
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
        const res = await axios.post('/api/pool/join', { 
            bet_amount: betAmount.value,
            game_type: gameType.value
        })
        match.value = res.data.match
        roomCode.value = res.data.room_code
        gameState.value = match.value.status === 'waiting' ? 'waiting' : 'playing'
        setupEcho()
        auth.fetchUser()
        if (gameState.value === 'playing') {
            nextTick(() => {
                initPhysics()
                nextTick(() => {
                    // If it's not my turn at game start, poll for opponent shots
                    if (!isMyTurn.value) startOpponentTurnPolling()
                })
            })
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
        const { data } = await axios.get('/api/pool/active-match')
        if (data.match) {
            match.value = data.match
            roomCode.value = data.match.room_code
            gameState.value = data.match.status === 'waiting' ? 'waiting' : 'playing'
            setupEcho()
            if (gameState.value === 'playing') {
                nextTick(() => {
                    initPhysics()
                    nextTick(() => {
                        if (!isMyTurn.value) startOpponentTurnPolling()
                    })
                })
                startTimer()
            } else if (gameState.value === 'waiting') {
                startPolling()
                startMatchingTimer()
            }
        }
    } catch (e) {}
}

const fetchMatchState = async () => {
    try {
        const { data } = await axios.get(`/api/pool/match/${roomCode.value}`)
        match.value = data
        if (data.status === 'playing') {
            gameState.value = 'playing'
            if (pollInterval) clearInterval(pollInterval)
            if (matchingTimerInterval) clearInterval(matchingTimerInterval)
            nextTick(() => {
                initPhysics()
                nextTick(() => {
                    if (!isMyTurn.value) startOpponentTurnPolling()
                })
            })
            startTimer()
            setupEcho()
        }
    } catch (e) {}
}

let pollInterval = null
const startPolling = () => {
    if (pollInterval) clearInterval(pollInterval)
    pollInterval = setInterval(fetchMatchState, 2500)
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
            const { data } = await axios.get(`/api/pool/match/${roomCode.value}`)
            if (data) {
                const oldState = match.value?.game_state
                
                if (data.game_state.status === 'finished') {
                    match.value = data
                    gameState.value = 'finished'
                    auth.fetchUser()
                    clearInterval(opponentTurnPollInterval)
                } else {
                    const serverTime = data.game_state?.last_action_at || 0
                    const localTime = match.value?.game_state?.last_action_at || 0

                    if (data.game_state.last_shot && (!oldState?.last_shot || oldState.last_shot.timestamp !== data.game_state.last_shot.timestamp)) {
                        match.value = data
                        // Guard: only simulate if not the original shooter
                        if (data.game_state.last_shot.user_id != auth.user?.id && !isOriginalShooter.value) {
                            simulateOpponentShot(data.game_state.last_shot)
                        }
                    } else if (!data.game_state.last_shot && serverTime >= localTime) {
                        if (!isPhysicsRunning.value) {
                            syncBallPositions(data.game_state.balls)
                        } else {
                            pendingBallsSync = data.game_state.balls
                        }
                        match.value = data
                    }
                    startTimer()
                }
            }
        } catch (e) {
            console.error("Error polling opponent turn:", e)
        }
    }, 1500)  // 1500ms polling — less aggressive, reduces double-fire chance
}

const remainingTime = ref(60)
let timerInterval = null
const startTimer = () => {
    if (timerInterval) clearInterval(timerInterval)
    
    const lastAction = match.value?.game_state?.last_action_at || Math.floor(Date.now() / 1000)
    const elapsed = Math.floor(Math.floor(Date.now() / 1000) - lastAction)
    remainingTime.value = Math.max(0, 60 - elapsed)
    
    timerInterval = setInterval(async () => {
        if (gameState.value !== 'playing') return

        remainingTime.value--
        if (remainingTime.value <= 0) {
            clearInterval(timerInterval)
            if (isMyTurn.value) {
                toast.show("Timeout! Turn forfeited.", "error")
                const { data } = await axios.get(`/api/pool/match/${roomCode.value}`)
                match.value = data
                if (data.status === 'completed') {
                    gameState.value = 'finished'
                    auth.fetchUser()
                }
            }
        }
    }, 1000)
}

const setupEcho = () => {
    if (!match.value || echo.value) return
    const token = localStorage.getItem('token')
    echo.value = initEcho(token)
    channel.value = echo.value.private(`pool.${match.value.id}`)
        .listen('.pool.updated', (e) => {
            console.log("Live Pool Update Received:", e)
            const oldState = match.value?.game_state

            if (e.gameState.last_shot && (!oldState?.last_shot || oldState.last_shot.timestamp !== e.gameState.last_shot.timestamp)) {
                match.value = JSON.parse(JSON.stringify({ ...match.value, game_state: e.gameState }))
                // Only simulate if WE did NOT fire this shot (prevent shooter from re-simulating own shot)
                if (e.gameState.last_shot.user_id != auth.user?.id && !isOriginalShooter.value) {
                    simulateOpponentShot(e.gameState.last_shot)
                }
            } else if (!e.gameState.last_shot) {
                // Turn resolved — sync ball positions first so canvas is correct BEFORE turn indicator flips
                if (!isPhysicsRunning.value) {
                    syncBallPositions(e.gameState.balls)
                } else {
                    pendingBallsSync = e.gameState.balls
                }
                match.value = JSON.parse(JSON.stringify({ ...match.value, game_state: e.gameState }))
            } else {
                match.value = JSON.parse(JSON.stringify({ ...match.value, game_state: e.gameState }))
            }

            if (e.gameState.status === 'finished') {
                gameState.value = 'finished'
                auth.fetchUser()
            } else {
                gameState.value = 'playing'
                startTimer()
            }
        })
}

// Player Helper Selectors
const playersList = computed(() => match.value?.players || [])
const activePlayersData = computed(() => match.value?.game_state?.players || [])

const myPlayerIndex = computed(() => {
    if (!auth.user || !match.value) return -1
    // Use loose == to handle string/number type mismatch from PHP JSON API (mirrors Carrom)
    return activePlayersData.value.findIndex(p => p.user_id == auth.user.id)
})

const turnPlayerIndex = computed(() => {
    return match.value?.game_state?.turn_index ?? 0
})

const isMyTurn = computed(() => {
    if (myPlayerIndex.value === -1) return false
    return myPlayerIndex.value === turnPlayerIndex.value
})

const activePlayerName = computed(() => {
    const activeData = activePlayersData.value[turnPlayerIndex.value]
    if (!activeData) return "Opponent"
    if (activeData.user_id == auth.user?.id) return "Your Turn"
    
    // Find player name from match players list
    const p = playersList.value.find(pl => pl.user_id == activeData.user_id)
    return p?.user?.username || activeData.username || "Opponent's Turn"
})

// Pool Specific Group Assign Helpers
const myGroup = computed(() => {
    if (myPlayerIndex.value === -1 || gameType.value === 'pool_snooker') return null
    return activePlayersData.value[myPlayerIndex.value]?.group || null
})

const opponentGroup = computed(() => {
    if (myPlayerIndex.value === -1 || gameType.value === 'pool_snooker') return null
    const oppIdx = myPlayerIndex.value === 0 ? 1 : 0
    return activePlayersData.value[oppIdx]?.group || null
})

// Physics System Settings
const TABLE_WIDTH = 800
const TABLE_HEIGHT = 400
const BALL_RADIUS = 10
const POCKET_RADIUS = 22

// Initialize Matter.js Physics Engine
const initPhysics = () => {
    if (engine) return

    const container = canvasRef.value.parentElement
    const cw = TABLE_WIDTH
    const ch = TABLE_HEIGHT
    
    engine = Matter.Engine.create({
        gravity: { x: 0, y: 0 }
    })
    world = engine.world

    // Add boundaries (6 segments of cushions to leave open spaces for pockets)
    const wallOptions = { 
        isStatic: true, 
        restitution: 0.9, 
        friction: 0.05,
        render: { fillStyle: '#4d2d18' }
    }
    
    const cushions = [
        // Top cushions
        Matter.Bodies.rectangle(210, 8, 330, 16, wallOptions),
        Matter.Bodies.rectangle(590, 8, 330, 16, wallOptions),
        // Bottom cushions
        Matter.Bodies.rectangle(210, ch - 8, 330, 16, wallOptions),
        Matter.Bodies.rectangle(590, ch - 8, 330, 16, wallOptions),
        // Left cushion
        Matter.Bodies.rectangle(8, 200, 16, 310, wallOptions),
        // Right cushion
        Matter.Bodies.rectangle(cw - 8, 200, 16, 310, wallOptions)
    ]
    Matter.Composite.add(world, cushions)

    // Setup pockets sensors (4 corners, 2 center side)
    const pocketCoords = [
        { x: 12, y: 12 }, { x: cw - 12, y: 12 },
        { x: 12, y: ch - 12 }, { x: cw - 12, y: ch - 12 },
        { x: cw / 2, y: -2 }, { x: cw / 2, y: ch + 2 }
    ]
    
    pocketSensors = pocketCoords.map(coord => {
        return Matter.Bodies.circle(coord.x, coord.y, POCKET_RADIUS, {
            isSensor: true,
            isStatic: true,
            label: 'pocket'
        })
    })
    Matter.Composite.add(world, pocketSensors)

    // Load initial balls from game_state
    const ballsData = match.value?.game_state?.balls || []
    ballBodies = []

    ballsData.forEach(ball => {
        const body = Matter.Bodies.circle(ball.x, ball.y, BALL_RADIUS, {
            restitution: 0.9,
            friction: 0.04,
            frictionAir: 0.028,  // Higher air friction = faster stopping, more responsive gameplay
            label: ball.type,
            density: 0.1
        })
        body.game_id = ball.id
        body.color = ball.color
        body.val = ball.val
        body.points = ball.points
        body.isPocketed = ball.isPocketed

        if (ball.isPocketed) {
            Matter.Body.setPosition(body, { x: -999, y: -999 })
            Matter.Body.setStatic(body, true)
        }

        if (ball.type === 'cue') {
            cueBallBody = body
        }

        ballBodies.push(body)
        Matter.Composite.add(world, body)
    })

    // Manual substep physics — 4 steps per frame so balls move 52/4=13px per step
    // Wall thickness is 16px so 13 < 16 → no tunneling at max shot power
    const SUBSTEPS = 4
    const PHYSICS_DELTA = (1000 / 60) / SUBSTEPS

    // Canvas rendering loop (we draw custom graphics rather than using Matter.Render)
    const canvas = canvasRef.value
    const ctx = canvas.getContext('2d')

    const draw = () => {
        if (!ctx) return

        // Run physics substeps only when balls are in motion
        if (isPhysicsRunning.value) {
            for (let s = 0; s < SUBSTEPS; s++) {
                Matter.Engine.update(engine, PHYSICS_DELTA)
            }
            checkPocketCollisions()
            checkAllBallsStopped()
        }

        ctx.clearRect(0, 0, cw, ch)

        // Draw cloth background (Obsidian space void gradient)
        const clothGrad = ctx.createRadialGradient(cw/2, ch/2, 50, cw/2, ch/2, cw/2)
        clothGrad.addColorStop(0, '#0d0c15')
        clothGrad.addColorStop(1, '#050409')
        ctx.fillStyle = clothGrad
        ctx.fillRect(0, 0, cw, ch)

        // Draw table boundary outline (Glowing neon cyan wood borders)
        ctx.strokeStyle = '#121623'
        ctx.lineWidth = 10
        ctx.strokeRect(0, 0, cw, ch)
        ctx.strokeStyle = '#00f2fe'
        ctx.lineWidth = 2
        ctx.strokeRect(4, 4, cw - 8, ch - 8)

        // Draw baulk line & semi-circle (D-spot)
        ctx.strokeStyle = 'rgba(0, 242, 254, 0.15)'
        ctx.lineWidth = 2
        ctx.beginPath()
        const baulkX = gameType.value === 'pool_snooker' ? 200 : 200
        ctx.moveTo(baulkX, 10)
        ctx.lineTo(baulkX, ch - 10)
        ctx.stroke()
        
        ctx.beginPath()
        ctx.arc(baulkX, ch / 2, 60, Math.PI / 2, -Math.PI / 2)
        ctx.stroke()

        // Draw pocket sensor openings (Glowing neon pink pocket outlines)
        pocketCoords.forEach(c => {
            ctx.beginPath()
            ctx.arc(c.x, c.y, POCKET_RADIUS, 0, Math.PI*2)
            ctx.fillStyle = '#050409'
            ctx.fill()
            ctx.strokeStyle = '#ff007f'
            ctx.lineWidth = 2
            ctx.stroke()
        })

        // Draw active cue balls guide line
        if (isAiming.value && cueBallBody && !isPhysicsRunning.value) {
            const cueX = cueBallBody.position.x
            const cueY = cueBallBody.position.y
            const angle = aimAngle.value

            const dx = Math.cos(angle)
            const dy = Math.sin(angle)

            // Draw glowing cyan dotted aiming guide line
            ctx.strokeStyle = 'rgba(0, 242, 254, 0.8)'
            ctx.lineWidth = 2
            ctx.setLineDash([5, 5])
            ctx.beginPath()
            ctx.moveTo(cueX, cueY)
            ctx.lineTo(cueX + dx * 350, cueY + dy * 350)
            ctx.stroke()
            ctx.setLineDash([])

            // Draw laser circular target spot at end (Glowing pink)
            ctx.fillStyle = 'rgba(255, 0, 127, 0.65)'
            ctx.beginPath()
            ctx.arc(cueX + dx * 200, cueY + dy * 200, 5, 0, Math.PI*2)
            ctx.fill()
        }

        // Draw all active balls on table
        ballBodies.forEach(body => {
            if (body.isPocketed) return

            const bx = body.position.x
            const by = body.position.y

            // Shadow
            ctx.fillStyle = 'rgba(0,0,0,0.35)'
            ctx.beginPath()
            ctx.arc(bx + 2, by + 2, BALL_RADIUS, 0, Math.PI*2)
            ctx.fill()

            // Main Ball
            ctx.fillStyle = body.color || '#fff'
            ctx.beginPath()
            ctx.arc(bx, by, BALL_RADIUS, 0, Math.PI*2)
            ctx.fill()

            // 3D Reflection Shine
            const shineGrad = ctx.createRadialGradient(bx - 3, by - 3, 1, bx, by, BALL_RADIUS)
            shineGrad.addColorStop(0, 'rgba(255,255,255,0.7)')
            shineGrad.addColorStop(0.3, 'rgba(255,255,255,0.1)')
            shineGrad.addColorStop(1, 'rgba(255,255,255,0)')
            ctx.fillStyle = shineGrad
            ctx.beginPath()
            ctx.arc(bx, by, BALL_RADIUS, 0, Math.PI*2)
            ctx.fill()

            // Draw Stripe lines if Stripe ball
            if (body.label === 'stripe') {
                ctx.fillStyle = '#ffffff'
                ctx.beginPath()
                ctx.arc(bx, by, BALL_RADIUS, -Math.PI / 4, Math.PI / 4)
                ctx.fill()
                ctx.beginPath()
                ctx.arc(bx, by, BALL_RADIUS, 3 * Math.PI / 4, 5 * Math.PI / 4)
                ctx.fill()
            }

            // Draw numbers (val) on balls for 1v1 Normal Pool
            if (body.val !== undefined && body.label !== 'cue') {
                ctx.fillStyle = '#ffffff'
                ctx.beginPath()
                ctx.arc(bx, by, 4, 0, Math.PI*2)
                ctx.fill()
                
                ctx.fillStyle = '#000000'
                ctx.font = '6px Inter, sans-serif'
                ctx.textAlign = 'center'
                ctx.textBaseline = 'middle'
                ctx.fillText(body.val.toString(), bx, by)
            }
        })

        // Draw pool stick/cue when aiming
        if (isAiming.value && cueBallBody && !isPhysicsRunning.value) {
            const cueX = cueBallBody.position.x
            const cueY = cueBallBody.position.y
            const angle = aimAngle.value
            
            // Draw wooden cue stick pointing backward
            const backX = Math.cos(angle + Math.PI)
            const backY = Math.sin(angle + Math.PI)
            const distance = 25 + powerPosition.value / 3

            ctx.lineWidth = 4
            ctx.strokeStyle = '#c49a6c' // stick wood color
            ctx.beginPath()
            ctx.moveTo(cueX + backX * distance, cueY + backY * distance)
            ctx.lineTo(cueX + backX * (distance + 150), cueY + backY * (distance + 150))
            ctx.stroke()

            // Cue white tip
            ctx.lineWidth = 4
            ctx.strokeStyle = '#ffffff'
            ctx.beginPath()
            ctx.moveTo(cueX + backX * distance, cueY + backY * distance)
            ctx.lineTo(cueX + backX * (distance + 8), cueY + backY * (distance + 8))
            ctx.stroke()
        }

        renderLoop = requestAnimationFrame(draw)
    }

    draw()
}

// Sync local physics positions with backend state
const syncBallPositions = (ballsData) => {
    if (!ballsData || ballBodies.length === 0) return

    ballsData.forEach(ball => {
        const body = ballBodies.find(b => b.game_id === ball.id)
        if (body) {
            body.isPocketed = ball.isPocketed
            if (ball.isPocketed) {
                Matter.Body.setPosition(body, { x: -999, y: -999 })
                Matter.Body.setStatic(body, true)
            } else {
                Matter.Body.setStatic(body, false)
                Matter.Body.setPosition(body, { x: ball.x, y: ball.y })
                Matter.Body.setVelocity(body, { x: 0, y: 0 })
            }
        }
    })
}

// Check pocket sensor intersections
const checkPocketCollisions = () => {
    ballBodies.forEach(body => {
        if (body.isPocketed) return

        pocketSensors.forEach(pocket => {
            const dist = Math.hypot(body.position.x - pocket.position.x, body.position.y - pocket.position.y)
            if (dist < POCKET_RADIUS + 4) {
                // Sunk a ball!
                body.isPocketed = true
                Matter.Body.setVelocity(body, { x: 0, y: 0 })
                Matter.Body.setPosition(body, { x: -999, y: -999 })
                Matter.Body.setStatic(body, true)
                
                pocketedThisTurn.value.push({
                    id: body.game_id,
                    type: body.label,
                    val: body.val,
                    points: body.points,
                    color: body.color
                })

                toast.show(`Sunk ${body.label === 'cue' ? 'Cue Ball (Scratch)' : (body.label + ' ball!')}`, body.label === 'cue' ? 'error' : 'success')
            }
        })
    })
}

// Aiming & Drag Handler
const onCanvasMouseDown = (e) => {
    if (gameState.value !== 'playing' || !isMyTurn.value || isPhysicsRunning.value || hasPlayedThisTurn.value) return

    const rect = canvasRef.value.getBoundingClientRect()
    // Scale from display size to canvas physics space (canvas is 800x400 internally)
    const scaleX = 800 / rect.width
    const scaleY = 400 / rect.height
    const mouseX = (e.clientX - rect.left) * scaleX
    const mouseY = (e.clientY - rect.top) * scaleY

    if (!cueBallBody) return
    const cueX = cueBallBody.position.x
    const cueY = cueBallBody.position.y

    // Can only start aiming by clicking near cue ball
    const dist = Math.hypot(mouseX - cueX, mouseY - cueY)
    if (dist < 50) {
        isAiming.value = true
        dragStart.value = { x: mouseX, y: mouseY }
        dragCurrent.value = { x: mouseX, y: mouseY }
        aimAngle.value = 0
        powerPosition.value = 0
    }
}

const onCanvasMouseMove = (e) => {
    if (!isAiming.value || !canvasRef.value) return

    const rect = canvasRef.value.getBoundingClientRect()
    const scaleX = 800 / rect.width
    const scaleY = 400 / rect.height
    const mouseX = (e.clientX - rect.left) * scaleX
    const mouseY = (e.clientY - rect.top) * scaleY

    dragCurrent.value = { x: mouseX, y: mouseY }

    // Aiming goes in the opposite direction of the drag vector (slingshot style)
    const dx = dragStart.value.x - mouseX
    const dy = dragStart.value.y - mouseY
    aimAngle.value = Math.atan2(dy, dx)

    const dragDist = Math.min(maxDragDistance, Math.hypot(dx, dy))
    powerPosition.value = (dragDist / maxDragDistance) * 100
}

const onCanvasMouseUp = () => {
    if (!isAiming.value) return
    isAiming.value = false

    const dx = dragStart.value.x - dragCurrent.value.x
    const dy = dragStart.value.y - dragCurrent.value.y
    const dragDist = Math.hypot(dx, dy)

    if (dragDist < 8) return // too soft, cancel strike

    // Max velocity ~52 — strong enough to clear the table without tunneling through walls
    const forceFactor = Math.min(1, dragDist / maxDragDistance)
    const strikeVx = Math.cos(aimAngle.value) * forceFactor * 52
    const strikeVy = Math.sin(aimAngle.value) * forceFactor * 52

    shootCueBall(strikeVx, strikeVy)
}

// Touch equivalents for mobile support
const getCanvasCoords = (touch) => {
    const rect = canvasRef.value.getBoundingClientRect()
    // Scale from display size to canvas physics space (canvas is 800x400 internally)
    const scaleX = 800 / rect.width
    const scaleY = 400 / rect.height
    return {
        x: (touch.clientX - rect.left) * scaleX,
        y: (touch.clientY - rect.top) * scaleY
    }
}

const onCanvasTouchStart = (e) => {
    if (gameState.value !== 'playing' || !isMyTurn.value || isPhysicsRunning.value || hasPlayedThisTurn.value) return
    const touch = e.touches[0]
    if (!touch || !canvasRef.value || !cueBallBody) return
    const { x, y } = getCanvasCoords(touch)
    const dist = Math.hypot(x - cueBallBody.position.x, y - cueBallBody.position.y)
    if (dist < 50) {
        isAiming.value = true
        dragStart.value = { x, y }
        dragCurrent.value = { x, y }
        aimAngle.value = 0
        powerPosition.value = 0
    }
}

const onCanvasTouchMove = (e) => {
    if (!isAiming.value || !canvasRef.value) return
    const touch = e.touches[0]
    if (!touch) return
    const { x, y } = getCanvasCoords(touch)
    dragCurrent.value = { x, y }
    const dx = dragStart.value.x - x
    const dy = dragStart.value.y - y
    aimAngle.value = Math.atan2(dy, dx)
    const dragDist = Math.min(maxDragDistance, Math.hypot(dx, dy))
    powerPosition.value = (dragDist / maxDragDistance) * 100
}


// Apply physics impulse locally & transmit to other players
const shootCueBall = async (vx, vy) => {
    if (!cueBallBody) return

    isPhysicsRunning.value = true
    hasPlayedThisTurn.value = true
    pocketedThisTurn.value = []
    isOriginalShooter.value = true

    Matter.Body.setStatic(cueBallBody, false)
    Matter.Body.setVelocity(cueBallBody, { x: vx, y: vy })

    // Submit strike parameters securely to backend
    try {
        await axios.post(`/api/pool/strike/${roomCode.value}`, {
            vx,
            vy,
            cueAngle: aimAngle.value,
            force: powerPosition.value
        })
    } catch (e) {
        toast.show("Error syncing shot with opponent.", "error")
    }

    startTimer()
}

// Timestamp-based dedup guard: prevents Echo + polling from simulating the same shot twice
let lastSimulatedShotTimestamp = null

// Receive opponent shot parameters and simulate
const simulateOpponentShot = (shotData) => {
    if (!cueBallBody) return
    if (isPhysicsRunning.value) return          // Already simulating — ignore duplicate
    if (shotData.user_id == auth.user?.id) return // We are the shooter — never re-simulate our own shot
    if (isOriginalShooter.value) return         // We are the shooter — never re-simulate our own shot
    if (shotData.timestamp && shotData.timestamp === lastSimulatedShotTimestamp) return // Exact duplicate event

    lastSimulatedShotTimestamp = shotData.timestamp || null

    isPhysicsRunning.value = true
    hasPlayedThisTurn.value = false
    pocketedThisTurn.value = []
    isOriginalShooter.value = false

    Matter.Body.setStatic(cueBallBody, false)
    Matter.Body.setVelocity(cueBallBody, { x: shotData.vx, y: shotData.vy })
}

// Stop Detection Loop
const checkAllBallsStopped = () => {
    if (!isPhysicsRunning.value) return

    // Verify all active bodies have kinetic energy near 0
    let allStopped = true
    ballBodies.forEach(body => {
        if (body.isPocketed) return
        const speed = Math.hypot(body.velocity.x, body.velocity.y)
        if (speed > 0.05) {
            allStopped = false
        }
    })

    if (allStopped) {
        isPhysicsRunning.value = false
        // Zero all velocities
        ballBodies.forEach(body => {
            if (!body.isPocketed) {
                Matter.Body.setVelocity(body, { x: 0, y: 0 })
                Matter.Body.setAngularVelocity(body, 0)
            }
        })

        // Mirror Carrom: only the active turn player who actually shot resolves state
        if (isMyTurn.value && hasPlayedThisTurn.value) {
            pendingBallsSync = null  // Shooter never needs a pending sync
            resolveRestingState()
        } else {
            // Either simulating opponent's shot, OR turn changed during simulation via Echo/poll
            // Always apply queued server positions so canvas stays authoritative
            if (pendingBallsSync) {
                syncBallPositions(pendingBallsSync)
                pendingBallsSync = null
            }
            if (!isMyTurn.value) {
                // Opponent simulation finished — start polling for final resolved state
                startOpponentTurnPolling()
            }
            // If isMyTurn is now true (turn changed during simulation), player can shoot immediately
        }
    }
}

// Compute Score & Match Rules Outcomes
const resolveRestingState = async () => {
    try {
        const ballsState = ballBodies.map(body => ({
            id: body.game_id,
            type: body.label,
            color: body.color,
            points: body.points,
            val: body.val,
            x: body.position.x,
            y: body.position.y,
            isPocketed: body.isPocketed
        }))

        let currentScores = activePlayersData.value.map(p => p.score)
        let nextTurnIndex = turnPlayerIndex.value
        let winnerId = null
        let currentGroups = activePlayersData.value.map(p => p.group)

        const pocketedList = pocketedThisTurn.value
        let cueScratch = pocketedList.some(b => b.type === 'cue')

        if (gameType.value === 'pool_snooker') {
            // Snooker Rule Resolving
            let pointsGained = 0
            
            // Check pocketed reds & colors
            const pocketedReds = pocketedList.filter(b => b.type === 'red')
            const pocketedColors = pocketedList.filter(b => b.type === 'color')

            pocketedReds.forEach(r => { pointsGained += 1 })
            pocketedColors.forEach(c => { pointsGained += (c.points || 0) })

            if (cueScratch) {
                // Scratch deducts 4 points
                pointsGained -= 4
                toast.show("Cue Scratch! 4 points deducted, turn rotates.", "error")
            }

            // Apply points
            currentScores[myPlayerIndex.value] = Math.max(0, currentScores[myPlayerIndex.value] + pointsGained)

            // Respawn colors in Snooker back to standard spots
            pocketedColors.forEach(c => {
                const body = ballBodies.find(b => b.game_id === c.id)
                if (body) {
                    body.isPocketed = false
                    Matter.Body.setStatic(body, false)
                    // Reset to initial coordinates based on color points
                    const initialSpots = {
                        2: { x: 200, y: 150 }, // Yellow
                        3: { x: 200, y: 250 }, // Green
                        4: { x: 200, y: 200 }, // Brown
                        5: { x: 400, y: 200 }, // Blue
                        6: { x: 580, y: 200 }, // Pink
                        7: { x: 700, y: 200 }  // Black
                    }
                    const spot = initialSpots[c.points] || { x: 400, y: 200 }
                    Matter.Body.setPosition(body, spot)
                    Matter.Body.setVelocity(body, { x: 0, y: 0 })

                    // Update corresponding item in state array
                    const bState = ballsState.find(b => b.id === c.id)
                    if (bState) {
                        bState.isPocketed = false
                        bState.x = spot.x
                        bState.y = spot.y
                    }
                }
            })

            // Respawn cue ball if scratch
            if (cueScratch && cueBallBody) {
                cueBallBody.isPocketed = false
                Matter.Body.setStatic(cueBallBody, false)
                Matter.Body.setPosition(cueBallBody, { x: 150, y: 200 })
                Matter.Body.setVelocity(cueBallBody, { x: 0, y: 0 })

                const cueState = ballsState.find(b => b.type === 'cue')
                if (cueState) {
                    cueState.isPocketed = false
                    cueState.x = 150;
                    cueState.y = 200;
                }
            }

            // Check if 25 points target reached
            if (currentScores[myPlayerIndex.value] >= 25) {
                winnerId = auth.user.id
            }

            // Turn switching: keep turn if player pocketed any valid ball and didn't scratch
            const validSink = (pocketedReds.length > 0 || pocketedColors.length > 0) && !cueScratch
            if (!validSink) {
                nextTurnIndex = (turnPlayerIndex.value + 1) % activePlayersData.value.length
            }

        } else {
            // 8 Ball Pool 1v1 Rule Resolving
            const myAssignedGroup = myGroup.value
            const pocketedSolids = pocketedList.filter(b => b.type === 'solid')
            const pocketedStripes = pocketedList.filter(b => b.type === 'stripe')
            const pocketedBlack = pocketedList.some(b => b.type === 'black')

            let turnShouldRotate = true

            // Respawn cue ball if scratch
            if (cueScratch && cueBallBody) {
                cueBallBody.isPocketed = false
                Matter.Body.setStatic(cueBallBody, false)
                Matter.Body.setPosition(cueBallBody, { x: 200, y: 200 })
                Matter.Body.setVelocity(cueBallBody, { x: 0, y: 0 })

                const cueState = ballsState.find(b => b.type === 'cue')
                if (cueState) {
                    cueState.isPocketed = false
                    cueState.x = 200;
                    cueState.y = 200;
                }
            }

            if (pocketedBlack) {
                // pocketed 8-ball: check win condition
                const remainingMine = ballsState.filter(b => b.type === myAssignedGroup && !b.isPocketed).length
                if (myAssignedGroup && remainingMine === 0 && !cueScratch) {
                    winnerId = auth.user.id
                    toast.show("Sunk Black 8-Ball! YOU WIN!", "success")
                } else {
                    // Instant loss if cleared early or scratched
                    const oppIdx = myPlayerIndex.value === 0 ? 1 : 0
                    winnerId = activePlayersData.value[oppIdx].user_id
                    toast.show("Clearing Black 8-ball early is a foul. Opponent wins!", "error")
                }
            } else if (!myAssignedGroup) {
                // Table is open: assign groups on first sunk ball
                if (pocketedSolids.length > 0 && !cueScratch) {
                    currentGroups[myPlayerIndex.value] = 'solid'
                    currentGroups[myPlayerIndex.value === 0 ? 1 : 0] = 'stripe'
                    turnShouldRotate = false
                    toast.show("Assigned Solids!", "info")
                } else if (pocketedStripes.length > 0 && !cueScratch) {
                    currentGroups[myPlayerIndex.value] = 'stripe'
                    currentGroups[myPlayerIndex.value === 0 ? 1 : 0] = 'solid'
                    turnShouldRotate = false
                    toast.show("Assigned Stripes!", "info")
                }
            } else {
                // Groups assigned: check if player pocketed their own group balls
                const mySunk = myAssignedGroup === 'solid' ? pocketedSolids.length : pocketedStripes.length
                
                if (mySunk > 0 && !cueScratch) {
                    turnShouldRotate = false
                }
            }

            if (cueScratch) {
                turnShouldRotate = true
            }

            if (turnShouldRotate) {
                nextTurnIndex = turnPlayerIndex.value === 0 ? 1 : 0
            }

            // Sync scores as count of pocketed group balls
            activePlayersData.value.forEach((player, pIdx) => {
                const group = currentGroups[pIdx]
                if (group) {
                    const sunkCount = ballsState.filter(b => b.type === group && b.isPocketed).length
                    currentScores[pIdx] = sunkCount
                }
            })
        }

        // Send resting state back to backend API
        const payload = {
            balls: ballsState,
            scores: currentScores,
            groups: currentGroups,
            next_turn_index: nextTurnIndex
        }

        if (winnerId) {
            payload.winner_id = winnerId
        }

        const res = await axios.post(`/api/pool/update-state/${roomCode.value}`, payload)

        if (winnerId) {
            match.value = res.data.match
            gameState.value = 'finished'
            auth.fetchUser()
        } else {
            // Sync ball positions first so canvas is up-to-date BEFORE turn indicator flips (mirrors Carrom)
            syncBallPositions(res.data.match.game_state.balls)
            match.value = JSON.parse(JSON.stringify(res.data.match))
            hasPlayedThisTurn.value = false
            isOriginalShooter.value = false
            startTimer()
        }

    } catch (e) {
        console.error("Failure updating resting state:", e)
        toast.show("Error synchronizing table state with Laravel.", "error")
        hasPlayedThisTurn.value = false
        isOriginalShooter.value = false
    }
}

// Forfeit active match
const forfeitMatch = async () => {
    if (!window.confirm("Are you sure you want to forfeit? You will lose your bet!")) return
    try {
        await axios.post(`/api/pool/forfeit/${roomCode.value}`)
        gameState.value = 'finished'
        auth.fetchUser()
    } catch (e) {
        toast.show("Error forfeiting match.", "error")
    }
}

// Mirror Carrom exactly: watch both isMyTurn AND gameState for robust turn sync
watch([isMyTurn, gameState], ([newTurn, newGameState]) => {
    if (newGameState === 'playing') {
        if (newTurn) {
            // It's my turn — sync ball positions from server state and stop opponent polling
            if (!isPhysicsRunning.value) {
                syncBallPositions(match.value?.game_state?.balls)
            }
            if (opponentTurnPollInterval) {
                clearInterval(opponentTurnPollInterval)
                opponentTurnPollInterval = null
            }
        } else {
            // Opponent's turn — start polling as Echo fallback
            startOpponentTurnPolling()
        }
    } else {
        if (opponentTurnPollInterval) clearInterval(opponentTurnPollInterval)
    }
}, { immediate: true })

onMounted(() => {
    auth.fetchUser()
    fetchSettings()
    checkActiveMatch()
    // Global listeners so drag works even when mouse/touch leaves the canvas
    window.addEventListener('mousemove', onCanvasMouseMove)
    window.addEventListener('mouseup', onCanvasMouseUp)
    window.addEventListener('touchmove', onCanvasTouchMove, { passive: false })
    window.addEventListener('touchend', onCanvasMouseUp, { passive: false })
})

onUnmounted(() => {
    window.removeEventListener('mousemove', onCanvasMouseMove)
    window.removeEventListener('mouseup', onCanvasMouseUp)
    window.removeEventListener('touchmove', onCanvasTouchMove)
    window.removeEventListener('touchend', onCanvasMouseUp)
    if (pollInterval) clearInterval(pollInterval)
    if (opponentTurnPollInterval) clearInterval(opponentTurnPollInterval)
    if (timerInterval) clearInterval(timerInterval)
    if (matchingTimerInterval) clearInterval(matchingTimerInterval)
    cancelAnimationFrame(renderLoop)
    if (engine) Matter.Engine.clear(engine)
    if (echo.value) {
        echo.value.disconnect()
    }
})
</script>

<template>
  <div class="min-h-screen bg-[#060606] text-white flex flex-col font-outfit relative overflow-hidden">
    <!-- Navbar -->
    <div class="p-6 flex items-center justify-between fixed top-0 left-0 right-0 z-[100] bg-[#121623]/80 backdrop-blur-md border-b border-white/5 h-[80px]">
      <button @click="router.push('/')" class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 group transition-all">
        <ChevronLeft class="w-6 h-6 group-hover:-translate-x-1 transition-transform" />
      </button>
      <div class="flex flex-col items-center">
        <h1 class="text-xl font-black italic tracking-tighter uppercase text-neon-cyan">{{ gameName }}</h1>
        <p class="text-[10px] text-slate-400 font-bold tracking-[0.3em] uppercase">MULTIPLAYER ARENA</p>
      </div>
      <div class="bg-[#121623] px-5 py-2 rounded-2xl border border-white/5 flex items-center gap-3">
        <Coins class="w-4 h-4 text-neon-pink" />
        <span class="font-black text-base">{{ currencySymbol }} {{ Number(auth.user?.balance || 0).toFixed(2) }}</span>
      </div>
    </div>

    <!-- Content Area -->
    <div class="flex-1 relative z-10 flex flex-col pt-[80px]">
      <!-- Matchmaking Lobby State -->
      <div v-if="gameState === 'lobby'" class="flex-1 flex flex-col items-center justify-center p-6 animate-fade-in max-w-md mx-auto w-full">
        <div class="w-full flex flex-col gap-6 text-center">
          <div class="space-y-2">
            <h2 class="text-3xl font-black uppercase italic text-transparent bg-clip-text bg-gradient-to-r from-neon-cyan to-neon-pink tracking-wide">
              {{ gameName }}
            </h2>
            <p class="text-slate-400 font-semibold text-xs uppercase tracking-widest">Select Bet Amount &amp; Battle players</p>
          </div>

          <div class="grid grid-cols-4 gap-2">
            <button v-for="amount in [10, 50, 100, 500]" :key="amount"
              @click="betAmount = amount"
              :class="[
                'py-3.5 rounded-2xl text-sm font-black italic tracking-wide transition-all border cursor-pointer',
                betAmount === amount 
                  ? 'bg-neon-cyan text-slate-950 border-neon-cyan shadow-lg shadow-neon-cyan/20 scale-105 font-black' 
                  : 'bg-white/5 border-white/5 text-zinc-300 hover:border-zinc-700'
              ]"
            >
              {{ currencySymbol }} {{ amount }}
            </button>
          </div>

          <div class="relative group">
            <span class="absolute left-8 top-1/2 -translate-y-1/2 text-neon-cyan font-black text-2xl">{{ currencySymbol }}</span>
            <input type="number" v-model="betAmount" class="w-full bg-white/5 border-4 border-white/10 rounded-[2.5rem] py-8 pl-24 text-4xl font-black focus:border-neon-cyan/50 outline-none text-slate-100">
          </div>

          <button @click="joinMatch" :disabled="processing" class="w-full bg-neon-pink text-white font-black py-7 rounded-[2.5rem] text-2xl shadow-[0_10px_0_#99014b] hover:scale-[1.02] active:translate-y-2 active:shadow-none transition-all uppercase italic cursor-pointer shadow-[0_0_20px_rgba(255,0,127,0.3)]">
            {{ processing ? 'Finding Opponent...' : 'Enter Tournament' }}
          </button>

          <!-- Rules & Guidelines Card -->
          <div class="w-full bg-[#121623]/60 border border-white/10 rounded-3xl p-5 space-y-4 shadow-2xl animate-fade-in text-left">
            <div class="flex items-center gap-2 text-neon-cyan">
              <Trophy class="w-4 h-4" />
              <span class="text-xs font-black uppercase tracking-wider">Tournament Rules &amp; Guidelines</span>
            </div>
            <ul class="space-y-3 text-[10px] text-zinc-400 font-bold uppercase tracking-wider leading-relaxed">
              <li v-if="gameType === 'pool_1v1'" class="flex gap-2">
                <span class="text-neon-cyan">★</span>
                <span><strong>Group Assignments</strong>: Sinking a ball first assigns you <strong>Solids</strong> or <strong>Stripes</strong>.</span>
              </li>
              <li v-else class="flex gap-2">
                <span class="text-neon-cyan">★</span>
                <span><strong>Snooker Points</strong>: Sinking reds yields 1pt; Sunk color balls yield respective points and respawn.</span>
              </li>
              <li v-if="gameType === 'pool_1v1'" class="flex gap-2">
                <span class="text-neon-cyan">★</span>
                <span><strong>Turn Continuation</strong>: Pocketing your <strong>own group ball</strong> grants an extra turn. Scratching hands turn to opponent.</span>
              </li>
              <li v-else class="flex gap-2">
                <span class="text-neon-cyan">★</span>
                <span><strong>Snooker Match Target</strong>: Face 3 to 5 players. The first player to reach <strong>25 points</strong> wins the match!</span>
              </li>
              <li class="flex gap-2">
                <span class="text-neon-cyan">★</span>
                <span><strong>Timeout limit</strong>: You have 60 seconds to take your shot or you forfeit your bet amount.</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Matchmaking Waiting State -->
      <div v-else-if="gameState === 'waiting'" class="flex-1 flex flex-col items-center justify-center p-6 space-y-10 animate-fade-in max-w-sm mx-auto w-full">
        <div class="w-40 h-40 bg-neon-cyan/10 rounded-[3rem] flex items-center justify-center border-2 border-neon-cyan/20 relative animate-pulse shadow-[0_0_20px_rgba(0,242,254,0.15)]">
          <Users class="w-20 h-20 text-neon-cyan" />
        </div>
        <div class="text-center space-y-2">
          <h2 class="text-3xl font-black uppercase italic text-transparent bg-clip-text bg-gradient-to-r from-neon-cyan to-neon-pink">Searching Match...</h2>
          <p class="text-slate-500 font-bold text-xs uppercase tracking-widest">Room Code: {{ roomCode }}</p>
          <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Stake Pot: {{ currencySymbol }} {{ match?.total_pot || betAmount }}</p>
          <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Timer: {{ matchingTimeElapsed }}s</p>
        </div>

        <!-- Snooker countdown overlay -->
        <div v-if="gameType === 'pool_snooker' && countdownTime > 0" class="w-full p-4 bg-neon-cyan/10 border border-neon-cyan/20 rounded-2xl text-center">
          <span class="text-[10px] text-neon-cyan uppercase font-black tracking-widest block mb-1">Queue Autostarting In</span>
          <span class="text-3xl font-black text-white tracking-widest">{{ countdownTime }}s</span>
          <p class="text-[10px] text-zinc-400 mt-2">Required: 3-5 players. Current lobby has {{ match?.game_state?.players_count || 1 }} players.</p>
        </div>

        <!-- Cancel Match Options -->
        <div class="w-full space-y-4">
          <div class="text-center text-slate-400 font-bold text-[10px] uppercase tracking-wider bg-white/5 p-4 rounded-2xl border border-white/5">
            ⚠️ High matching queue. You can cancel and refund anytime.
          </div>
          <button @click="cancelMatchmaking" :disabled="cancelling" class="w-full bg-red-600 text-white font-black py-6 rounded-[2.5rem] text-xl shadow-[0_8px_0_#991b1b] active:translate-y-2 active:shadow-none transition-all uppercase italic cursor-pointer">
            {{ cancelling ? 'Leaving Arena...' : 'Cancel &amp; Refund' }}
          </button>
        </div>
      </div>

      <!-- Playing -->
      <div v-else-if="gameState === 'playing'" class="flex-1 flex flex-col-reverse lg:flex-row-reverse p-6 gap-6 items-stretch justify-center max-w-6xl mx-auto w-full">
        <!-- Left sidebar: scores & status -->
        <div class="lg:w-80 flex flex-col gap-4">
          <!-- Pot Amount HUD -->
          <div class="bg-[#121623]/60 backdrop-blur-xl border border-white/10 p-5 rounded-3xl shadow-lg relative overflow-hidden flex justify-between items-center">
            <div class="absolute -top-10 -right-10 w-24 h-24 bg-neon-cyan/5 rounded-full blur-2xl"></div>
            <div>
              <span class="text-[9px] text-zinc-400 uppercase font-black tracking-widest block leading-none">TOTAL POT STAKE</span>
              <span class="text-2xl font-black text-neon-cyan mt-2 block">{{ currencySymbol }} {{ match?.total_pot }}</span>
            </div>
            <div class="bg-white/5 border border-white/10 p-3 rounded-2xl">
              <Coins size="20" class="text-neon-cyan" />
            </div>
          </div>

          <!-- Turn HUD Indicator -->
          <div :class="[
            'border rounded-3xl p-5 shadow-lg relative overflow-hidden transition-all duration-300 text-center',
            isMyTurn 
              ? 'bg-neon-cyan/10 border-neon-cyan/30 shadow-neon-cyan/5' 
              : 'bg-black/60 border-white/10'
          ]">
            <span class="text-[9px] text-zinc-400 uppercase font-black tracking-widest block leading-none mb-2">MATCH TURN STATE</span>
            <div class="flex items-center justify-center gap-3">
              <div :class="[
                'w-3 h-3 rounded-full',
                isMyTurn ? 'bg-neon-cyan animate-pulse shadow-[0_0_8px_#00f2fe]' : 'bg-zinc-600'
              ]"></div>
              <span class="text-lg font-black tracking-tight" :class="isMyTurn ? 'text-neon-cyan' : 'text-zinc-100'">
                {{ activePlayerName }}
              </span>
            </div>

            <!-- Turn timer bar -->
            <div class="mt-4 bg-zinc-950/80 rounded-full h-1.5 overflow-hidden border border-white/5">
              <div class="bg-neon-cyan h-full transition-all duration-1000 shadow-[0_0_8px_#00f2fe]"
                :style="{ width: `${(remainingTime / 60) * 100}%` }"
              ></div>
            </div>
            <span class="text-[10px] text-zinc-500 font-bold block mt-2">{{ remainingTime }}s left</span>
          </div>

          <!-- Scores Sidebar -->
          <div class="bg-[#121623]/60 backdrop-blur-xl border border-white/10 rounded-3xl p-5 flex-1 shadow-lg flex flex-col justify-between">
            <div>
              <h3 class="text-xs font-black uppercase text-zinc-400 tracking-wider mb-4">LOBBY SCORES</h3>
              
              <div class="flex flex-col gap-2">
                <div v-for="(pData, idx) in activePlayersData" :key="pData.user_id"
                  :class="[
                    'flex justify-between items-center p-3 rounded-2xl border text-sm font-bold',
                    idx === turnPlayerIndex ? 'bg-white/5 border-neon-cyan/30 text-neon-cyan' : 'bg-zinc-950/40 border-white/5 text-zinc-300'
                  ]"
                >
                  <div class="flex items-center gap-2">
                    <!-- Solids vs Stripes custom CSS styled pills for 1v1 -->
                    <template v-if="gameType === 'pool_1v1' && pData.group">
                      <!-- Solids Pill (Neon Cyan) -->
                      <div v-if="pData.group === 'solid'" 
                        class="w-4 h-4 rounded-full bg-neon-cyan shadow-md shadow-neon-cyan/30 flex items-center justify-center relative overflow-hidden border border-neon-cyan shrink-0"
                        title="Solids"
                      >
                        <div class="absolute w-1.5 h-1.5 bg-white/70 rounded-full top-0.5 left-0.5"></div>
                      </div>
                      <!-- Stripes Pill (Neon Pink) -->
                      <div v-if="pData.group === 'stripe'" 
                        class="w-4 h-4 rounded-full bg-white shadow-md shadow-white/10 flex items-center justify-center relative overflow-hidden border border-zinc-300 shrink-0"
                        title="Stripes"
                      >
                        <div class="w-full h-1.5 bg-neon-pink my-auto"></div>
                        <div class="absolute w-1 h-1 bg-white/80 rounded-full top-0.5 left-0.5"></div>
                      </div>
                    </template>
                    <span class="truncate max-w-[120px]">{{ pData.user_id === auth.user?.id ? 'You' : (playersList.find(pl => pl.user_id === pData.user_id)?.user?.username || pData.username || 'Player') }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-zinc-400 text-xs font-bold">{{ gameType === 'pool_snooker' ? 'pts' : 'balls' }}</span>
                    <span class="text-base font-black" :class="idx === turnPlayerIndex ? 'text-neon-cyan' : 'text-zinc-100'">
                      {{ pData.score }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bottom Forfeit Trigger -->
            <button @click="forfeitMatch"
              class="w-full py-3 bg-red-950/20 border border-red-500/20 hover:bg-red-950/35 hover:border-red-500/35 text-red-400 font-extrabold uppercase rounded-2xl active:scale-95 transition-all text-xs block text-center cursor-pointer"
            >
              Forfeit Match
            </button>
          </div>
        </div>

        <!-- Right Side: Matter.js Physics Canvas -->
        <div class="flex-1 flex flex-col justify-center items-center bg-[#121623]/60 border border-white/10 rounded-3xl p-5 relative overflow-hidden shadow-2xl">
          <div class="w-full max-w-[800px] aspect-[2/1] bg-[#0d0c15] rounded-2xl overflow-hidden border border-white/5 relative select-none">
            <canvas ref="canvasRef" width="800" height="400"
              @mousedown="onCanvasMouseDown"
              @touchstart.prevent="onCanvasTouchStart"
              class="w-full h-full cursor-crosshair block"
            ></canvas>

            <!-- Visible simulating overlay — same as Carrom -->
            <div v-if="isPhysicsRunning"
              class="absolute inset-0 bg-black/40 pointer-events-auto flex items-center justify-center rounded-2xl backdrop-blur-sm"
            >
              <span class="text-xs uppercase tracking-[0.2em] font-black text-neon-pink bg-[#121623]/80 px-5 py-2.5 rounded-full border border-white/10 shadow-[0_0_20px_rgba(255,0,127,0.3)] animate-pulse">
                Simulating Shot...
              </span>
            </div>

            <!-- Opponent turn — subtle dim to show it's not interactive -->
            <div v-else-if="!isMyTurn"
              class="absolute inset-0 pointer-events-auto bg-black/10 rounded-2xl"
            ></div>
          </div>

          <!-- Cue Strike Control HUD -->
          <div class="w-full mt-4 flex items-center justify-between gap-6 bg-zinc-950/60 rounded-2xl p-4 border border-white/5">
            <div class="flex-1 flex items-center gap-3">
              <Sliders size="18" class="text-neon-cyan" />
              <div class="flex-1">
                <span class="text-[10px] text-zinc-400 font-bold uppercase tracking-wider block mb-1">Strike Force</span>
                <div class="h-2 bg-zinc-900 rounded-full overflow-hidden border border-white/5">
                  <div class="bg-neon-cyan h-full transition-all shadow-[0_0_8px_#00f2fe]" :style="{ width: `${powerPosition}%` }"></div>
                </div>
              </div>
              <span class="text-xs font-black text-neon-cyan w-12 text-right">{{ Math.round(powerPosition) }}%</span>
            </div>

            <!-- Drag Hint -->
            <div class="text-right text-xs text-zinc-400">
              <span v-if="isMyTurn && !isPhysicsRunning" class="text-neon-cyan animate-pulse font-extrabold">DRAG CUE BALL BACK TO AIM &amp; SHOOT</span>
              <span v-else-if="isPhysicsRunning" class="text-zinc-500 font-bold">SIMULATING COLLISONS...</span>
              <span v-else class="text-zinc-500 font-bold">OPPONENT IS SHOOTING...</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Match Finished State Screen overlay -->
      <div v-else-if="gameState === 'finished'" class="absolute inset-0 bg-black/95 z-[200] flex items-center justify-center p-6 backdrop-blur-3xl">
        <div class="w-full max-w-md bg-[#0a0a0a] border-4 border-white/10 rounded-[4rem] p-12 text-center space-y-10 shadow-[0_0_40px_rgba(255,0,127,0.15)]">
          <div class="w-20 h-20 bg-zinc-800/80 rounded-full flex items-center justify-center mx-auto border border-white/5 shadow-2xl">
            <Trophy size="40" class="text-neon-cyan" />
          </div>

          <h2 class="text-5xl font-black italic uppercase tracking-wider"
            :class="match?.game_state?.winner_id == auth.user?.id ? 'text-neon-green' : 'text-neon-pink'">
            {{ match?.game_state?.winner_id == auth.user?.id ? 'VICTORY' : 'DEFEAT' }}
          </h2>
          <p class="text-zinc-400 text-xs">{{ match?.game_state?.message || 'The match has concluded.' }}</p>

          <!-- Winnings Payout card -->
          <div class="bg-white/5 rounded-[2.5rem] p-8 border border-white/10 space-y-6">
            <div class="flex justify-between items-center">
              <div class="text-left">
                <p class="text-[10px] text-slate-500 uppercase font-black">Score</p>
                <p class="text-4xl font-black text-white">{{ activePlayersData[myPlayerIndex]?.score || 0 }}</p>
              </div>
              <div class="text-right">
                <p class="text-[10px] text-slate-500 uppercase font-black">Opponent Score</p>
                <p class="text-4xl font-black text-slate-400">{{ activePlayersData[myPlayerIndex === 0 ? 1 : 0]?.score || 0 }}</p>
              </div>
            </div>

            <div v-if="match?.game_state?.winner_id == auth.user?.id" class="pt-6 border-t border-white/10">
              <span class="text-[10px] text-zinc-400 uppercase font-black tracking-widest block mb-2">WINNER NET PAYOUT</span>
              <div class="flex items-center justify-center gap-2 mb-2">
                <Coins size="24" class="text-neon-cyan" />
                <span class="text-3xl font-black text-white">{{ currencySymbol }} {{ Number(match?.game_state?.payout || 0).toFixed(2) }}</span>
              </div>
              <span class="text-[9px] text-neon-cyan font-bold uppercase tracking-wider block">Credited to Balance</span>
            </div>
            <div v-else class="pt-6 border-t border-white/10 text-zinc-500 text-sm font-bold uppercase tracking-wider">
              Better luck next time!
            </div>
          </div>

          <!-- Exit Match -->
          <button @click="router.push('/')"
            class="w-full bg-white text-black py-7 rounded-[2.5rem] font-black text-2xl uppercase italic flex items-center justify-center gap-2 active:scale-95 transition-all cursor-pointer hover:bg-slate-200"
          >
            Exit Arena
            <ArrowRight size="18" />
          </button>
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

/* Disable mouse selection on the canvas board */
canvas {
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  -webkit-touch-callout: none;
}
</style>
