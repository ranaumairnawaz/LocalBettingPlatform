<template>
  <div class="min-h-screen bg-[#121212] text-white flex flex-col font-sans overflow-x-hidden pb-12">
    <!-- Shared Vehicle Gradient Definitions -->
    <svg style="display: none;">
      <defs>
        <!-- Sports Car Gradient -->
        <linearGradient id="sportsBody" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#dc2626" />
          <stop offset="50%" stop-color="#ef4444" />
          <stop offset="100%" stop-color="#991b1b" />
        </linearGradient>
        <linearGradient id="sportsStripe" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#ffffff" />
          <stop offset="100%" stop-color="#cbd5e1" />
        </linearGradient>
        
        <!-- Taxi Gradient -->
        <linearGradient id="taxiBody" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#f59e0b" />
          <stop offset="50%" stop-color="#fbbf24" />
          <stop offset="100%" stop-color="#d97706" />
        </linearGradient>
        <linearGradient id="windowGlass" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#1e293b" />
          <stop offset="100%" stop-color="#0f172a" />
        </linearGradient>
        
        <!-- Police Gradient -->
        <linearGradient id="policeCab" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#ffffff" />
          <stop offset="100%" stop-color="#e2e8f0" />
        </linearGradient>
        
        <!-- Cargo Truck Gradients -->
        <linearGradient id="truckCab" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#ea580c" />
          <stop offset="50%" stop-color="#f97316" />
          <stop offset="100%" stop-color="#c2410c" />
        </linearGradient>
        <linearGradient id="cargoBed" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#e2e8f0" />
          <stop offset="50%" stop-color="#f8fafc" />
          <stop offset="100%" stop-color="#94a3b8" />
        </linearGradient>
        
        <!-- Sedan Gradient -->
        <linearGradient id="sedanBody" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#0284c7" />
          <stop offset="50%" stop-color="#38bdf8" />
          <stop offset="100%" stop-color="#0369a1" />
        </linearGradient>
        
        <!-- Fire Engine Gradients -->
        <linearGradient id="fireBody" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#b91c1c" />
          <stop offset="50%" stop-color="#ef4444" />
          <stop offset="100%" stop-color="#991b1b" />
        </linearGradient>
        <linearGradient id="ladderGrad" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#94a3b8" />
          <stop offset="100%" stop-color="#475569" />
        </linearGradient>
      </defs>
    </svg>

    <!-- Header (Premium Fixed Bar) -->
    <header class="w-full bg-[#1a1a1a] border-b border-white/5 py-4 px-6 flex justify-between items-center fixed top-0 left-0 right-0 z-50 shadow-md">
      <div class="flex items-center gap-3">
        <button 
          @click="$router.push('/')" 
          class="p-2 hover:bg-white/5 active:scale-95 transition rounded-full text-zinc-400 hover:text-white"
        >
          <ArrowLeft size="20" />
        </button>
        <div class="flex flex-col">
          <h1 class="text-lg font-black italic tracking-wider text-primary uppercase">{{ gameTitle }}</h1>
          <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest">ARCADE SURVIVAL</span>
        </div>
      </div>

      <div class="bg-zinc-900 border border-white/5 px-4 py-2 rounded-2xl flex items-center gap-3 shadow-inner">
        <div class="flex flex-col items-end">
          <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Your Balance</span>
          <span class="text-primary font-black text-sm sm:text-base">Rs. {{ parseFloat(auth.user?.balance || 0).toFixed(2) }}</span>
        </div>
        <button 
          @click="$router.push('/wallet')" 
          class="bg-primary text-black hover:bg-yellow-400 active:scale-95 rounded-xl p-1.5 transition-all shadow-md"
        >
          <Plus size="18" />
        </button>
      </div>
    </header>

    <div class="max-w-6xl w-full mx-auto p-4 flex-1 flex flex-col gap-6 pt-[80px]">
      
      <!-- Jackpot Display -->
      <div class="relative w-full bg-zinc-900/60 border border-white/5 rounded-3xl p-6 text-center shadow-lg overflow-hidden flex flex-col items-center justify-center group">
        <!-- Glowing background pulse -->
        <div class="absolute -right-20 -bottom-20 w-48 h-48 bg-yellow-500/5 rounded-full blur-3xl group-hover:scale-150 transition-transform"></div>
        <span class="text-[10px] md:text-xs font-black uppercase tracking-widest text-primary/80 mb-1 animate-pulse">👑 MEGA PROGRESSIVE JACKPOT 👑</span>
        <span class="text-3xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-500 via-yellow-200 to-yellow-500 drop-shadow-[0_0_20px_rgba(234,179,8,0.3)]">
          Rs. {{ jackpot }}
        </span>
      </div>

      <div class="w-full flex flex-col lg:grid lg:grid-cols-12 gap-6 items-start">
        <!-- LEFT COLUMN: Main Game Viewport -->
        <div class="lg:col-span-7 flex flex-col gap-6 w-full">
          <!-- Main Game Viewport (Fixed aspect, styled screen cabinet) -->
          <div class="relative w-full bg-zinc-950 rounded-[2.5rem] p-4 md:p-6 border-4 border-zinc-800 shadow-2xl overflow-hidden">
            <!-- Arcade inner shadow overlay -->
            <div class="absolute inset-0 bg-gradient-to-b from-white/5 via-transparent to-black/30 pointer-events-none z-30"></div>
            
            <!-- Viewport wrapper -->
            <div class="w-full relative overflow-hidden h-60 sm:h-72 rounded-3xl bg-[#141416] border border-white/5 shadow-inner">
              
              <!-- Road Track (Scrolls/translates horizontally based on chicken position) -->
              <div 
                class="relative flex items-stretch h-full transition-transform duration-500 ease-out select-none w-[650%]"
                :style="{ transform: `translateX(${-roadScrollOffset}%)` }"
              >
                <!-- 1. Start Pasture (Column index 0) -->
                <div class="w-[3.846%] bg-gradient-to-b from-[#14532d] via-[#166534] to-[#14532d] border-r-4 border-emerald-500/30 relative flex flex-col items-center justify-center overflow-hidden">
                  <!-- Checkered Starting Ribbon -->
                  <div class="absolute right-0 top-0 bottom-0 w-[4px] bg-[repeating-conic-gradient(#000_0_25%,#fff_0_50%)] [background-size:4px_4px] z-20 opacity-75"></div>
                  <div class="absolute inset-0 bg-[radial-gradient(#22c55e_1px,transparent_1px)] [background-size:12px_12px] opacity-20"></div>
                  
                  <!-- Cute flower scatter on pasture -->
                  <span class="absolute top-4 left-2 text-[10px] opacity-80 select-none animate-pulse">🌸</span>
                  <span class="absolute bottom-4 right-3 text-[10px] opacity-80 select-none animate-pulse">🌼</span>
                  
                  <span class="text-emerald-300/85 font-black tracking-widest -rotate-90 text-[9px] sm:text-[11px] uppercase flex items-center gap-1.5 whitespace-nowrap z-10 filter drop-shadow-[0_2px_2px_rgba(0,0,0,0.5)]">
                    🏁 START FIELD
                  </span>
                </div>

                <!-- 2. The 24 Traffic Lanes (Columns index 1 to 24) -->
                <div 
                  v-for="(mult, index) in multipliers" 
                  :key="index"
                  class="w-[3.846%] border-r border-white/5 relative flex flex-col items-center justify-between py-6 transition-all duration-300 overflow-hidden shadow-[inset_0_0_20px_rgba(0,0,0,0.6)]"
                  :class="[
                    currentLane > index + 1 
                      ? 'bg-zinc-950/65 opacity-80' 
                      : currentLane === index + 1 && gameActive
                        ? 'bg-yellow-500/5 shadow-[inset_0_0_30px_rgba(234,179,8,0.06)]'
                        : 'bg-gradient-to-b from-[#18181c] via-[#242429] to-[#18181c]'
                  ]"
                >
                  <!-- Top Grassy Shoulder -->
                  <div class="absolute top-0 left-0 w-full h-3.5 bg-gradient-to-b from-emerald-800 to-emerald-700 z-10 border-b border-emerald-600/40"></div>
                  <div class="absolute top-3.5 left-0 w-full h-[2px] bg-zinc-500/35 z-10"></div>
                  
                  <!-- Bottom Grassy Shoulder -->
                  <div class="absolute bottom-0 left-0 w-full h-3.5 bg-gradient-to-t from-emerald-800 to-emerald-700 z-10 border-t border-emerald-600/40"></div>
                  <div class="absolute bottom-3.5 left-0 w-full h-[2px] bg-zinc-500/35 z-10"></div>

                  <!-- Zebra Pedestrian Crosswalk stripes (Top & Bottom) -->
                  <div class="absolute top-[15px] left-0 w-full h-4.5 flex justify-around opacity-[0.25] pointer-events-none z-0 px-1">
                    <span class="w-[2.5px] h-full bg-white rounded-sm"></span>
                    <span class="w-[2.5px] h-full bg-white rounded-sm"></span>
                    <span class="w-[2.5px] h-full bg-white rounded-sm"></span>
                  </div>
                  <div class="absolute bottom-[15px] left-0 w-full h-4.5 flex justify-around opacity-[0.25] pointer-events-none z-0 px-1">
                    <span class="w-[2.5px] h-full bg-white rounded-sm"></span>
                    <span class="w-[2.5px] h-full bg-white rounded-sm"></span>
                    <span class="w-[2.5px] h-full bg-white rounded-sm"></span>
                  </div>

                  <!-- Vertical Highway lane dashes down the center -->
                  <div class="absolute top-8 bottom-8 left-1/2 -translate-x-1/2 w-[2px] flex flex-col justify-around pointer-events-none z-0 opacity-20">
                    <div class="w-[2px] h-2 bg-yellow-400"></div>
                    <div class="w-[2px] h-2 bg-yellow-400"></div>
                    <div class="w-[2px] h-2 bg-yellow-400"></div>
                    <div class="w-[2px] h-2 bg-yellow-400"></div>
                  </div>

                  <!-- Ambient Continuous Traffic (Continuous CSS moving cars) -->
                  <div class="absolute inset-y-0 w-full overflow-hidden z-10 pointer-events-none flex flex-col justify-around py-4">
                    <!-- Vehicle A (Only if not crashed here) -->
                    <div 
                      v-if="crashingLane !== index + 1"
                      class="absolute left-0 w-full flex justify-center select-none"
                      :class="(index % 2 === 0) ? 'animate-traffic-down' : 'animate-traffic-up'"
                      :style="{ 
                        animationDuration: `${2.8 + (index % 4) * 0.5}s`, 
                        animationDelay: `-${(index * 1.3) % 4}s`,
                        animationPlayState: (currentLane === index + 1 && gameActive && !isCrashed) ? 'paused' : 'running'
                      }"
                    >
                      <div 
                        class="w-[20px] sm:w-[26px] h-[42px] sm:h-[54px] filter drop-shadow-[0_2.5px_4px_rgba(0,0,0,0.65)]"
                        :style="{ transform: (index % 2 === 0) ? 'rotate(180deg)' : 'none' }"
                      >
                        <!-- Sports Car SVG -->
                        <svg v-if="getVehicleType(index, 'A') === 'sports'" viewBox="0 0 32 64" class="w-full h-full overflow-visible">
                          <rect x="1" y="9" width="3" height="9" rx="1.5" fill="#18181b" />
                          <rect x="28" y="9" width="3" height="9" rx="1.5" fill="#18181b" />
                          <rect x="1" y="47" width="3" height="9" rx="1.5" fill="#18181b" />
                          <rect x="28" y="47" width="3" height="9" rx="1.5" fill="#18181b" />
                          <path d="M 6 3 C 10 2, 22 2, 26 3 C 28 10, 28 54, 26 60 C 22 62, 10 62, 6 60 C 4 54, 4 10, 6 3 Z" fill="url(#sportsBody)" />
                          <rect x="12" y="3" width="2" height="15" fill="url(#sportsStripe)" />
                          <rect x="18" y="3" width="2" height="15" fill="url(#sportsStripe)" />
                          <rect x="12" y="48" width="2" height="12" fill="url(#sportsStripe)" />
                          <rect x="18" y="48" width="2" height="12" fill="url(#sportsStripe)" />
                          <path d="M 7 21 C 7 17, 25 17, 25 21 L 23 26 L 9 26 Z" fill="#0f172a" />
                          <path d="M 8 46 C 8 48, 24 48, 24 46 L 22 42 L 10 42 Z" fill="#0f172a" />
                          <path d="M 5.5 27 L 8.5 27 L 8.5 41 L 5.5 41 Z" fill="#0f172a" />
                          <path d="M 26.5 27 L 23.5 27 L 23.5 41 L 26.5 41 Z" fill="#0f172a" />
                          <rect x="4" y="58" width="24" height="3" rx="1" fill="#991b1b" />
                          <rect x="5" y="57" width="2" height="2" fill="#1e293b" />
                          <rect x="25" y="57" width="2" height="2" fill="#1e293b" />
                          <ellipse cx="8" cy="4" rx="2" ry="1" fill="#38bdf8" />
                          <ellipse cx="24" cy="4" rx="2" ry="1" fill="#38bdf8" />
                          <rect x="7" y="60" width="3" height="1" fill="#f87171" />
                          <rect x="22" y="60" width="3" height="1" fill="#f87171" />
                        </svg>

                        <!-- Taxi SVG -->
                        <svg v-else-if="getVehicleType(index, 'A') === 'taxi'" viewBox="0 0 32 64" class="w-full h-full overflow-visible">
                          <rect x="1" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="1" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <path d="M 4 20 Q 1 20 2 18" stroke="#d97706" stroke-width="1.5" fill="none" />
                          <path d="M 28 20 Q 31 20 30 18" stroke="#d97706" stroke-width="1.5" fill="none" />
                          <rect x="4" y="3" width="24" height="58" rx="6" fill="url(#taxiBody)" />
                          <path d="M 6 20 C 6 17, 26 17, 26 20 L 24 25 L 8 25 Z" fill="url(#windowGlass)" />
                          <path d="M 7 47 C 7 49, 25 49, 25 47 L 23 43 L 9 43 Z" fill="url(#windowGlass)" />
                          <path d="M 5 26 L 8 26 L 8 42 L 5 42 Z" fill="url(#windowGlass)" />
                          <path d="M 27 26 L 24 26 L 24 42 L 27 42 Z" fill="url(#windowGlass)" />
                          <rect x="8" y="24" width="16" height="20" rx="2" fill="#f59e0b" />
                          <rect x="11" y="32" width="10" height="4" rx="1" fill="#000000" />
                          <rect x="12" y="33" width="8" height="2" rx="0.5" fill="#fbbf24" />
                          <ellipse cx="8" cy="5" rx="2" ry="1.5" fill="#fef08a" />
                          <ellipse cx="24" cy="5" rx="2" ry="1.5" fill="#fef08a" />
                          <rect x="6" y="59" width="4" height="1.5" fill="#ef4444" />
                          <rect x="22" y="59" width="4" height="1.5" fill="#ef4444" />
                          <path d="M 10 10 L 22 10" stroke="#b45309" stroke-width="1.5" stroke-linecap="round" />
                          <path d="M 12 13 L 20 13" stroke="#b45309" stroke-width="1" stroke-linecap="round" />
                        </svg>

                        <!-- Police SVG -->
                        <svg v-else-if="getVehicleType(index, 'A') === 'police'" viewBox="0 0 32 64" class="w-full h-full overflow-visible">
                          <rect x="1" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="1" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="4" y="3" width="24" height="58" rx="6" fill="#0f172a" />
                          <rect x="4" y="18" width="24" height="28" fill="url(#policeCab)" />
                          <path d="M 6 20 C 6 17, 26 17, 26 20 L 24 25 L 8 25 Z" fill="#020617" />
                          <path d="M 7 47 C 7 49, 25 49, 25 47 L 23 43 L 9 43 Z" fill="#020617" />
                          <path d="M 5 26 L 8 26 L 8 42 L 5 42 Z" fill="#020617" />
                          <path d="M 27 26 L 24 26 L 24 42 L 27 42 Z" fill="#020617" />
                          <rect x="8" y="24" width="16" height="20" rx="2" fill="url(#policeCab)" />
                          <rect x="7" y="32" width="18" height="4" rx="1" fill="#1e293b" />
                          <rect x="8" y="33" width="8" height="2" rx="0.5" fill="#3b82f6" class="animate-pulse" />
                          <rect x="16" y="33" width="8" height="2" rx="0.5" fill="#ef4444" class="animate-pulse" style="animation-delay: 0.25s;" />
                          <rect x="12" y="10" width="8" height="4" rx="1" fill="#ffffff" opacity="0.1" />
                          <text x="16" y="14" font-size="5" font-weight="900" fill="#ffffff" text-anchor="middle" font-family="sans-serif">POLICE</text>
                          <ellipse cx="8" cy="5" rx="2" ry="1.5" fill="#ffffff" />
                          <ellipse cx="24" cy="5" rx="2" ry="1.5" fill="#ffffff" />
                          <rect x="6" y="59" width="4" height="1.5" fill="#dc2626" />
                          <rect x="22" y="59" width="4" height="1.5" fill="#dc2626" />
                        </svg>

                        <!-- Sedan SVG -->
                        <svg v-else-if="getVehicleType(index, 'A') === 'sedan'" viewBox="0 0 32 64" class="w-full h-full overflow-visible">
                          <rect x="1" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="1" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="4" y="3" width="24" height="58" rx="6" fill="url(#sedanBody)" />
                          <path d="M 6 20 C 6 17, 26 17, 26 20 L 24 25 L 8 25 Z" fill="#0f172a" />
                          <path d="M 7 47 C 7 49, 25 49, 25 47 L 23 43 L 9 43 Z" fill="#0f172a" />
                          <path d="M 5 26 L 8 26 L 8 42 L 5 42 Z" fill="#0f172a" />
                          <path d="M 27 26 L 24 26 L 24 42 L 27 42 Z" fill="#0f172a" />
                          <rect x="8" y="24" width="16" height="20" rx="2" fill="#0284c7" />
                          <rect x="11" y="27" width="10" height="7" rx="1" fill="#0f172a" opacity="0.6" />
                          <ellipse cx="8" cy="5" rx="2" ry="1.5" fill="#fef08a" />
                          <ellipse cx="24" cy="5" rx="2" ry="1.5" fill="#fef08a" />
                          <rect x="6" y="59" width="4" height="1.5" fill="#dc2626" />
                          <rect x="22" y="59" width="4" height="1.5" fill="#dc2626" />
                        </svg>

                        <!-- Cargo Truck SVG -->
                        <svg v-else viewBox="0 0 32 68" class="w-full h-full overflow-visible">
                          <rect x="0.5" y="8" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="28" y="8" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="0.5" y="44" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="28" y="44" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="0.5" y="54" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="28" y="54" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="1.5" y="16" width="2" height="4" rx="0.5" fill="#ea580c" />
                          <rect x="28.5" y="16" width="2" height="4" rx="0.5" fill="#ea580c" />
                          <rect x="3.5" y="3" width="25" height="22" rx="4" fill="url(#truckCab)" />
                          <path d="M 5.5 13 C 5.5 9, 26.5 9, 26.5 13 L 24.5 18 L 7.5 18 Z" fill="#0f172a" />
                          <rect x="8" y="17" width="16" height="6" fill="url(#truckCab)" />
                          <rect x="2.5" y="24" width="27" height="41" rx="2" fill="url(#cargoBed)" />
                          <line x1="6" y1="27" x2="6" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <line x1="11" y1="27" x2="11" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <line x1="16" y1="27" x2="16" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <line x1="21" y1="27" x2="21" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <line x1="26" y1="27" x2="26" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <circle cx="10" cy="5" r="1" fill="#fb923c" />
                          <circle cx="16" cy="4" r="1.2" fill="#fb923c" />
                          <circle cx="22" cy="5" r="1" fill="#fb923c" />
                          <ellipse cx="7" cy="4.5" rx="1.8" ry="1.2" fill="#fef08a" />
                          <ellipse cx="25" cy="4.5" rx="1.8" ry="1.2" fill="#fef08a" />
                          <rect x="4" y="65" width="4" height="2" fill="#b91c1c" />
                          <rect x="24" y="65" width="4" height="2" fill="#b91c1c" />
                        </svg>
                      </div>
                    </div>
                    <!-- Vehicle B (Offset helper to make it busier) -->
                    <div 
                      v-if="crashingLane !== index + 1"
                      class="absolute left-0 w-full flex justify-center select-none"
                      :class="(index % 2 === 0) ? 'animate-traffic-down' : 'animate-traffic-up'"
                      :style="{ 
                        animationDuration: `${3.2 + (index % 3) * 0.6}s`, 
                        animationDelay: `-${((index + 2) * 1.7) % 5}s`,
                        animationPlayState: (currentLane === index + 1 && gameActive && !isCrashed) ? 'paused' : 'running'
                      }"
                    >
                      <div 
                        class="w-[20px] sm:w-[26px] h-[42px] sm:h-[54px] filter drop-shadow-[0_2.5px_4px_rgba(0,0,0,0.65)]"
                        :style="{ transform: (index % 2 === 0) ? 'rotate(180deg)' : 'none' }"
                      >
                        <!-- Sports Car SVG -->
                        <svg v-if="getVehicleType(index, 'B') === 'sports'" viewBox="0 0 32 64" class="w-full h-full overflow-visible">
                          <rect x="1" y="9" width="3" height="9" rx="1.5" fill="#18181b" />
                          <rect x="28" y="9" width="3" height="9" rx="1.5" fill="#18181b" />
                          <rect x="1" y="47" width="3" height="9" rx="1.5" fill="#18181b" />
                          <rect x="28" y="47" width="3" height="9" rx="1.5" fill="#18181b" />
                          <path d="M 6 3 C 10 2, 22 2, 26 3 C 28 10, 28 54, 26 60 C 22 62, 10 62, 6 60 C 4 54, 4 10, 6 3 Z" fill="url(#sportsBody)" />
                          <rect x="12" y="3" width="2" height="15" fill="url(#sportsStripe)" />
                          <rect x="18" y="3" width="2" height="15" fill="url(#sportsStripe)" />
                          <rect x="12" y="48" width="2" height="12" fill="url(#sportsStripe)" />
                          <rect x="18" y="48" width="2" height="12" fill="url(#sportsStripe)" />
                          <path d="M 7 21 C 7 17, 25 17, 25 21 L 23 26 L 9 26 Z" fill="#0f172a" />
                          <path d="M 8 46 C 8 48, 24 48, 24 46 L 22 42 L 10 42 Z" fill="#0f172a" />
                          <path d="M 5.5 27 L 8.5 27 L 8.5 41 L 5.5 41 Z" fill="#0f172a" />
                          <path d="M 26.5 27 L 23.5 27 L 23.5 41 L 26.5 41 Z" fill="#0f172a" />
                          <rect x="4" y="58" width="24" height="3" rx="1" fill="#991b1b" />
                          <rect x="5" y="57" width="2" height="2" fill="#1e293b" />
                          <rect x="25" y="57" width="2" height="2" fill="#1e293b" />
                          <ellipse cx="8" cy="4" rx="2" ry="1" fill="#38bdf8" />
                          <ellipse cx="24" cy="4" rx="2" ry="1" fill="#38bdf8" />
                          <rect x="7" y="60" width="3" height="1" fill="#f87171" />
                          <rect x="22" y="60" width="3" height="1" fill="#f87171" />
                        </svg>

                        <!-- Taxi SVG -->
                        <svg v-else-if="getVehicleType(index, 'B') === 'taxi'" viewBox="0 0 32 64" class="w-full h-full overflow-visible">
                          <rect x="1" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="1" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <path d="M 4 20 Q 1 20 2 18" stroke="#d97706" stroke-width="1.5" fill="none" />
                          <path d="M 28 20 Q 31 20 30 18" stroke="#d97706" stroke-width="1.5" fill="none" />
                          <rect x="4" y="3" width="24" height="58" rx="6" fill="url(#taxiBody)" />
                          <path d="M 6 20 C 6 17, 26 17, 26 20 L 24 25 L 8 25 Z" fill="url(#windowGlass)" />
                          <path d="M 7 47 C 7 49, 25 49, 25 47 L 23 43 L 9 43 Z" fill="url(#windowGlass)" />
                          <path d="M 5 26 L 8 26 L 8 42 L 5 42 Z" fill="url(#windowGlass)" />
                          <path d="M 27 26 L 24 26 L 24 42 L 27 42 Z" fill="url(#windowGlass)" />
                          <rect x="8" y="24" width="16" height="20" rx="2" fill="#f59e0b" />
                          <rect x="11" y="32" width="10" height="4" rx="1" fill="#000000" />
                          <rect x="12" y="33" width="8" height="2" rx="0.5" fill="#fbbf24" />
                          <ellipse cx="8" cy="5" rx="2" ry="1.5" fill="#fef08a" />
                          <ellipse cx="24" cy="5" rx="2" ry="1.5" fill="#fef08a" />
                          <rect x="6" y="59" width="4" height="1.5" fill="#ef4444" />
                          <rect x="22" y="59" width="4" height="1.5" fill="#ef4444" />
                          <path d="M 10 10 L 22 10" stroke="#b45309" stroke-width="1.5" stroke-linecap="round" />
                          <path d="M 12 13 L 20 13" stroke="#b45309" stroke-width="1" stroke-linecap="round" />
                        </svg>

                        <!-- Police SVG -->
                        <svg v-else-if="getVehicleType(index, 'B') === 'police'" viewBox="0 0 32 64" class="w-full h-full overflow-visible">
                          <rect x="1" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="1" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="4" y="3" width="24" height="58" rx="6" fill="#0f172a" />
                          <rect x="4" y="18" width="24" height="28" fill="url(#policeCab)" />
                          <path d="M 6 20 C 6 17, 26 17, 26 20 L 24 25 L 8 25 Z" fill="#020617" />
                          <path d="M 7 47 C 7 49, 25 49, 25 47 L 23 43 L 9 43 Z" fill="#020617" />
                          <path d="M 5 26 L 8 26 L 8 42 L 5 42 Z" fill="#020617" />
                          <path d="M 27 26 L 24 26 L 24 42 L 27 42 Z" fill="#020617" />
                          <rect x="8" y="24" width="16" height="20" rx="2" fill="url(#policeCab)" />
                          <rect x="7" y="32" width="18" height="4" rx="1" fill="#1e293b" />
                          <rect x="8" y="33" width="8" height="2" rx="0.5" fill="#3b82f6" class="animate-pulse" />
                          <rect x="16" y="33" width="8" height="2" rx="0.5" fill="#ef4444" class="animate-pulse" style="animation-delay: 0.25s;" />
                          <rect x="12" y="10" width="8" height="4" rx="1" fill="#ffffff" opacity="0.1" />
                          <text x="16" y="14" font-size="5" font-weight="900" fill="#ffffff" text-anchor="middle" font-family="sans-serif">POLICE</text>
                          <ellipse cx="8" cy="5" rx="2" ry="1.5" fill="#ffffff" />
                          <ellipse cx="24" cy="5" rx="2" ry="1.5" fill="#ffffff" />
                          <rect x="6" y="59" width="4" height="1.5" fill="#dc2626" />
                          <rect x="22" y="59" width="4" height="1.5" fill="#dc2626" />
                        </svg>

                        <!-- Sedan SVG -->
                        <svg v-else-if="getVehicleType(index, 'B') === 'sedan'" viewBox="0 0 32 64" class="w-full h-full overflow-visible">
                          <rect x="1" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="8" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="1" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="28" y="46" width="3" height="10" rx="1.5" fill="#18181b" />
                          <rect x="4" y="3" width="24" height="58" rx="6" fill="url(#sedanBody)" />
                          <path d="M 6 20 C 6 17, 26 17, 26 20 L 24 25 L 8 25 Z" fill="#0f172a" />
                          <path d="M 7 47 C 7 49, 25 49, 25 47 L 23 43 L 9 43 Z" fill="#0f172a" />
                          <path d="M 5 26 L 8 26 L 8 42 L 5 42 Z" fill="#0f172a" />
                          <path d="M 27 26 L 24 26 L 24 42 L 27 42 Z" fill="#0f172a" />
                          <rect x="8" y="24" width="16" height="20" rx="2" fill="#0284c7" />
                          <rect x="11" y="27" width="10" height="7" rx="1" fill="#0f172a" opacity="0.6" />
                          <ellipse cx="8" cy="5" rx="2" ry="1.5" fill="#fef08a" />
                          <ellipse cx="24" cy="5" rx="2" ry="1.5" fill="#fef08a" />
                          <rect x="6" y="59" width="4" height="1.5" fill="#dc2626" />
                          <rect x="22" y="59" width="4" height="1.5" fill="#dc2626" />
                        </svg>

                        <!-- Cargo Truck SVG -->
                        <svg v-else viewBox="0 0 32 68" class="w-full h-full overflow-visible">
                          <rect x="0.5" y="8" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="28" y="8" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="0.5" y="44" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="28" y="44" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="0.5" y="54" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="28" y="54" width="3.5" height="11" rx="1" fill="#111113" />
                          <rect x="1.5" y="16" width="2" height="4" rx="0.5" fill="#ea580c" />
                          <rect x="28.5" y="16" width="2" height="4" rx="0.5" fill="#ea580c" />
                          <rect x="3.5" y="3" width="25" height="22" rx="4" fill="url(#truckCab)" />
                          <path d="M 5.5 13 C 5.5 9, 26.5 9, 26.5 13 L 24.5 18 L 7.5 18 Z" fill="#0f172a" />
                          <rect x="8" y="17" width="16" height="6" fill="url(#truckCab)" />
                          <rect x="2.5" y="24" width="27" height="41" rx="2" fill="url(#cargoBed)" />
                          <line x1="6" y1="27" x2="6" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <line x1="11" y1="27" x2="11" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <line x1="16" y1="27" x2="16" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <line x1="21" y1="27" x2="21" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <line x1="26" y1="27" x2="26" y2="62" stroke="#94a3b8" stroke-width="1" />
                          <circle cx="10" cy="5" r="1" fill="#fb923c" />
                          <circle cx="16" cy="4" r="1.2" fill="#fb923c" />
                          <circle cx="22" cy="5" r="1" fill="#fb923c" />
                          <ellipse cx="7" cy="4.5" rx="1.8" ry="1.2" fill="#fef08a" />
                          <ellipse cx="25" cy="4.5" rx="1.8" ry="1.2" fill="#fef08a" />
                          <rect x="4" y="65" width="4" height="2" fill="#b91c1c" />
                          <rect x="24" y="65" width="4" height="2" fill="#b91c1c" />
                        </svg>
                      </div>
                    </div>
                  </div>

                  <!-- Lane Number Stripe -->
                  <span class="text-[9px] font-black text-zinc-500/80 tracking-wider z-20 mt-2">L{{ index + 1 }}</span>

                  <!-- Multiplier Checkpoint Pad (Ultra-Premium concentric dial target) -->
                  <div 
                    class="w-11 h-11 sm:w-13 sm:h-13 rounded-full flex flex-col items-center justify-center transition-all duration-300 z-20 font-black relative shadow-lg cursor-pointer"
                    :class="[
                      currentLane > index + 1 
                        ? 'bg-gradient-to-b from-zinc-800 to-zinc-900 border border-yellow-500/35 text-primary scale-95 shadow-md' 
                        : currentLane === index + 1 && gameActive
                          ? 'bg-gradient-to-br from-yellow-300 to-yellow-500 border-2 border-yellow-100 text-zinc-950 scale-110 shadow-[0_0_25px_rgba(234,179,8,0.7)]'
                          : 'bg-gradient-to-b from-[#18181c] to-[#0f0f12] border border-white/5 text-zinc-400 hover:text-white hover:border-zinc-700 shadow-inner'
                    ]"
                  >
                    <!-- Internal circular ring overlay -->
                    <div 
                      class="absolute rounded-full border border-dashed pointer-events-none"
                      :class="[
                        currentLane > index + 1 
                          ? 'inset-1.5 border-yellow-500/20' 
                          : currentLane === index + 1 && gameActive
                            ? 'inset-1 border-zinc-950/40 animate-[spin_6s_linear_infinite]'
                            : 'inset-1.5 border-white/5'
                      ]"
                    ></div>
                    
                    <span class="text-[8px] sm:text-[9.5px] tracking-tighter z-10">{{ mult }}x</span>
                    
                    <!-- Pulsing aura on current standing pad -->
                    <div v-if="currentLane === index + 1 && gameActive" class="absolute -inset-1.5 rounded-full border border-yellow-300/40 animate-ping z-0 pointer-events-none"></div>
                  </div>

                  <!-- Decorative asphalt center spacer dash -->
                  <div class="w-0.5 h-6 bg-zinc-800/45 rounded mb-2"></div>

                  <!-- High-speed Crash Vehicle (Spawns on loss) -->
                  <div 
                    v-if="crashingLane === index + 1"
                    class="absolute left-0 w-full h-full flex items-center justify-center z-30 pointer-events-none"
                    :class="(index % 2 === 0) ? 'animate-crash-vehicle-down' : 'animate-crash-vehicle-up'"
                  >
                    <div 
                      class="w-[28px] sm:w-[35px] h-[60px] sm:h-[75px] filter drop-shadow-[0_4px_8px_rgba(0,0,0,0.6)]"
                      :style="{ transform: (index % 2 === 0) ? 'rotate(180deg)' : 'none' }"
                    >
                      <!-- Fire Engine Crash SVG -->
                      <svg viewBox="0 0 32 70" class="w-full h-full overflow-visible">
                        <rect x="0.5" y="9" width="3.5" height="12" rx="1.5" fill="#111" />
                        <rect x="28" y="9" width="3.5" height="12" rx="1.5" fill="#111" />
                        <rect x="0.5" y="46" width="3.5" height="12" rx="1.5" fill="#111" />
                        <rect x="28" y="46" width="3.5" height="12" rx="1.5" fill="#111" />
                        <rect x="0.5" y="56" width="3.5" height="12" rx="1.5" fill="#111" />
                        <rect x="28" y="56" width="3.5" height="12" rx="1.5" fill="#111" />
                        <rect x="1.5" y="18" width="2" height="4" rx="0.5" fill="#dc2626" />
                        <rect x="28.5" y="18" width="2" height="4" rx="0.5" fill="#dc2626" />
                        <rect x="3.5" y="3" width="25" height="24" rx="4" fill="url(#fireBody)" />
                        <path d="M 5.5 13 C 5.5 9, 26.5 9, 26.5 13 L 24.5 19 L 7.5 19 Z" fill="#0f172a" />
                        <rect x="8" y="21" width="16" height="3" rx="1" fill="#1e293b" />
                        <rect x="9" y="21.5" width="7" height="2" fill="#3b82f6" class="animate-pulse" />
                        <rect x="16" y="21.5" width="7" height="2" fill="#ef4444" class="animate-pulse" style="animation-delay: 0.2s;" />
                        <rect x="2.5" y="27" width="27" height="41" rx="2" fill="url(#fireBody)" />
                        <rect x="11" y="30" width="10" height="34" fill="url(#ladderGrad)" rx="1" />
                        <line x1="11" y1="34" x2="21" y2="34" stroke="#ffffff" stroke-width="1.5" />
                        <line x1="11" y1="39" x2="21" y2="39" stroke="#ffffff" stroke-width="1.5" />
                        <line x1="11" y1="44" x2="21" y2="44" stroke="#ffffff" stroke-width="1.5" />
                        <line x1="11" y1="49" x2="21" y2="49" stroke="#ffffff" stroke-width="1.5" />
                        <line x1="11" y1="54" x2="21" y2="54" stroke="#ffffff" stroke-width="1.5" />
                        <line x1="11" y1="59" x2="21" y2="59" stroke="#ffffff" stroke-width="1.5" />
                        <ellipse cx="7" cy="4.5" rx="2" ry="1.5" fill="#fef08a" />
                        <ellipse cx="25" cy="4.5" rx="2" ry="1.5" fill="#fef08a" />
                        <rect x="4" y="66" width="4" height="2" fill="#ef4444" />
                        <rect x="24" y="66" width="4" height="2" fill="#ef4444" />
                      </svg>
                    </div>
                  </div>
                </div>

                <!-- 3. Finish Pasture (Column index 25) -->
                <div class="w-[3.846%] bg-gradient-to-b from-[#78350f] via-[#92400e] to-[#78350f] border-l-4 border-yellow-500/30 relative flex flex-col items-center justify-center overflow-hidden">
                  <!-- Checkered Finish Ribbon -->
                  <div class="absolute left-0 top-0 bottom-0 w-[4px] bg-[repeating-conic-gradient(#000_0_25%,#fff_0_50%)] [background-size:4px_4px] z-20 opacity-75"></div>
                  <div class="absolute inset-0 bg-[radial-gradient(#eab308_1px,transparent_1px)] [background-size:12px_12px] opacity-15"></div>
                  
                  <!-- Stack of golden achievements inside finish zone -->
                  <div class="absolute bottom-4 text-center text-xl flex flex-col gap-0.5 animate-bounce z-10">
                    <span class="filter drop-shadow-[0_1.5px_2px_rgba(0,0,0,0.5)]">👑</span>
                    <span class="filter drop-shadow-[0_1.5px_2px_rgba(0,0,0,0.5)]">🏆</span>
                  </div>

                  <!-- Golden Sparkles -->
                  <span class="absolute top-4 right-2 text-[10px] animate-ping opacity-80 select-none z-10">✨</span>
                  
                  <span class="text-yellow-300 font-black tracking-widest -rotate-90 text-[9px] sm:text-[11px] uppercase flex items-center gap-1.5 whitespace-nowrap z-10 filter drop-shadow-[0_2px_2px_rgba(0,0,0,0.6)]">
                    🏆 FINISH FIELD
                  </span>
                </div>

                <!-- The Chicken Character (Positions dynamically by matching the column indices) -->
                <div 
                  class="absolute h-full top-0 transition-all duration-400 ease-out flex items-center z-20"
                  :style="{ 
                    left: `calc((${currentLane} / 26) * 100%)`, 
                    width: '3.846%' 
                  }"
                >
                  <div class="w-full flex justify-center items-center relative">
                    
                    <!-- Safe glowing shield if at finish pasture -->
                    <div v-if="currentLane === 25" class="absolute -inset-6 border border-yellow-400/30 bg-yellow-400/10 rounded-full animate-pulse z-0"></div>

                    <!-- 1. Living/Normal Pixel Chicken (Vector SVG Character) -->
                    <div 
                      v-if="!isCrashed"
                      class="relative flex items-center justify-center select-none w-11 h-11 sm:w-15 sm:h-15"
                      :class="{
                        'animate-chicken-breathe': !isJumping && gameActive,
                        'animate-chicken-jump': isJumping
                      }"
                    >
                      <svg viewBox="0 0 64 64" class="w-full h-full overflow-visible">
                          <defs>
                              <!-- Body Gradient (Soft 3D shading) -->
                              <radialGradient id="bodyGrad" cx="35%" cy="35%" r="65%">
                                  <stop offset="0%" stop-color="#ffffff" />
                                  <stop offset="65%" stop-color="#f8fafc" />
                                  <stop offset="100%" stop-color="#cbd5e1" />
                              </radialGradient>
                              <!-- Comb Gradient (Cherry Red) -->
                              <linearGradient id="combGrad" x1="0" y1="0" x2="0" y2="1">
                                  <stop offset="0%" stop-color="#f87171" />
                                  <stop offset="60%" stop-color="#dc2626" />
                                  <stop offset="100%" stop-color="#991b1b" />
                              </linearGradient>
                              <!-- Beak Gradient (Warm Amber) -->
                              <linearGradient id="beakGrad" x1="0" y1="0" x2="1" y2="0.5">
                                  <stop offset="0%" stop-color="#fbbf24" />
                                  <stop offset="100%" stop-color="#ea580c" />
                              </linearGradient>
                              <!-- Wing Gradient (Soft Feathers) -->
                              <linearGradient id="wingGrad" x1="0" y1="0" x2="1" y2="1">
                                  <stop offset="0%" stop-color="#ffffff" />
                                  <stop offset="100%" stop-color="#94a3b8" />
                              </linearGradient>
                              <!-- Tail Gradient -->
                              <linearGradient id="tailGrad" x1="0" y1="1" x2="1" y2="0">
                                  <stop offset="0%" stop-color="#ffffff" />
                                  <stop offset="100%" stop-color="#cbd5e1" />
                              </linearGradient>
                              <!-- Leg Gradient -->
                              <linearGradient id="legGrad" x1="0" y1="0" x2="0" y2="1">
                                  <stop offset="0%" stop-color="#fb923c" />
                                  <stop offset="100%" stop-color="#d97706" />
                              </linearGradient>
                              <!-- Wattle Gradient -->
                              <linearGradient id="wattleGrad" x1="0" y1="0" x2="0" y2="1">
                                  <stop offset="0%" stop-color="#ef4444" />
                                  <stop offset="100%" stop-color="#b91c1c" />
                              </linearGradient>
                          </defs>

                          <!-- Drop shadow under body -->
                          <ellipse cx="32" cy="54" rx="14" ry="4" fill="rgba(0,0,0,0.18)" class="chicken-shadow" />
                          
                          <!-- Main body group -->
                          <g class="chicken-body-group" style="transform-origin: 32px 50px;">
                              <!-- Legs -->
                              <g class="chicken-legs">
                                  <!-- Left Leg -->
                                  <g class="chicken-leg-left" style="transform-origin: 26px 36px;">
                                      <rect x="24.5" y="35" width="3" height="13" fill="url(#legGrad)" rx="1.5" />
                                      <path d="M 21 48 C 21 46.5, 26 45, 31 48 C 30 49, 22 49, 21 48 Z" fill="url(#legGrad)" />
                                  </g>
                                  <!-- Right Leg -->
                                  <g class="chicken-leg-right" style="transform-origin: 38px 36px;">
                                      <rect x="36.5" y="35" width="3" height="13" fill="url(#legGrad)" rx="1.5" />
                                      <path d="M 33 48 C 33 46.5, 38 45, 43 48 C 42 49, 34 49, 33 48 Z" fill="url(#legGrad)" />
                                  </g>
                              </g>

                              <!-- Tail Feathers -->
                              <g class="chicken-tail">
                                  <path d="M 18 24 C 10 16, 12 8, 20 14 C 23 16, 21 21, 18 24 Z" fill="url(#tailGrad)" />
                                  <path d="M 16 30 C 8 24, 10 16, 18 20 C 20 22, 19 27, 16 30 Z" fill="url(#tailGrad)" />
                                  <path d="M 16 36 C 8 32, 12 26, 18 28 C 19 29, 18 33, 16 36 Z" fill="url(#tailGrad)" />
                              </g>

                              <!-- Main Chubby Body (Plump egg shape) -->
                              <path d="M 18 33 C 18 22, 22 18, 32 18 C 42 18, 46 22, 46 33 C 46 41, 41 46, 32 46 C 23 46, 18 41, 18 33 Z" fill="url(#bodyGrad)" />

                              <!-- Comb (Crown on Head) -->
                              <g class="chicken-comb">
                                  <circle cx="28" cy="15" r="5" fill="url(#combGrad)" />
                                  <circle cx="33" cy="12" r="6" fill="url(#combGrad)" />
                                  <circle cx="38" cy="15" r="4.5" fill="url(#combGrad)" />
                              </g>

                              <!-- Yellow Beak (Curved & Premium) -->
                              <path d="M 44 26 C 44 26, 52 28, 51 31 C 50 33, 44 33, 44 33 Z" fill="url(#beakGrad)" />

                              <!-- Red Wattle -->
                              <path d="M 42 32 C 45 32, 46 37, 44 39 C 42 41, 39 39, 40 36 C 40 33, 42 32, 42 32 Z" fill="url(#wattleGrad)" />

                              <!-- Expressive Eyeball & Pupil -->
                              <circle cx="37" cy="25" r="4.5" fill="#ffffff" />
                              <circle cx="38.5" cy="25" r="2.8" fill="#1e293b" />
                              <circle cx="39.5" cy="24" r="1.1" fill="#ffffff" />

                              <!-- Side Wing (Glossy & Organic) -->
                              <g class="chicken-wing" style="transform-origin: 24px 33px;">
                                  <path d="M 22 30 C 22 24, 32 26, 34 32 C 35 36, 28 38, 24 38 C 22 38, 22 35, 22 30 Z" fill="url(#wingGrad)" />
                                  <path d="M 25 32 Q 29 33 31 30" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" fill="none" />
                              </g>
                          </g>
                      </svg>
                    </div>

                    <!-- 2. Crashed State Ghost Ascension Character -->
                    <div 
                      v-if="isCrashed"
                      class="relative flex items-center justify-center select-none w-11 h-11 sm:w-15 sm:h-15 animate-ghost-ascend"
                    >
                      <!-- Crash shockwave burst flash -->
                      <span v-if="showCrashFlash" class="absolute text-5xl sm:text-6xl animate-ping opacity-90 z-30 pointer-events-none">💥</span>
                      
                      <svg viewBox="0 0 64 64" class="w-full h-full overflow-visible opacity-95">
                          <defs>
                              <linearGradient id="ghostGrad" x1="0" y1="0" x2="0" y2="1">
                                  <stop offset="0%" stop-color="rgba(255, 255, 255, 0.95)" />
                                  <stop offset="70%" stop-color="rgba(241, 245, 249, 0.85)" />
                                  <stop offset="100%" stop-color="rgba(186, 230, 253, 0.35)" />
                              </linearGradient>
                              <linearGradient id="haloGrad" x1="0" y1="0" x2="1" y2="0">
                                  <stop offset="0%" stop-color="#ffd54f" />
                                  <stop offset="50%" stop-color="#fff59d" />
                                  <stop offset="100%" stop-color="#ffb300" />
                              </linearGradient>
                          </defs>
                          
                          <!-- Halo -->
                          <ellipse cx="32" cy="12" rx="8" ry="2.5" fill="none" stroke="url(#haloGrad)" stroke-width="2.5" class="animate-pulse" />
                          
                          <!-- Ghost Body -->
                          <path d="M 18 42 C 18 24, 46 24, 46 42 C 46 47, 42 49, 40 48 C 38 47, 36 49, 32 49 C 28 49, 26 47, 24 48 C 22 49, 18 47, 18 42 Z" fill="url(#ghostGrad)" />
                          
                          <!-- Sad Face (Crying tears) -->
                          <path d="M 27 28 Q 29 30 31 28" fill="none" stroke="#475569" stroke-width="1.8" stroke-linecap="round" />
                          <path d="M 33 28 Q 35 30 37 28" fill="none" stroke="#475569" stroke-width="1.8" stroke-linecap="round" />
                          <ellipse cx="32" cy="34" rx="2" ry="3" fill="#475569" />
                          
                          <!-- Tears -->
                          <path d="M 36.5 30 C 36.5 30, 37.5 32, 37.5 34 C 37.5 35, 36.5 35, 36.5 34 C 36.5 33, 36.5 30, 36.5 30 Z" fill="#38bdf8" />
                          
                          <!-- Feathery Ghostly Wings -->
                          <path d="M 14 34 C 11 34, 11 38, 14 38 Z" fill="rgba(255,255,255,0.7)" transform="rotate(-15 14 34)" />
                          <path d="M 50 34 C 53 34, 53 38, 50 38 Z" fill="rgba(255,255,255,0.7)" transform="rotate(15 50 34)" />

                          <!-- Ghost Comb -->
                          <circle cx="28" cy="20" r="3" fill="rgba(251,113,133,0.55)" />
                          <circle cx="32" cy="18" r="3.5" fill="rgba(251,113,133,0.55)" />
                          <circle cx="36" cy="20" r="2.5" fill="rgba(251,113,133,0.55)" />
                          
                          <!-- Ghost Beak -->
                          <path d="M 40 29 Q 45 30 40 33 Z" fill="rgba(253,224,71,0.6)" />
                      </svg>
                    </div>

                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Controls and Betting Panel -->
        <div class="lg:col-span-5 flex flex-col gap-6 w-full">
          <!-- Controls & Betting Panel -->
          <div class="w-full bg-[#1a1a1a] p-5 md:p-8 rounded-[2rem] border border-white/5 shadow-xl">
            <div class="w-full max-w-3xl mx-auto space-y-6">
              
              <!-- Game Not Active state (Lobby & Bets setting) -->
              <div v-if="!gameActive" class="flex flex-col gap-6 animate-fade-in">
                
                <!-- Bet Input & Chips selector -->
                <div class="flex flex-col gap-4">
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-black uppercase tracking-wider text-zinc-400">Select Bet Amount</span>
                    <span class="text-[10px] text-zinc-500 font-bold uppercase">Min: Rs.10 / Max: Rs.2000</span>
                  </div>

                  <!-- Main Amount Number Input -->
                  <div class="relative w-full">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500 font-black text-sm">Rs.</span>
                    <input 
                      v-model.number="betAmount" 
                      type="number" 
                      class="w-full bg-zinc-900/90 text-white font-black py-4 pl-12 pr-28 rounded-2xl border border-white/5 focus:outline-none focus:border-primary/50 transition-colors text-base md:text-lg shadow-inner" 
                      placeholder="Amount" 
                    />
                    
                    <!-- Quick Double / Max tags inside input -->
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                      <button @click="doubleBet" class="bg-zinc-800 hover:bg-zinc-700 active:scale-95 transition-all text-zinc-300 font-black px-2.5 py-1.5 rounded-lg text-xs border border-white/5">
                        X2
                      </button>
                      <button @click="maxBet" class="bg-zinc-800 hover:bg-zinc-700 active:scale-95 transition-all text-zinc-300 font-black px-2.5 py-1.5 rounded-lg text-xs border border-white/5">
                        MAX
                      </button>
                    </div>
                  </div>

                  <!-- Premium Betting Chips Row -->
                  <div class="flex items-center justify-between gap-2.5 flex-wrap">
                    <button 
                      v-for="chip in [10, 50, 100, 500, 1000]" 
                      :key="chip"
                      @click="betAmount = chip"
                      class="flex-1 min-w-[50px] relative py-3 rounded-xl border-2 font-black text-xs md:text-sm flex items-center justify-center select-none active:scale-90 transition-all cursor-pointer shadow-md"
                      :class="[
                        betAmount === chip 
                          ? 'border-primary bg-zinc-800 text-primary scale-105 shadow-primary/10 ring-2 ring-primary/20' 
                          : 'border-zinc-800 bg-zinc-900/80 text-zinc-400 hover:text-zinc-200'
                      ]"
                    >
                      <div class="absolute inset-0.5 border border-dashed border-white/5 rounded-lg"></div>
                      {{ chip }}
                    </button>
                  </div>
                </div>

                <!-- START GAME Action trigger -->
                <button 
                  @click="startGame" 
                  :disabled="processing || betAmount < 10"
                  class="w-full bg-gradient-to-r from-yellow-500 via-yellow-400 to-yellow-500 text-zinc-950 font-black py-4.5 rounded-2xl shadow-[0_6px_0_#b48a00] hover:brightness-110 active:shadow-none active:translate-y-1 transition-all text-base md:text-lg tracking-widest uppercase flex items-center justify-center gap-2"
                >
                  <Coins size="20" fill="currentColor" /> START GAME
                </button>

                <!-- Rules & Guidelines Card -->
                <div class="bg-zinc-900/40 border border-white/5 rounded-2xl p-5 space-y-3.5 text-left">
                  <div class="flex items-center gap-2 text-primary">
                    <Trophy class="w-4 h-4 animate-bounce" />
                    <span class="text-xs font-black uppercase tracking-wider">Lanes Survival Guide</span>
                  </div>
                  <ul class="space-y-2.5 text-[10px] text-zinc-400 font-bold uppercase tracking-wider leading-relaxed">
                    <li class="flex gap-2">
                      <span class="text-primary">★</span>
                      <span><strong>24 Traffic Lanes</strong>: Walk the chicken through 24 increasingly busy traffic lanes. Each step escalates multipliers up to <strong>50x</strong>!</span>
                    </li>
                    <li class="flex gap-2">
                      <span class="text-primary">★</span>
                      <span><strong>Traffic Hazard</strong>: Avoid speeding fire engines or delivery trucks. Getting hit will crash the chicken and clear your bet!</span>
                    </li>
                    <li class="flex gap-2">
                      <span class="text-primary">★</span>
                      <span><strong>Cash Out</strong>: Press cash out anytime to lock in your active winnings.</span>
                    </li>
                    <li class="flex gap-2">
                      <span class="text-primary">★</span>
                      <span><strong>👑 Jackpot</strong>: Settle safely in the final 25th golden pasture to win a 10% slice of the progressive Mega Jackpot!</span>
                    </li>
                  </ul>
                </div>

              </div>

              <!-- Active Game state (CROSS NEXT / CASH OUT) -->
              <div v-else class="flex flex-col gap-4 animate-fade-in">
                <!-- Current Multiplier progress status bar -->
                <div class="bg-zinc-900 border border-white/5 rounded-2xl p-4 flex justify-between items-center shadow-inner">
                  <div class="flex flex-col">
                    <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Current Lane</span>
                    <span class="text-lg font-black text-white">L{{ currentLane }} / 24</span>
                  </div>
                  <div class="flex flex-col items-end">
                    <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Multiplier Accumulator</span>
                    <span class="text-lg font-black text-primary">{{ currentLane > 0 ? multipliers[currentLane - 1] : 1.0 }}X</span>
                  </div>
                </div>

                <!-- Double buttons action controls -->
                <div class="flex gap-3 md:gap-4">
                  <!-- CROSS NEXT button -->
                  <button 
                    @click="move" 
                    :disabled="processing || isJumping || isCrashed" 
                    class="flex-1 bg-white hover:bg-zinc-200 text-zinc-950 font-black py-4.5 rounded-2xl shadow-[0_6px_0_#d4d4d8] active:shadow-none active:translate-y-1 transition-all flex justify-center items-center gap-2 text-sm md:text-base uppercase tracking-widest"
                  >
                    CROSS NEXT <ArrowRight class="w-4 h-4 md:w-5 md:h-5" />
                  </button>
                  
                  <!-- CASH OUT button -->
                  <button 
                    @click="cashOut" 
                    :disabled="processing || currentLane === 0 || isJumping || isCrashed" 
                    class="flex-1 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:brightness-110 text-white font-black py-4.5 rounded-2xl shadow-[0_6px_0_#065f46] active:shadow-none active:translate-y-1 transition-all disabled:opacity-40 disabled:shadow-none disabled:translate-y-1 text-sm md:text-base uppercase tracking-widest"
                  >
                    CASH OUT (Rs. {{ currentWinnings }})
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
        </div>

    <!-- Lose / Roadkill Overlay modal -->
    <div v-if="gameOver" class="fixed inset-0 bg-black/85 flex items-center justify-center p-6 z-[70] backdrop-blur-md animate-fade-in">
      <div class="bg-zinc-900 border border-white/5 p-10 rounded-[2.5rem] text-center max-w-sm w-full shadow-2xl relative overflow-hidden">
        <!-- Exploded glow background -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(239,68,68,0.06)_0%,transparent_100%)] pointer-events-none"></div>
        <div class="text-7xl mb-6 select-none animate-pulse">🪦</div>
        <h2 class="text-3xl font-black text-red-500 mb-2 uppercase italic tracking-wider">ROADKILL!</h2>
        <p class="text-zinc-400 text-sm mb-8 font-semibold uppercase tracking-wider">The chicken was crushed in the traffic lanes.</p>
        <button 
          @click="gameOver = false" 
          class="w-full bg-zinc-800 hover:bg-zinc-700 active:scale-95 text-white py-4 rounded-xl font-black border border-white/5 transition-all uppercase tracking-widest text-xs shadow-md"
        >
          Try Again
        </button>
      </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { ArrowLeft, ArrowRight, Trophy, Coins, RotateCcw, HelpCircle, X, Plus } from 'lucide-vue-next'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import axios from 'axios'

const auth = useAuthStore()
const toast = useToastStore()
const betAmount = ref(100)
const gameActive = ref(false)
const currentLane = ref(0)
const gameOver = ref(false)
const processing = ref(false)
const logId = ref(null)
const crashingLane = ref(-1)
const roadContainer = ref(null)
const jackpot = ref('0.00')
const gameTitle = ref('Chicken Cross')

// State reactive animation indicators
const isJumping = ref(false)
const isCrashed = ref(false)
const showCrashFlash = ref(false)

// Dynamic 24 multipliers matching backend
const multipliers = [
  1.15, 1.25, 1.38, 1.52, 1.68, 1.86, 2.05, 2.28, 2.54, 2.85,
  3.20, 3.60, 4.10, 4.70, 5.45, 6.35, 7.45, 8.85, 10.60, 12.85,
  15.85, 20.00, 30.00, 50.00
]

// Deterministic vehicle helper for assigning SVGs to lanes and slots
const getVehicleType = (index, type) => {
  const types = ['sports', 'taxi', 'police', 'sedan', 'truck']
  const seed = (index * 7 + (type === 'A' ? 3 : 11)) % types.length
  return types[seed]
}

// Fetch dynamic title from database games table based on chicken slug
const fetchGameDetails = async () => {
  try {
    const { data } = await axios.get('/api/games')
    const chickenGame = data.find(g => g.slug === 'chicken')
    if (chickenGame && chickenGame.name) {
      gameTitle.value = chickenGame.name
    }
  } catch (e) {
    // ignore, fall back to default
  }
}

// Double / Max bet helpers
const doubleBet = () => {
  if (betAmount.value * 2 <= 2000) {
    betAmount.value *= 2
  } else {
    betAmount.value = 2000
  }
}

const maxBet = () => {
  betAmount.value = 2000
}

const fetchJackpot = async () => {
  try {
    const { data } = await axios.get('/api/games/chicken/jackpot')
    jackpot.value = parseFloat(data.jackpot || 0).toFixed(2)
  } catch (e) {
    // ignore
  }
}

// Interval references
let jackpotInterval = null

onMounted(() => {
  fetchGameDetails()
  fetchJackpot()
  jackpotInterval = setInterval(fetchJackpot, 10000) // update every 10s
})

onUnmounted(() => {
  if (jackpotInterval) clearInterval(jackpotInterval)
})

// Scroll/Translation alignment logic:
// Each lane is exactly 25% of viewport width. So column flex width is 1/26.
// Track width is 26 * 25% = 650% of the viewport width.
// To center the chicken beautifully, we shift left past Lane 1.
const roadScrollOffset = computed(() => {
  if (currentLane.value <= 1) return 0
  const maxShiftLanes = multipliers.length - 2.5 // limit shifting to not scroll past finish pasture
  const shiftLanes = Math.min(maxShiftLanes, currentLane.value - 1)
  return (shiftLanes / 26) * 100 // percentage of the track container
})

const currentWinnings = computed(() => {
  if (currentLane.value === 0) return '0.00'
  return (betAmount.value * multipliers[currentLane.value - 1]).toFixed(2)
})

const startGame = async () => {
  try {
    const { data } = await axios.post('/api/games/chicken/start', {
      amount: betAmount.value
    })
    logId.value = data.log_id
    if (data.jackpot !== undefined) {
      jackpot.value = parseFloat(data.jackpot).toFixed(2)
    }
    
    gameActive.value = true
    currentLane.value = 0
    gameOver.value = false
    crashingLane.value = -1
    isJumping.value = false
    isCrashed.value = false
    showCrashFlash.value = false

    auth.fetchUser()
    toast.show('Game started! Cross the roads safely.')
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to start game', 'error')
  }
}

const move = async () => {
  if (processing.value || isJumping.value || isCrashed.value) return
  processing.value = true
  
  try {
    const { data } = await axios.post('/api/games/chicken/move', {
      log_id: logId.value
    })

    if (data.status === 'success') {
      // 1. Trigger horizontal & vertical jump animation
      isJumping.value = true
      currentLane.value = data.current_lane

      // 2. End jumping state after jump duration completes
      setTimeout(() => {
        isJumping.value = false
        // Automatically cashout if successfully reached the 24th final lane
        if (currentLane.value === multipliers.length) {
          currentLane.value = 25 // Hop into Finish pasture!
          setTimeout(() => {
            cashOut()
          }, 350)
        }
      }, 400)

    } else {
      // Crash!
      const crashTargetLane = data.current_lane + 1 // backend returns lane index where it crashed
      
      // 1. Make the chicken jump into the crashing lane first
      isJumping.value = true
      currentLane.value = crashTargetLane

      // 2. Coordinates the impact exactly when landing (300ms)
      setTimeout(() => {
        isJumping.value = false
        isCrashed.value = true
        showCrashFlash.value = true
        crashingLane.value = crashTargetLane // Spawns the crash vehicle zooming through
        
        // Hide the blast ring shortly
        setTimeout(() => {
          showCrashFlash.value = false
        }, 300)

        // 3. Complete crash sequences and show lose overlay after chicken flies away
        setTimeout(() => {
          gameActive.value = false
          gameOver.value = true
        }, 1150)
      }, 300)
    }
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to move', 'error')
  } finally {
    processing.value = false
  }
}

const cashOut = async () => {
  if (processing.value) return
  processing.value = true
  
  try {
    const { data } = await axios.post('/api/games/chicken/cashout', {
      log_id: logId.value
    })
    gameActive.value = false
    
    if (data.jackpot_won > 0) {
      toast.show(`MEGA JACKPOT HIT! Won total Rs. ${parseFloat(data.payout).toFixed(2)} (Includes Rs. ${parseFloat(data.jackpot_won).toFixed(2)} Jackpot!)`)
    } else {
      toast.show(`Cashed out Rs. ${parseFloat(data.payout).toFixed(2)}!`)
    }
    
    // Hop into finish zone just in case they cashed out normal
    currentLane.value = 25
    
    auth.fetchUser()
    fetchJackpot()
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to cash out', 'error')
  } finally {
    processing.value = false
  }
}
</script>

<style scoped>
/* 1. Continuous Ambient Lane Traffic Scrolling animations */
@keyframes traffic-down {
  0% { transform: translateY(-130%); }
  100% { transform: translateY(280%); }
}

@keyframes traffic-up {
  0% { transform: translateY(280%); }
  100% { transform: translateY(-130%); }
}

.animate-traffic-down {
  animation: traffic-down infinite linear;
}

.animate-traffic-up {
  animation: traffic-up infinite linear;
}

/* 2. Collision Crash Vehicle Zoom Animations */
@keyframes crash-vehicle-down {
  0% { transform: translateY(-150%); }
  35% { transform: translateY(0); } /* precise crash point */
  100% { transform: translateY(280%); }
}

@keyframes crash-vehicle-up {
  0% { transform: translateY(280%); }
  35% { transform: translateY(0); } /* precise crash point */
  100% { transform: translateY(-150%); }
}

.animate-crash-vehicle-down {
  animation: crash-vehicle-down 0.9s cubic-bezier(0.15, 0.85, 0.35, 1) forwards;
}

.animate-crash-vehicle-up {
  animation: crash-vehicle-up 0.9s cubic-bezier(0.15, 0.85, 0.35, 1) forwards;
}

/* 3. Idle chicken breathing stretching bobbing animation */
@keyframes chicken-breathe {
  0%, 100% { transform: scale(1, 1) rotate(0deg) translateY(0); }
  50% { transform: scale(0.98, 1.03) rotate(0.8deg) translateY(-1px); }
}

.animate-chicken-breathe {
  animation: chicken-breathe 1.2s ease-in-out infinite;
  transform-origin: bottom center;
}

/* 4. Crossy road high parabolic jump CSS animation */
@keyframes chicken-jump {
  0% { transform: scale(1.25, 0.7) translateY(0) rotate(0deg); }
  20% { transform: scale(0.85, 1.2) translateY(-20px) rotate(6deg); }
  50% { transform: scale(0.9, 1.15) translateY(-45px) rotate(12deg); }
  75% { transform: scale(0.95, 1.05) translateY(-15px) rotate(4deg); }
  88% { transform: scale(1.2, 0.78) translateY(2px) rotate(-4deg); }
  100% { transform: scale(1, 1) translateY(0) rotate(0deg); }
}

.animate-chicken-jump {
  animation: chicken-jump 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
  transform-origin: bottom center;
}

/* 5. Explosive Crash Death ghost ascension animation */
@keyframes ghost-ascend {
  0% { transform: translateY(0) translateX(0) scale(1); opacity: 1; }
  20% { transform: translateY(-30px) translateX(6px) scale(1.05); opacity: 0.95; }
  40% { transform: translateY(-65px) translateX(-8px) scale(1.0); opacity: 0.85; }
  60% { transform: translateY(-100px) translateX(8px) scale(0.9); opacity: 0.65; }
  80% { transform: translateY(-130px) translateX(-6px) scale(0.8); opacity: 0.4; }
  100% { transform: translateY(-170px) translateX(0) scale(0.7); opacity: 0; }
}

.animate-ghost-ascend {
  animation: ghost-ascend 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}

/* 6. Leg swing and wing flap sub-animations */
@keyframes left-leg-swing {
  0%, 100% { transform: rotate(0deg); }
  30% { transform: rotate(-35deg) translate(-2px, -2px); }
  70% { transform: rotate(15deg) translate(1px, -1px); }
}
@keyframes right-leg-swing {
  0%, 100% { transform: rotate(0deg); }
  30% { transform: rotate(35deg) translate(2px, -2px); }
  70% { transform: rotate(-15deg) translate(-1px, -1px); }
}
@keyframes wing-flap {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(-35deg) scaleY(0.9); }
  50% { transform: rotate(10deg) scaleY(1.1); }
  75% { transform: rotate(-30deg) scaleY(0.95); }
}

.animate-chicken-jump .chicken-leg-left {
  animation: left-leg-swing 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.animate-chicken-jump .chicken-leg-right {
  animation: right-leg-swing 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.animate-chicken-jump .chicken-wing {
  animation: wing-flap 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

/* 7. Utility visual effects */
@keyframes fade-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.animate-fade-in {
  animation: fade-in 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.bg-primary {
  background-color: #ffd700;
}

.text-primary {
  color: #ffd700;
}

.border-primary {
  border-color: #ffd700;
}
</style>
