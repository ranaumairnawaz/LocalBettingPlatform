<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { initEcho } from '../plugins/echo'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import { ChevronLeft, Coins, Users, Trophy, Star, AlertCircle, Play, Sliders } from 'lucide-vue-next'
import axios from 'axios'
import Matter from 'matter-js'

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

// Matter.js & Canvas Variables
const canvasRef = ref(null)
let engine = null
let world = null
let runner = null
let renderLoop = null

// Game Pucks & Striker References
let strikerBody = null
let puckBodies = []
let pocketSensors = []
let physicsTicks = 0
let lastTime = 0
let accumulator = 0
const fixedDelta = 1000 / 60

// Interactive Game State
const strikerX = ref(250) // Horizontal placement along baseline
const isAiming = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const dragCurrent = ref({ x: 0, y: 0 })
const maxDragDistance = 150
const isPhysicsRunning = ref(false)
const pocketedThisTurn = ref([])
const aimAngle = ref(0)
const powerPosition = ref(0)
let powerDirection = 1
const hasPlayedThisTurn = ref(false)

// Matchmaking State
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
        await axios.post('/api/carrom/cancel-match')
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
        const res = await axios.post('/api/carrom/join', { bet_amount: betAmount.value })
        match.value = res.data.match
        roomCode.value = res.data.room_code
        gameState.value = match.value.status === 'waiting' ? 'waiting' : 'playing'
        setupEcho()
        auth.fetchUser()
        if (gameState.value === 'playing') {
            nextTick(() => {
                initPhysics()
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
        const { data } = await axios.get('/api/carrom/active-match')
        if (data.match) {
            match.value = data.match
            roomCode.value = data.match.room_code
            gameState.value = data.match.status === 'waiting' ? 'waiting' : 'playing'
            setupEcho()
            if (gameState.value === 'playing') {
                nextTick(() => {
                    initPhysics()
                })
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
            const { data } = await axios.get(`/api/carrom/match/${roomCode.value}`)
            match.value = data
            if (data.status === 'playing') {
                gameState.value = 'playing'
                clearInterval(pollInterval)
                if (matchingTimerInterval) clearInterval(matchingTimerInterval)
                nextTick(() => {
                    initPhysics()
                })
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
        if (gameState.value !== 'playing') {
            clearInterval(opponentTurnPollInterval)
            return
        }
        try {
            const { data } = await axios.get(`/api/carrom/match/${roomCode.value}`)
            if (data) {
                if (data.game_state.status === 'finished') {
                    match.value = data
                    gameState.value = 'finished'
                    auth.fetchUser()
                    clearInterval(opponentTurnPollInterval)
                    return
                }

                // If it is NOT our turn, process opponent shot simulation and coordinate syncing
                if (!isMyTurn.value) {
                    const oldState = match.value?.game_state
                    if (data.game_state.last_shot && (!oldState?.last_shot || oldState.last_shot.timestamp !== data.game_state.last_shot.timestamp)) {
                        match.value = data
                        simulateOpponentShot(data.game_state.last_shot, data.game_state.pucks)
                    } else if (!data.game_state.last_shot) {
                        // Sync positions first so canvas updates before controls show
                        if (!isPhysicsRunning.value) {
                            syncPuckPositions(data.game_state.pucks)
                        }
                        match.value = data
                    } else {
                        match.value = data
                    }
                    startTimer()
                } else {
                    // It is our turn. We only want to check if the opponent has won, forfeited or if match is completed
                    if (data.status === 'completed' || data.game_state.status === 'finished') {
                        match.value = data
                        gameState.value = 'finished'
                        auth.fetchUser()
                        clearInterval(opponentTurnPollInterval)
                    }
                }
            }
        } catch (e) {
            console.error("Error polling opponent turn:", e)
        }
    }, 2000)
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
                toast.show("Timeout! Active player forfeited due to inactivity.", "error")
                const { data } = await axios.get(`/api/carrom/match/${roomCode.value}`)
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
    channel.value = echo.value.private(`carrom.${match.value.id}`)
        .listen('.carrom.updated', (e) => {
            console.log("Live Carrom Update Received:", e)
            const oldState = match.value?.game_state
            
            // If opponent struck the striker
            if (e.gameState.last_shot && (!oldState?.last_shot || oldState.last_shot.timestamp !== e.gameState.last_shot.timestamp)) {
                match.value = JSON.parse(JSON.stringify({ ...match.value, game_state: e.gameState }))
                simulateOpponentShot(e.gameState.last_shot, e.gameState.pucks)
            } else if (!e.gameState.last_shot) {
                // Sync puck positions FIRST so canvas is up-to-date (only if not simulating)
                if (!isPhysicsRunning.value && !isMyTurn.value) {
                    syncPuckPositions(e.gameState.pucks)
                }
                // Then update match value so turn index switches and controls show AFTER positions are synced
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

// Computeds for turn, players, and perspectives
const players = computed(() => match.value?.game_state?.players || [])
const myPlayerIndex = computed(() => {
    if (!players.value || !auth.user) return 0
    const idx = players.value.findIndex(p => p.user_id == auth.user.id)
    return idx === -1 ? 0 : idx
})
const opponentPlayerIndex = computed(() => (myPlayerIndex.value === 0) ? 1 : 0)

const myInfo = computed(() => players.value[myPlayerIndex.value])
const opponentInfo = computed(() => players.value[opponentPlayerIndex.value])

const isMyTurn = computed(() => {
    if (!match.value || !match.value.game_state) return false
    return match.value.game_state.turn_index === myPlayerIndex.value
})

const myBaselineY = computed(() => {
    // If I'm Player 0, baseline is at bottom (420px), else top (80px)
    return myPlayerIndex.value === 0 ? 420 : 80
})

const opponentBaselineY = computed(() => {
    return myPlayerIndex.value === 0 ? 80 : 420
})

// physics engine initialisation
const initPhysics = () => {
    if (!canvasRef.value) return

    // Screen Dimensions: 500x500 board
    const width = 500
    const height = 500

    engine = Matter.Engine.create({
        gravity: { x: 0, y: 0 } // No gravity for top-down board!
    })
    world = engine.world

    // Add ultra-thick wooden boundaries (static bodies) to prevent high-velocity tunneling/escape
    const t = 200 // Thickness extending outwards off-screen
    const walls = [
        Matter.Bodies.rectangle(width / 2, 20 - t / 2, width + 2 * t, t, { isStatic: true, restitution: 0.9, friction: 0.05 }), // Top
        Matter.Bodies.rectangle(width / 2, height - 20 + t / 2, width + 2 * t, t, { isStatic: true, restitution: 0.9, friction: 0.05 }), // Bottom
        Matter.Bodies.rectangle(20 - t / 2, height / 2, t, height + 2 * t, { isStatic: true, restitution: 0.9, friction: 0.05 }), // Left
        Matter.Bodies.rectangle(width - 20 + t / 2, height / 2, t, height + 2 * t, { isStatic: true, restitution: 0.9, friction: 0.05 }), // Right
    ]
    Matter.Composite.add(world, walls)

    // Pockets sensor configurations (Top-Left, Top-Right, Bottom-Left, Bottom-Right)
    const pocketRadius = 24
    pocketSensors = [
        { x: 30, y: 30, r: pocketRadius },
        { x: 470, y: 30, r: pocketRadius },
        { x: 30, y: 470, r: pocketRadius },
        { x: 470, y: 470, r: pocketRadius }
    ]

    // Create Striker (Not added to physical world until user sets position or opponent shoots)
    strikerBody = Matter.Bodies.circle(strikerX.value, myBaselineY.value, 18, {
        mass: 3.5,
        restitution: 0.85,
        frictionAir: 0.015,
        label: 'striker',
        collisionFilter: {
            mask: 0x0000 // Ghost by default during initial placement to prevent shifting pieces
        }
    })

    // Load Pucks from match game state
    loadPucks()

    // Add and position striker on baseline in physical world
    resetStrikerLocal()

    // No Matter.Runner for perfect monitor-rate independent determinism!
    // We step the engine manually in the render loop with a fixed 60Hz timestep.

    // Render Canvas Loop
    startRenderLoop()
}

const loadPucks = (pucksData = null) => {
    // Clear old puck bodies
    puckBodies.forEach(body => Matter.Composite.remove(world, body))
    puckBodies = []

    const data = pucksData || match.value?.game_state?.pucks || []
    data.forEach(p => {
        if (!p.isPocketed) {
            const body = Matter.Bodies.circle(p.x, p.y, 14, {
                mass: 1.0,
                restitution: 0.85,
                frictionAir: 0.018,
                label: `puck_${p.id}_${p.type}`
            })
            Matter.Composite.add(world, body)
            puckBodies.push(body)
        }
    })
}

const syncPuckPositions = (pucksData) => {
    if (!world) return
    loadPucks(pucksData)
    resetStrikerLocal()
    isPhysicsRunning.value = false
    pocketedThisTurn.value = []
}

// Custom Premium Canvas Rendering
const startRenderLoop = () => {
    const canvas = canvasRef.value
    if (!canvas) return
    const ctx = canvas.getContext('2d')

    const draw = () => {
        if (gameState.value !== 'playing') {
            renderLoop = null
            return
        }

        ctx.save()
        // Perspective flip: Player 1 sees the board rotated 180 degrees so their baseline is at the bottom
        if (myPlayerIndex.value === 1) {
            ctx.translate(250, 250)
            ctx.rotate(Math.PI)
            ctx.translate(-250, -250)
        }

        // 1. Draw Obsidian Space Void Background Board
        const boardGrad = ctx.createRadialGradient(250, 250, 50, 250, 250, 350)
        boardGrad.addColorStop(0, '#0f0e1a')
        boardGrad.addColorStop(1, '#050409')
        ctx.fillStyle = boardGrad
        ctx.fillRect(0, 0, 500, 500)

        // Board inner margins & lines
        ctx.strokeStyle = 'rgba(0, 242, 254, 0.3)'
        ctx.lineWidth = 2
        ctx.strokeRect(20, 20, 460, 460)
        ctx.strokeStyle = 'rgba(255, 0, 127, 0.2)'
        ctx.strokeRect(25, 25, 450, 450)

        // Diagonal lines from corner to center
        ctx.strokeStyle = 'rgba(0, 242, 254, 0.15)'
        ctx.beginPath()
        ctx.moveTo(30, 30)
        ctx.lineTo(180, 180)
        ctx.moveTo(470, 30)
        ctx.lineTo(320, 180)
        ctx.moveTo(30, 470)
        ctx.lineTo(180, 320)
        ctx.moveTo(470, 470)
        ctx.lineTo(320, 320)
        ctx.stroke()

        // 2. Baselines (Where striker slides)
        ctx.strokeStyle = 'rgba(0, 242, 254, 0.4)'
        ctx.lineWidth = 1.5
        // Bottom Baseline
        ctx.beginPath()
        ctx.moveTo(80, 420)
        ctx.lineTo(420, 420)
        ctx.moveTo(80, 415)
        ctx.lineTo(420, 415)
        // Top Baseline
        ctx.moveTo(80, 80)
        ctx.lineTo(420, 80)
        ctx.moveTo(80, 85)
        ctx.lineTo(420, 85)
        ctx.stroke()

        // Baseline Circles (Glowing Cyberpink dots)
        const drawBaselineCircles = (y) => {
            ctx.save()
            ctx.fillStyle = '#ff007f'
            ctx.shadowBlur = 10
            ctx.shadowColor = '#ff007f'
            ctx.beginPath()
            ctx.arc(80, y, 7, 0, Math.PI * 2)
            ctx.arc(420, y, 7, 0, Math.PI * 2)
            ctx.fill()
            ctx.restore()
        }
        drawBaselineCircles(417.5)
        drawBaselineCircles(82.5)

        // 3. Central concentric rings
        ctx.strokeStyle = 'rgba(0, 242, 254, 0.4)'
        ctx.lineWidth = 2
        ctx.beginPath()
        ctx.arc(250, 250, 80, 0, Math.PI * 2)
        ctx.stroke()
        
        ctx.strokeStyle = '#ff007f'
        ctx.beginPath()
        ctx.arc(250, 250, 20, 0, Math.PI * 2)
        ctx.stroke()

        // 4. Draw Pocket Holes (Cyberpink glowing pockets)
        pocketSensors.forEach(p => {
            // Glowing neon-pink border rim
            ctx.strokeStyle = '#ff007f'
            ctx.shadowBlur = 12
            ctx.shadowColor = '#ff007f'
            ctx.lineWidth = 4
            ctx.beginPath()
            ctx.arc(p.x, p.y, p.r + 2, 0, Math.PI * 2)
            ctx.stroke()

            // Pocket hole depth (Black radial gradient)
            const grad = ctx.createRadialGradient(p.x, p.y, 5, p.x, p.y, p.r)
            grad.addColorStop(0, '#050409')
            grad.addColorStop(1, '#1b000f')
            ctx.fillStyle = grad
            ctx.shadowBlur = 0 // turn off shadow for content fill
            ctx.beginPath()
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2)
            ctx.fill()
        })

        // 5. Draw Matter.js Pucks
        puckBodies.forEach(p => {
            const labelParts = p.label.split('_')
            const puckType = labelParts[2] // 'white', 'black', or 'queen'
            const { x, y } = p.position

            ctx.save()
            ctx.beginPath()
            ctx.arc(x, y, 14, 0, Math.PI * 2)

            // Dynamic Gradient mapping for 3D Ivory appearance
            let grad = ctx.createRadialGradient(x - 4, y - 4, 2, x, y, 14)
            if (puckType === 'queen') {
                grad.addColorStop(0, '#ff4d4d')
                grad.addColorStop(0.7, '#cc0000')
                grad.addColorStop(1, '#800000') // Ruby Red
            } else if (puckType === 'white') {
                grad.addColorStop(0, '#ffffff')
                grad.addColorStop(0.7, '#f2e6d9')
                grad.addColorStop(1, '#cca375') // Cream Ivory
            } else {
                grad.addColorStop(0, '#595959')
                grad.addColorStop(0.7, '#262626')
                grad.addColorStop(1, '#0d0d0d') // Charcoal Dark Black
            }

            ctx.fillStyle = grad
            ctx.fill()

            // Shiny metal circular rim
            ctx.strokeStyle = puckType === 'queen' ? '#ffb700' : (puckType === 'white' ? '#00f2fe' : '#ff007f')
            ctx.lineWidth = 1.5
            ctx.stroke()
            ctx.restore()
        })

        // 6. Draw Striker
        if (strikerBody && world.bodies.includes(strikerBody)) {
            const { x, y } = strikerBody.position
            ctx.save()
            ctx.beginPath()
            ctx.arc(x, y, 18, 0, Math.PI * 2)

            // Striker premium glowing metallic/neon-cyan styling
            const grad = ctx.createRadialGradient(x - 5, y - 5, 3, x, y, 18)
            grad.addColorStop(0, '#e0ffff')
            grad.addColorStop(0.5, '#00f2fe')
            grad.addColorStop(1, '#005f66')
            ctx.fillStyle = grad
            ctx.shadowBlur = 15
            ctx.shadowColor = '#00f2fe'
            ctx.fill()

            // Outer cyber-pink glowing metal border
            ctx.strokeStyle = '#ff007f'
            ctx.lineWidth = 2.5
            ctx.stroke()
            ctx.restore()
        }

        // Update power position back-and-forth dynamically on my turn
        if (isMyTurn.value && !isPhysicsRunning.value) {
            powerPosition.value += powerDirection * 1.2
            if (powerPosition.value >= 100) {
                powerPosition.value = 100
                powerDirection = -1
            } else if (powerPosition.value <= 0) {
                powerPosition.value = 0
                powerDirection = 1
            }
        }

        // 7. Draw Aiming Laser Dotted Guidelines (Aim slider driven or Slingshot drag driven)
        if (isMyTurn.value && !isPhysicsRunning.value && strikerBody) {
            const sPos = strikerBody.position
            
            if (isAiming.value) {
                // Slingshot drag-and-hit aiming
                const dx = dragStart.value.x - dragCurrent.value.x
                const dy = dragStart.value.y - dragCurrent.value.y
                const dist = Math.min(Math.sqrt(dx * dx + dy * dy), maxDragDistance)
                const angleRad = Math.atan2(dy, dx)

                // 1. Draw glowing elastic slingshot pull-back rubber band
                ctx.save()
                ctx.strokeStyle = '#ff007f' // Hot neon pink
                ctx.shadowBlur = 15
                ctx.shadowColor = '#ff007f'
                ctx.lineWidth = 1.5 + (dist / maxDragDistance) * 4 // gets thicker as we pull more!
                ctx.beginPath()
                ctx.moveTo(sPos.x, sPos.y)
                ctx.lineTo(dragCurrent.value.x, dragCurrent.value.y)
                ctx.stroke()
                
                // Draw a small glowing anchor spot under pointer
                ctx.fillStyle = '#ff007f'
                ctx.beginPath()
                ctx.arc(dragCurrent.value.x, dragCurrent.value.y, 4, 0, Math.PI * 2)
                ctx.fill()
                ctx.restore()

                // 2. Draw forward aiming laser guideline pointing in shot direction
                if (dist > 15) {
                    ctx.save()
                    ctx.strokeStyle = 'rgba(0, 242, 254, 0.9)' // Glowing neon cyan
                    ctx.shadowBlur = 10
                    ctx.shadowColor = '#00f2fe'
                    ctx.lineWidth = 2.5
                    ctx.setLineDash([6, 6])
                    ctx.beginPath()
                    ctx.moveTo(sPos.x, sPos.y)
                    
                    // Aim laser length grows with pull power
                    const laserLen = 100 + (dist / maxDragDistance) * 180
                    const targetX = sPos.x + Math.cos(angleRad) * laserLen
                    const targetY = sPos.y + Math.sin(angleRad) * laserLen
                    
                    ctx.lineTo(targetX, targetY)
                    ctx.stroke()

                    // Glowing target crosshair dot at far end
                    ctx.fillStyle = '#ff007f'
                    ctx.shadowBlur = 12
                    ctx.shadowColor = '#ff007f'
                    ctx.beginPath()
                    ctx.arc(targetX, targetY, 6, 0, Math.PI * 2)
                    ctx.fill()
                    ctx.restore()
                }
            } else {
                // Traditional Slider-driven aiming
                const visualAngle = Number(aimAngle.value)
                const angleRad = (myPlayerIndex.value === 1) 
                    ? (90 - visualAngle) * Math.PI / 180 
                    : (-90 + visualAngle) * Math.PI / 180

                // Draw aiming laser dotted line pointing forward (220px long)
                ctx.save()
                ctx.strokeStyle = 'rgba(0, 242, 254, 0.9)' // Glowing neon cyan
                ctx.shadowBlur = 8
                ctx.shadowColor = '#00f2fe'
                ctx.lineWidth = 2.5
                ctx.setLineDash([6, 6])
                ctx.beginPath()
                ctx.moveTo(sPos.x, sPos.y)
                ctx.lineTo(sPos.x + Math.cos(angleRad) * 220, sPos.y + Math.sin(angleRad) * 220)
                ctx.stroke()

                // Draw targeting glowing arrow/crosshair at far end
                ctx.fillStyle = '#ff007f' // Hot cyberpink target spot
                ctx.shadowBlur = 10
                ctx.shadowColor = '#ff007f'
                ctx.beginPath()
                ctx.arc(sPos.x + Math.cos(angleRad) * 220, sPos.y + Math.sin(angleRad) * 220, 6, 0, Math.PI * 2)
                ctx.fill()
                ctx.restore()
            }
        }

        ctx.restore()

        // Realtime monitor-rate independent deterministic physics stepping
        if (isPhysicsRunning.value) {
            const currentTime = performance.now()
            let delta = currentTime - lastTime
            lastTime = currentTime
            
            if (delta > 100) delta = 100 // Prevent spiral of death
            
            accumulator += delta
            while (accumulator >= fixedDelta) {
                Matter.Engine.update(engine, fixedDelta)
                physicsTicks++
                accumulator -= fixedDelta
            }
            
            checkPocketCollisions()
            checkAllPucksStopped()
        }

        renderLoop = requestAnimationFrame(draw)
    }

    renderLoop = requestAnimationFrame(draw)
}

// Aiming Click & Drag Handling (Slingshot)
const handleCanvasPointerDown = (e) => {
    if (!isMyTurn.value || isPhysicsRunning.value || !strikerBody) return

    const rect = canvasRef.value.getBoundingClientRect()
    let x = (e.clientX - rect.left) * (500 / rect.width)
    let y = (e.clientY - rect.top) * (500 / rect.height)

    // If I am Player 1, flip mouse coordinates 180 degrees around board center
    if (myPlayerIndex.value === 1) {
        x = 500 - x
        y = 500 - y
    }

    // Check if clicked close to striker to start slingshot dragging
    const sPos = strikerBody.position
    const dist = Math.sqrt((x - sPos.x) * (x - sPos.x) + (y - sPos.y) * (y - sPos.y))
    if (dist < 40) {
        isAiming.value = true
        dragStart.value = { x: sPos.x, y: sPos.y }
        dragCurrent.value = { x, y }
    }
}

const handleCanvasPointerMove = (e) => {
    if (!isAiming.value || !canvasRef.value) return
    const rect = canvasRef.value.getBoundingClientRect()
    let x = (e.clientX - rect.left) * (500 / rect.width)
    let y = (e.clientY - rect.top) * (500 / rect.height)

    if (myPlayerIndex.value === 1) {
        x = 500 - x
        y = 500 - y
    }

    dragCurrent.value = { x, y }
}

const handleCanvasPointerUp = () => {
    if (!isAiming.value) return
    isAiming.value = false

    const dx = dragStart.value.x - dragCurrent.value.x
    const dy = dragStart.value.y - dragCurrent.value.y
    const dist = Math.min(Math.sqrt(dx * dx + dy * dy), maxDragDistance)

    // Shot threshold
    if (dist > 15) {
        hasPlayedThisTurn.value = true // Immediately hide controls!
        const angle = Math.atan2(dy, dx)
        const force = (dist / maxDragDistance) * 100 // Increased striker force multiplier to 100 max

        shootStrikerLocal(strikerBody.position.x, angle, force)

        // Sync shot to backend so opponent can witness the physical event
        submitStrikeBackend(strikerBody.position.x, angle, force)
    }
}

// Physical Shot Simulation Execution
const shootStrikerLocal = (x, angle, force) => {
    if (!strikerBody || !world) return

    // Restore full solid collisions for physical gameplay simulation
    strikerBody.collisionFilter.mask = 0xFFFFFFFF

    // Position striker exactly on baseline
    Matter.Body.setPosition(strikerBody, { x, y: isMyTurn.value ? myBaselineY.value : opponentBaselineY.value })
    Matter.Body.setVelocity(strikerBody, { x: 0, y: 0 })

    if (!world.bodies.includes(strikerBody)) {
        Matter.Composite.add(world, strikerBody)
    }

    // Apply exact force vector (calibrated to 0.0065 for high velocity travel of 2.5x board width)
    const forceVec = {
        x: Math.cos(angle) * force * 0.0065,
        y: Math.sin(angle) * force * 0.0065
    }
    Matter.Body.applyForce(strikerBody, strikerBody.position, forceVec)

    isPhysicsRunning.value = true
    physicsTicks = 0
    pocketedThisTurn.value = []
    lastTime = performance.now()
    accumulator = 0
}

const simulateOpponentShot = (shotData, pucks = null) => {
    if (isMyTurn.value) return
    toast.show("Opponent took a shot!", "info")
    if (pucks) {
        syncPuckPositions(pucks)
    }
    shootStrikerLocal(shotData.x, shotData.angle, shotData.force)
}

// Collisions with Pockets (Sinks)
const checkPocketCollisions = () => {
    // 1. Check pucks
    puckBodies.forEach((body) => {
        const pos = body.position
        pocketSensors.forEach(pocket => {
            const dist = Math.sqrt((pos.x - pocket.x) * (pos.x - pocket.x) + (pos.y - pocket.y) * (pos.y - pocket.y))
            if (dist < pocket.r) {
                // Pocketed! Remove from Matter.js world
                Matter.Composite.remove(world, body)
                puckBodies = puckBodies.filter(b => b !== body)

                // Parse details from label
                const labelParts = body.label.split('_')
                const puckId = parseInt(labelParts[1])
                const puckType = labelParts[2]

                pocketedThisTurn.value.push({ id: puckId, type: puckType })
                toast.show(`Sunk a ${puckType} coin!`, 'success')
            }
        })
    })

    // 2. Check striker scratch
    if (strikerBody && world.bodies.includes(strikerBody)) {
        const sPos = strikerBody.position
        pocketSensors.forEach(pocket => {
            const dist = Math.sqrt((sPos.x - pocket.x) * (sPos.x - pocket.x) + (sPos.y - pocket.y) * (sPos.y - pocket.y))
            if (dist < pocket.r) {
                // Scratch! Remove from world
                Matter.Composite.remove(world, strikerBody)
                toast.show("Scratch! Striker pocketed.", "warning")
            }
        })
    }
}

// Stop Detection & Resting Settle logic
const checkAllPucksStopped = () => {
    if (!isPhysicsRunning.value) return

    // Wait at least 15 frames (approx. 250ms) to ensure physics has updated and bodies are in motion
    physicsTicks++
    if (physicsTicks < 15) return

    // All bodies must have a speed close to 0 to be considered resting
    const speedThreshold = 0.05
    let allStopped = true

    if (strikerBody && world.bodies.includes(strikerBody)) {
        if (strikerBody.speed > speedThreshold) allStopped = false
    }

    puckBodies.forEach(body => {
        if (body.speed > speedThreshold) allStopped = false
    })

    if (allStopped) {
        isPhysicsRunning.value = false
        // Make sure velocities are set to exactly 0 to avoid drifting
        if (strikerBody) Matter.Body.setVelocity(strikerBody, { x: 0, y: 0 })
        puckBodies.forEach(b => Matter.Body.setVelocity(b, { x: 0, y: 0 }))

        // Only active player resolves next turn, settles scores, and sends resting state back to DB
        if (isMyTurn.value && hasPlayedThisTurn.value) {
            resolveRestingState()
        } else if (match.value?.game_state?.turn_index === myPlayerIndex.value) {
            // Opponent has finished their turn and turn shifted to us; load final puck positions
            syncPuckPositions(match.value.game_state.pucks)
        }
    }
}

const resolveRestingState = async () => {
    if (gameState.value !== 'playing') return

    // 1. Map current physical positions of pucks back to game pucks array
    const dbPucks = JSON.parse(JSON.stringify(match.value.game_state.pucks))
    
    // Process any pucks that fell into pockets this turn
    pocketedThisTurn.value.forEach(p => {
        const found = dbPucks.find(dp => Number(dp.id) === Number(p.id))
        if (found) found.isPocketed = true
    })

    // Update coordinates for pucks still active on board
    puckBodies.forEach(body => {
        const labelParts = body.label.split('_')
        const puckId = parseInt(labelParts[1])
        const found = dbPucks.find(dp => Number(dp.id) === Number(puckId))
        if (found) {
            found.x = body.position.x
            found.y = body.position.y
        }
    })

    // 2. Score calculations
    let myScoreChange = 0
    let oppScoreChange = 0
    let queenPocketed = false

    const myColor = myInfo.value.color // 'white' or 'black'
    const oppColor = opponentInfo.value.color

    pocketedThisTurn.value.forEach(p => {
        if (p.type === myColor) {
            myScoreChange += 1
        } else if (p.type === oppColor) {
            oppScoreChange += 1
        } else if (p.type === 'queen') {
            queenPocketed = true
        }
    })

    const newMyScore = myInfo.value.score + myScoreChange
    const newOppScore = opponentInfo.value.score + oppScoreChange

    const scores = []
    scores[myPlayerIndex.value] = newMyScore
    scores[opponentPlayerIndex.value] = newOppScore

    // Check how many of the player's own pieces are still active on the board after the shot
    const remainingMyPieces = dbPucks.filter(p => p.type === myColor && !p.isPocketed).length

    let winnerId = null
    let extraTurnActive = match.value.game_state.extra_turn === true
    let nextTurnIndex = myPlayerIndex.value
    let forceQueenReturn = false

    if (queenPocketed) {
        if (remainingMyPieces === 0 || newMyScore >= 3) {
            // Legal pocket of queen when all own pieces are pocketed!
            winnerId = auth.user.id
            toast.show("👑 Instant Victory! You pocketed the Queen legally!", "success")
        } else {
            // Pocketed queen illegally (early)
            forceQueenReturn = true
            toast.show("⚠️ Early Queen! Queen returned to center. Opponent gets turn + extra turn.", "warning")
            
            // Return Queen to center in dbPucks
            const queenPuck = dbPucks.find(p => p.type === 'queen')
            if (queenPuck) {
                queenPuck.isPocketed = false
                queenPuck.x = 250
                queenPuck.y = 250
            }
            
            // Hand turn to opponent with an extra turn
            nextTurnIndex = opponentPlayerIndex.value
            extraTurnActive = true
        }
    }

    if (!forceQueenReturn) {
        // Normal turn flow if we did not trigger an illegal queen foul
        const pocketedMyColor = pocketedThisTurn.value.some(p => p.type === myColor)
        if (pocketedMyColor) {
            // Kept turn by pocketing own color
            nextTurnIndex = myPlayerIndex.value
            extraTurnActive = false // consume extra turn if they had it
        } else {
            if (extraTurnActive) {
                // Keep turn using the extra turn safety net!
                nextTurnIndex = myPlayerIndex.value
                extraTurnActive = false
                toast.show("Used your extra turn!", "info")
            } else {
                // Hand turn to opponent
                nextTurnIndex = opponentPlayerIndex.value
            }
        }
    }

    // 3. Sync to backend
    try {
        const { data } = await axios.post(`/api/carrom/update-state/${roomCode.value}`, {
            pucks: dbPucks,
            scores: scores,
            next_turn_index: nextTurnIndex,
            winner_id: winnerId,
            extra_turn: extraTurnActive
        })
        
        if (gameState.value !== 'playing') return

        if (data.match.game_state.status === 'finished') {
            match.value = JSON.parse(JSON.stringify(data.match))
            gameState.value = 'finished'
            auth.fetchUser()
        } else {
            // Sync positions first so canvas is up-to-date
            syncPuckPositions(data.match.game_state.pucks)
            // Then update match value so controls show AFTER positions are synced
            match.value = JSON.parse(JSON.stringify(data.match))
        }
    } catch (err) {
        console.error("Failed to update resting state:", err.response?.data || err)
        if (gameState.value === 'playing') {
            toast.show("Error saving resting state", "error")
            resetStrikerLocal()
        }
    }
}

const resetStrikerLocal = () => {
    if (!strikerBody || !world) return
    isAiming.value = false
    hasPlayedThisTurn.value = false

    // Turn striker into a ghost during positioning to prevent shifting adjacent pieces
    strikerBody.collisionFilter.mask = 0x0000

    // Put striker back at default baseline position
    strikerX.value = 250
    Matter.Body.setPosition(strikerBody, { x: 250, y: isMyTurn.value ? myBaselineY.value : opponentBaselineY.value })
    Matter.Body.setVelocity(strikerBody, { x: 0, y: 0 })

    if (!world.bodies.includes(strikerBody)) {
        Matter.Composite.add(world, strikerBody)
    }
}

const handleStrikerSliderInput = () => {
    if (isPhysicsRunning.value || !isMyTurn.value || !strikerBody) return
    
    // Perspective flip: if Player 1, sliding left (lower strikerX) physically places striker to the right (500 - strikerX)
    const physicalX = myPlayerIndex.value === 1 ? (500 - Number(strikerX.value)) : Number(strikerX.value)
    Matter.Body.setPosition(strikerBody, { x: physicalX, y: myBaselineY.value })
}

const submitStrikeBackend = async (x, angle, force) => {
    try {
        const { data } = await axios.post(`/api/carrom/strike/${roomCode.value}`, { x, angle, force })
        match.value = JSON.parse(JSON.stringify(data.match))
        startTimer()
    } catch (err) {
        toast.show(err.response?.data?.message || 'Error submitting strike', 'error')
        hasPlayedThisTurn.value = false
        isPhysicsRunning.value = false
        resetStrikerLocal()
    }
}

const executeStrikeLocal = () => {
    if (isPhysicsRunning.value || !isMyTurn.value || !strikerBody) return

    // Immediately hide HUD controls locally for responsive, smooth UI
    hasPlayedThisTurn.value = true

    // Calculate power: center (50) is max, corners (0 or 100) is min
    const p = powerPosition.value
    const powerMultiplier = 0.1 + 0.9 * (1 - Math.abs(p - 50) / 50)
    const force = powerMultiplier * 100 // Increased maximum force multiplier to 100 max

    // Calculate physical angle relative to baseline
    const visualAngle = Number(aimAngle.value)
    const physicalAngle = (myPlayerIndex.value === 1)
        ? (90 - visualAngle) * Math.PI / 180
        : (-90 + visualAngle) * Math.PI / 180

    shootStrikerLocal(strikerBody.position.x, physicalAngle, force)
    submitStrikeBackend(strikerBody.position.x, physicalAngle, force)
    
    // Reset sliders for next turn
    aimAngle.value = 0
}

const forfeitMatch = async () => {
    if (!window.confirm("Are you sure you want to forfeit? You will lose your bet!")) return
    try {
        await axios.post(`/api/carrom/forfeit/${roomCode.value}`)
        gameState.value = 'finished'
        auth.fetchUser()
    } catch (err) {
        toast.show("Forfeit error", "error")
    }
}

// Watchers for turns
watch([isMyTurn, gameState], ([newTurn, newGameState]) => {
    if (newGameState === 'playing') {
        if (newTurn) {
            if (!isPhysicsRunning.value) {
                syncPuckPositions(match.value?.game_state?.pucks)
            }
        }
        // Always ensure opponent turn polling is active during 'playing' state to catch forfeits
        startOpponentTurnPolling()
    } else {
        if (opponentTurnPollInterval) clearInterval(opponentTurnPollInterval)
    }
}, { immediate: true })

onMounted(() => {
    auth.fetchUser()
    checkActiveMatch()
    window.addEventListener('pointermove', handleCanvasPointerMove)
    window.addEventListener('pointerup', handleCanvasPointerUp)
})

onUnmounted(() => {
    if (renderLoop) cancelAnimationFrame(renderLoop)
    if (runner) Matter.Runner.stop(runner)
    if (engine) Matter.Engine.clear(engine)
    
    if (pollInterval) clearInterval(pollInterval)
    if (timerInterval) clearInterval(timerInterval)
    if (opponentTurnPollInterval) clearInterval(opponentTurnPollInterval)
    if (matchingTimerInterval) clearInterval(matchingTimerInterval)
    if (channel.value) channel.value.unsubscribe()

    window.removeEventListener('pointermove', handleCanvasPointerMove)
    window.removeEventListener('pointerup', handleCanvasPointerUp)
})
</script><template>
    <div class="min-h-screen bg-[#090a0f] text-white flex flex-col font-outfit relative overflow-hidden">
        <!-- Ambient Background Glows -->
        <div class="absolute w-[500px] h-[500px] rounded-full bg-neon-cyan/5 blur-[120px] -top-40 -left-40 animate-pulse pointer-events-none"></div>
        <div class="absolute w-[500px] h-[500px] rounded-full bg-neon-pink/5 blur-[120px] -bottom-40 -right-40 animate-pulse pointer-events-none"></div>

        <!-- Navbar -->
        <div class="fixed top-0 left-0 right-0 p-6 flex items-center justify-between z-[100] bg-[#121623]/80 backdrop-blur-md border-b border-white/5 h-[80px] shadow-lg">
            <button @click="router.push('/')" class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 group transition-all">
                <ChevronLeft class="w-6 h-6 group-hover:-translate-x-1 transition-transform" />
            </button>
            <div class="flex flex-col items-center">
                <h1 class="text-xl font-black italic tracking-tighter uppercase text-neon-cyan">Carrom Club</h1>
                <p class="text-[10px] text-slate-400 font-bold tracking-[0.3em] uppercase">1v1 Champions Cup</p>
            </div>
            <div class="bg-[#121623] px-5 py-2 rounded-2xl border border-white/5 flex items-center gap-3">
                <Coins class="w-4 h-4 text-neon-pink" />
                <span class="font-black text-base">{{ Number(auth.user?.balance || 0).toFixed(2) }}</span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex-1 relative z-10 flex flex-col mt-[80px]">
            <!-- Lobby -->
            <div v-if="gameState === 'lobby'" class="flex-1 flex flex-col items-center justify-center p-6 space-y-10 animate-fade-in">
                <div class="text-center space-y-4">
                    <div class="w-28 h-28 bg-neon-pink/15 rounded-[2.5rem] flex items-center justify-center mx-auto border border-neon-pink/20 shadow-[0_0_40px_rgba(255,0,127,0.15)] animate-pulse">
                        <Trophy class="w-14 h-14 text-neon-pink" />
                    </div>
                    <h2 class="text-4xl font-black italic uppercase tracking-wider text-white">PvP Carrom League</h2>
                </div>
                <div class="w-full max-w-sm space-y-6">
                    <div class="relative group">
                        <span class="absolute left-8 top-1/2 -translate-y-1/2 text-neon-cyan font-black text-2xl">Rs.</span>
                        <input type="number" v-model="betAmount" class="w-full bg-[#121623]/60 border-2 border-white/10 rounded-[2.5rem] py-8 pl-24 text-4xl font-black focus:border-neon-cyan/50 focus:shadow-[0_0_20px_rgba(0,242,254,0.15)] text-neon-cyan outline-none transition-all">
                    </div>
                    <button @click="joinMatch" :disabled="processing" class="w-full bg-neon-pink text-white font-black py-7 rounded-[2.5rem] text-2xl shadow-[0_10px_0_#99004d] shadow-[0_0_30px_rgba(255,0,127,0.4)] hover:scale-[1.02] active:translate-y-2 active:shadow-none transition-all uppercase italic tracking-wider cursor-pointer">
                        {{ processing ? 'Finding Opponent...' : 'Enter Tournament' }}
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
                                <span><strong>Color Assignments</strong>: You are assigned <strong>White</strong> or <strong>Black</strong> pieces (indicated in game HUD).</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-neon-pink">★</span>
                                <span><strong>Turn Rules</strong>: Pocketing your <strong>own piece</strong> grants an extra turn. Pocketing an opponent's piece scores for them and hands them the turn.</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-neon-pink">★</span>
                                <span><strong>👑 QUEEN PREREQUISITE</strong>: The Queen can <strong>ONLY</strong> be pocketed after pocketing all 3 of your pieces. Pocketing it legally wins the match!</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-neon-pink">★</span>
                                <span><strong>⚠️ EARLY QUEEN FOUL</strong>: Pocketing the Queen early returns it to the center, ends your turn, and awards your opponent an <strong>extra turn</strong> safety shot!</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="text-neon-pink">★</span>
                                <span><strong>Timeout limit</strong>: You have 60 seconds to take your shot or you forfeit your bet amount.</span>
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
                    <h2 class="text-3xl font-black uppercase italic tracking-wider">Searching Matchmaking...</h2>
                    <p class="text-neon-cyan font-bold text-xs uppercase tracking-widest">Room Code: {{ roomCode }}</p>
                    <p class="text-slate-400 font-bold text-xs uppercase tracking-widest font-mono">Timer: {{ matchingTimeElapsed }}s</p>
                </div>
                
                <!-- Cancel Match Options -->
                <div v-if="matchingTimeElapsed >= 20" class="w-full max-w-sm space-y-4 animate-fade-in">
                    <div class="text-center text-rose-400 font-bold text-sm bg-rose-500/10 p-4 rounded-2xl border border-rose-500/10">
                        ⚠️ High matching queue. You can cancel and refund anytime.
                    </div>
                    <button @click="cancelMatchmaking" :disabled="cancelling" class="w-full bg-[#090a0f] border border-rose-500/30 text-rose-400 font-black py-6 rounded-[2.5rem] text-xl shadow-[0_0_20px_rgba(239,68,68,0.1)] active:translate-y-2 active:shadow-none transition-all uppercase italic cursor-pointer">
                        {{ cancelling ? 'Cancelling...' : 'Cancel & Refund' }}
                    </button>
                </div>
            </div>

            <!-- Playing -->
            <div v-else-if="gameState === 'playing'" class="flex-1 flex flex-col w-full">
                
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start w-full max-w-5xl mx-auto p-4 flex-1">
                    
                    <!-- Left Column: Carrom Board & Instructions -->
                    <div class="lg:col-span-6 xl:col-span-7 flex flex-col gap-4 w-full items-center justify-center">
                        <!-- Square Board Container -->
                        <div class="relative w-full max-w-[460px] aspect-square bg-[#121623] rounded-3xl p-3 border-4 border-white/10 shadow-[0_20px_50px_rgba(0,0,0,0.8)]">
                            <!-- Canvas -->
                            <canvas 
                                ref="canvasRef" 
                                width="500" 
                                height="500" 
                                class="w-full h-full rounded-2xl bg-[#0d0c15] shadow-inner cursor-crosshair"
                                @pointerdown="handleCanvasPointerDown"
                            ></canvas>

                            <!-- Local Overlay guides when physics is simulating -->
                            <div v-if="isPhysicsRunning" class="absolute inset-0 bg-black/40 pointer-events-none flex items-center justify-center rounded-2xl backdrop-blur-sm">
                                <span class="text-xs uppercase tracking-[0.2em] font-black text-neon-pink bg-[#121623]/80 px-5 py-2.5 rounded-full border border-white/10 shadow-[0_0_20px_rgba(255,0,127,0.3)] animate-pulse">
                                    Simulating Strike...
                                </span>
                            </div>
                        </div>

                        <!-- Bottom Instructions -->
                        <div class="w-full max-w-[460px] p-4 text-center bg-[#121623]/60 border border-white/5 rounded-2xl backdrop-blur-md">
                            <p v-if="isMyTurn && !isPhysicsRunning" class="text-xs text-slate-400 font-bold uppercase tracking-wider animate-pulse flex items-center justify-center gap-2">
                                <Play class="w-3.5 h-3.5 text-neon-cyan" />
                                Time the pulsing bar in the center MAX zone and hit STRIKE!
                            </p>
                            <p v-else-if="isPhysicsRunning" class="text-xs text-slate-500 font-bold uppercase tracking-wider animate-pulse">
                                Simulating physics board in real-time...
                            </p>
                            <p v-else class="text-xs text-slate-500 font-bold uppercase tracking-wider animate-pulse">
                                Wait for opponent shot vector...
                            </p>
                        </div>
                    </div>

                    <!-- Right Column: Scores HUD & Interactive Sliders -->
                    <div class="lg:col-span-6 xl:col-span-5 flex flex-col gap-4 w-full">
                        <!-- Top HUD -->
                        <div class="bg-[#121623]/80 backdrop-blur-xl border border-white/10 p-4 px-6 rounded-2xl flex items-center justify-between shadow-2xl">
                            <!-- My Score Details & Assigned Color Indicator -->
                            <div class="flex items-center gap-4">
                                <!-- You Color & Score -->
                                <div class="flex flex-col items-center">
                                    <span class="text-[8px] text-slate-400 font-black uppercase tracking-widest mb-1">YOU</span>
                                    <div class="flex items-center gap-2">
                                        <div 
                                            class="w-4 h-4 rounded-full border shadow-inner shrink-0"
                                            :class="myInfo?.color === 'white' ? 'bg-white border-zinc-300' : 'bg-zinc-950 border-zinc-800'"
                                            :title="`Your piece color: ${myInfo?.color}`"
                                        ></div>
                                        <span class="text-3xl font-black text-neon-cyan font-mono italic leading-none">{{ myInfo?.score || 0 }}</span>
                                    </div>
                                </div>

                                <div class="text-slate-500 font-black text-[10px] uppercase tracking-widest">VS</div>

                                <!-- Opponent Color & Score -->
                                <div class="flex flex-col items-center">
                                    <span class="text-[8px] text-slate-400 font-black uppercase tracking-widest mb-1">OPPONENT</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl font-black text-slate-400 font-mono italic leading-none">{{ opponentInfo?.score || 0 }}</span>
                                        <div 
                                            class="w-4 h-4 rounded-full border shadow-inner shrink-0"
                                            :class="opponentInfo?.color === 'white' ? 'bg-white border-zinc-300' : 'bg-zinc-950 border-zinc-800'"
                                            :title="`Opponent piece color: ${opponentInfo?.color}`"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions & Status -->
                            <div class="flex flex-col items-end gap-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-neon-cyan shadow-[0_0_8px_rgba(0,242,254,0.8)]" :class="isMyTurn ? 'animate-pulse' : ''"></span>
                                    <span class="text-[10px] font-black uppercase tracking-wider">
                                        {{ isMyTurn ? 'Your Turn' : "Opponent's Turn" }}
                                    </span>
                                </div>
                                <div v-if="match?.game_state?.extra_turn" class="bg-[#102a1e]/80 border border-emerald-500/30 px-2 py-0.5 rounded-full text-[8px] font-black text-emerald-400 tracking-wider uppercase animate-pulse shadow-[0_0_10px_rgba(16,185,129,0.2)] shrink-0">
                                    ⚡ Extra Shot
                                </div>
                                <div class="flex gap-2 items-center">
                                    <div class="bg-[#121623]/80 border border-white/10 px-2.5 py-0.5 rounded-full text-[10px] font-bold text-neon-pink font-mono">
                                        {{ remainingTime }}s
                                    </div>
                                    <button @click="forfeitMatch" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition-colors">
                                        Forfeit
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive HUD Controls -->
                        <div v-if="isMyTurn && !isPhysicsRunning && !hasPlayedThisTurn" class="bg-[#121623]/80 border border-white/10 rounded-3xl p-5 space-y-5 shadow-2xl animate-fade-in backdrop-blur-md">
                            <!-- Control 1: Position Slider -->
                            <div class="flex items-center gap-4">
                                <div class="p-2.5 bg-white/5 border border-white/5 rounded-xl text-neon-cyan">
                                    <Sliders class="w-4 h-4" />
                                </div>
                                <div class="flex-1 flex flex-col">
                                    <div class="flex justify-between text-[10px] text-slate-400 uppercase font-black tracking-wider mb-1">
                                        <span>Place Striker</span>
                                        <span class="text-neon-cyan font-bold font-mono">Pos: {{ strikerX }}</span>
                                    </div>
                                    <input 
                                        type="range" 
                                        v-model="strikerX" 
                                        min="100" 
                                        max="400" 
                                        step="1"
                                        class="w-full h-1.5 bg-[#090a0f] rounded-lg appearance-none cursor-pointer accent-neon-cyan"
                                        @input="handleStrikerSliderInput"
                                    />
                                </div>
                            </div>

                            <!-- Control 2: Aim Angle Slider -->
                            <div class="flex items-center gap-4">
                                <div class="p-2.5 bg-white/5 border border-white/5 rounded-xl text-neon-cyan">
                                    <Play class="w-4 h-4 rotate-[-90deg]" />
                                </div>
                                <div class="flex-1 flex flex-col">
                                    <div class="flex justify-between text-[10px] text-slate-400 uppercase font-black tracking-wider mb-1">
                                        <span>Target Aim Angle</span>
                                        <span class="text-neon-cyan font-black font-mono">{{ aimAngle }}°</span>
                                    </div>
                                    <div class="flex justify-between text-[8px] text-slate-500 font-bold mb-1 uppercase tracking-wider">
                                        <span>LEFT</span>
                                        <span>STRAIGHT</span>
                                        <span>RIGHT</span>
                                    </div>
                                    <input 
                                        type="range" 
                                        v-model="aimAngle" 
                                        min="-60" 
                                        max="60" 
                                        step="1"
                                        class="w-full h-1.5 bg-[#090a0f] rounded-lg appearance-none cursor-pointer accent-neon-cyan"
                                    />
                                </div>
                            </div>

                            <!-- Control 3: Pulsing Power Bar -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-[10px] text-slate-400 uppercase font-black tracking-wider">
                                    <span>Strike Force Indicator</span>
                                    <span class="text-neon-cyan font-black font-mono">
                                        Power: {{ Math.round((0.1 + 0.9 * (1 - Math.abs(powerPosition - 50) / 50)) * 100) }}%
                                    </span>
                                </div>
                                <!-- Pulse Bar Track -->
                                <div class="relative w-full h-6 bg-[#090a0f] border border-white/5 rounded-full overflow-hidden flex items-center px-1">
                                    <!-- Power Zone Gradients -->
                                    <div class="absolute inset-0 bg-gradient-to-r from-rose-600 via-neon-cyan to-rose-600 opacity-60"></div>
                                    <div class="absolute inset-y-0 left-[40%] right-[40%] bg-neon-cyan blur-sm opacity-50"></div>
                                    
                                    <!-- Center Target Indicator -->
                                    <div class="absolute inset-y-0 left-[48%] right-[48%] bg-white/20 border-x border-neon-cyan/30 flex items-center justify-center">
                                        <span class="text-[8px] font-black text-white scale-75 text-center">MAX</span>
                                    </div>

                                    <!-- Pulsing slider pin indicator -->
                                    <div 
                                        class="absolute w-4 h-full bg-white rounded-full shadow-[0_0_10px_#00f2fe] border border-[#090a0f] transition-all duration-[16ms]"
                                        :style="{ left: `calc(${powerPosition}% - 8px)` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Control 4: Submit Strike Button -->
                            <button 
                                @click="executeStrikeLocal" 
                                class="w-full py-4 bg-neon-pink hover:bg-neon-pink/95 text-white border border-neon-pink/30 font-black text-xl rounded-2xl shadow-[0_8px_0_#99004d] shadow-[0_0_20px_rgba(255,0,127,0.35)] hover:scale-[1.01] active:translate-y-1 active:shadow-none transition-all uppercase italic tracking-wider flex items-center justify-center gap-3 cursor-pointer"
                            >
                                <Trophy class="w-5 h-5 text-white animate-bounce" />
                                Release & Strike!
                            </button>
                        </div>

                        <!-- Waiting Card (Non-active turns or Simulating) -->
                        <div v-else class="bg-[#121623]/40 border border-white/5 rounded-3xl p-8 text-center flex flex-col items-center justify-center space-y-4 shadow-xl">
                            <span class="w-10 h-10 rounded-full border-4 border-t-neon-pink border-r-neon-pink border-white/5 border-l-white/5 inline-block animate-spin shadow-[0_0_10px_rgba(255,0,127,0.15)]"></span>
                            <p class="text-xs text-slate-400 font-black uppercase tracking-[0.2em] animate-pulse">
                                {{ isPhysicsRunning ? 'Strike in progress...' : "Waiting for opponent..." }}
                            </p>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Finished -->
            <div v-else-if="gameState === 'finished'" class="absolute inset-0 bg-[#090a0f]/95 z-[200] flex items-center justify-center p-6 backdrop-blur-3xl">
                <div class="w-full max-w-md bg-[#121623]/90 border border-white/10 rounded-[4rem] p-12 text-center space-y-10 shadow-[0_0_50px_rgba(0,0,0,0.8)]">
                    <h2 class="text-6xl font-black italic uppercase tracking-wider"
                        :class="match?.game_state?.winner_id == auth.user?.id ? 'text-neon-cyan shadow-[0_0_30px_rgba(0,242,254,0.3)]' : 'text-neon-pink shadow-[0_0_30px_rgba(255,0,127,0.3)]'">
                        {{ match?.game_state?.winner_id == auth.user?.id ? 'VICTORY' : 'DEFEAT' }}
                    </h2>

                    <div class="bg-black/40 rounded-[2.5rem] p-8 border border-white/5 space-y-6">
                        <div class="flex justify-between items-center">
                            <div class="text-left">
                                <p class="text-[10px] text-slate-500 uppercase font-black">You</p>
                                <p class="text-4xl font-black text-white font-mono italic">{{ myInfo?.score || 0 }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-slate-500 uppercase font-black">Opponent</p>
                                <p class="text-4xl font-black text-slate-400 font-mono italic">{{ opponentInfo?.score || 0 }}</p>
                            </div>
                        </div>
                        <div v-if="match?.game_state?.winner_id == auth.user?.id" class="pt-6 border-t border-white/5">
                            <div class="text-5xl font-black text-neon-cyan font-mono italic">Rs. {{ Number(match?.game_state?.payout || 0).toFixed(2) }}</div>
                        </div>
                        <div class="pt-6 border-t border-white/5 text-slate-500 text-sm font-bold uppercase tracking-wider" v-else>
                            Better luck next time!
                        </div>
                    </div>
                    <button @click="router.push('/')" class="w-full bg-neon-cyan text-black py-7 rounded-[2.5rem] font-black text-2xl uppercase italic tracking-wider cursor-pointer shadow-[0_0_20px_rgba(0,242,254,0.3)] hover:scale-105 transition-all">Exit Arena</button>
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
::-webkit-scrollbar { display: none; }
</style>
