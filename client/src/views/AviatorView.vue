<template>
  <div class="min-h-screen bg-[#0e0e12] text-gray-100 flex flex-col font-sans select-none pb-12">
    <!-- Header Branding -->
    <header class="bg-[#121218]/90 border-b border-[#252538] fixed top-0 left-0 right-0 z-50 backdrop-blur-md px-6 py-4 flex items-center justify-between shadow-xl">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-rose-600 flex items-center justify-center shadow-lg shadow-rose-900/30">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
        </div>
        <div>
          <h1 class="text-xl font-black uppercase tracking-widest bg-gradient-to-r from-amber-400 via-rose-400 to-rose-600 bg-clip-text text-transparent">
            Aviator 3D
          </h1>
          <p class="text-[10px] text-rose-500 font-bold uppercase tracking-wider">Stateless Multi-Flight Arena</p>
        </div>
      </div>

      <div class="flex items-center space-x-6">
        <!-- Live Status indicator -->
        <div class="hidden sm:flex items-center space-x-2 bg-[#171725] px-3 py-1.5 rounded-full border border-[#2d2d44]">
          <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
          </span>
          <span class="text-xs font-semibold text-emerald-400 uppercase tracking-widest">LIVE MULTIPLAYER</span>
        </div>

        <!-- Wallet Balance Display -->
        <div class="flex items-center space-x-3 bg-gradient-to-r from-[#1b1b2a] to-[#24243a] px-4 py-2 rounded-xl border border-amber-500/20 shadow-md">
          <div class="bg-amber-500/10 p-1 rounded-lg">
            <span class="text-amber-400 text-sm font-bold">Rs.</span>
          </div>
          <div class="flex flex-col">
            <span class="text-[9px] text-gray-400 uppercase font-black tracking-wider">Wallet Balance</span>
            <span class="text-base font-black text-amber-300 tracking-tight">
              {{ balance.toFixed(2) }}
            </span>
          </div>
        </div>

        <!-- Home Lobby link -->
        <router-link to="/" class="bg-[#1b1b28] hover:bg-[#25253d] text-gray-300 hover:text-white p-2.5 rounded-xl border border-[#2b2b40] transition duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
        </router-link>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 pt-24 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      
      <!-- LEFT + CENTER COLUMN: Game Screen and Controls -->
      <div class="lg:col-span-2 flex flex-col space-y-6">
        
        <!-- ROADMAP HISTORY PILLS -->
        <div class="bg-[#121218]/90 border border-[#232338] rounded-2xl p-4 flex items-center justify-between shadow-xl">
          <div class="flex items-center space-x-2 text-xs font-bold text-gray-400 uppercase tracking-widest">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.8 2.8a1 1 0 101.414-1.414L11 9.586V6z" clip-rule="evenodd" />
            </svg>
            <span>History</span>
          </div>

          <div class="flex space-x-2 overflow-x-auto py-1 px-2 scrollbar-none max-w-[80%]">
            <div 
              v-for="(round, idx) in history" 
              :key="idx"
              :class="[
                'px-3 py-1 rounded-full text-xs font-black tracking-tight border flex-shrink-0 shadow-md transform transition hover:scale-105',
                round.crash_multiplier >= 10.0 
                  ? 'bg-gradient-to-r from-amber-600 to-yellow-500 border-yellow-400 text-white animate-pulse' 
                  : round.crash_multiplier >= 2.0 
                    ? 'bg-gradient-to-r from-violet-700 to-indigo-600 border-indigo-500 text-white' 
                    : 'bg-[#1b1b28] border-[#292944] text-rose-500'
              ]"
            >
              {{ parseFloat(round.crash_multiplier).toFixed(2) }}x
            </div>
            <div v-if="history.length === 0" class="text-xs text-gray-500 italic">No flights registered yet</div>
          </div>
        </div>

        <!-- 3D SPACE FLIGHT SCREEN AREA -->
        <div class="relative bg-radial-gradient bg-[#0b0b0f] border border-[#23233c] rounded-3xl overflow-hidden shadow-2xl h-[420px] sm:h-[460px] flex flex-col justify-between">
          
          <!-- Dynamic Status Badges -->
          <div class="absolute top-4 left-4 z-10 flex space-x-2">
            <div class="bg-[#12121c]/95 border border-[#2b2b48] px-3 py-1.5 rounded-xl flex items-center space-x-2 backdrop-blur-sm">
              <div class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></div>
              <span class="text-[10px] font-black uppercase tracking-wider text-rose-400">
                Round #{{ roundIndex }}
              </span>
            </div>
            <div class="bg-[#12121c]/95 border border-[#2b2b48] px-3 py-1.5 rounded-xl flex items-center space-x-1.5 backdrop-blur-sm">
              <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Active Bets:</span>
              <span class="text-[10px] font-black text-amber-400">
                {{ totals.players_count }} / Rs. {{ totals.bet_amount.toFixed(0) }}
              </span>
            </div>
          </div>

          <div class="absolute top-4 right-4 z-10">
            <button @click="showRules = true" class="bg-[#171728]/95 hover:bg-[#25253d] border border-[#2e2e4a] text-gray-300 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition duration-200">
              Rules
            </button>
          </div>

          <!-- HTML5 CANVAS FOR 3D PERSPECTIVE FLIGHT -->
          <canvas ref="flightCanvas" class="absolute inset-0 w-full h-full block z-0 pointer-events-none"></canvas>

          <!-- MULTIPLIER AND STATUS DISPLAY OVERLAYS -->
          <div class="absolute inset-0 flex flex-col items-center justify-center z-10 pointer-events-none select-none text-center">
            
            <!-- BETTING PHASE -->
            <div v-if="phase === 'betting'" class="flex flex-col items-center animate-fade-in">
              <!-- Circular Countdown SVG -->
              <div class="relative w-36 h-36 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90">
                  <circle cx="72" cy="72" r="60" stroke="#1d1d2b" stroke-width="8" fill="transparent" />
                  <circle cx="72" cy="72" r="60" stroke="#f59e0b" stroke-width="8" fill="transparent"
                    :stroke-dasharray="2 * Math.PI * 60"
                    :stroke-dashoffset="(2 * Math.PI * 60) * (1 - secondsPassed / 12)"
                    stroke-linecap="round" class="transition-all duration-1000 ease-linear" />
                </svg>
                <div class="absolute flex flex-col items-center">
                  <span class="text-3xl font-black text-amber-400 tracking-tight">
                    {{ Math.max(0, Math.ceil(12 - secondsPassed)) }}s
                  </span>
                  <span class="text-[9px] font-black uppercase text-gray-400 tracking-wider">Betting Open</span>
                </div>
              </div>
              <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mt-4 animate-pulse">Waiting for Takeoff...</p>
            </div>

            <!-- FLIGHT ACTIVE PHASE -->
            <div v-else-if="phase === 'fighting'" class="flex flex-col items-center">
              <span class="text-[10px] font-black text-rose-500 uppercase tracking-widest mb-2 bg-rose-500/10 px-3 py-1 rounded-full border border-rose-500/20">
                ACTIVE FLIGHT
              </span>
              <div class="text-6xl sm:text-7xl font-black tracking-tighter text-white drop-shadow-[0_0_20px_rgba(245,158,11,0.3)] select-none">
                {{ liveMultiplier.toFixed(2) }}x
              </div>
              <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mt-2">Current Multiplier</div>
            </div>

            <!-- COOLDOWN / CRASHED PHASE -->
            <div v-else-if="phase === 'cooldown'" class="flex flex-col items-center animate-bounce">
              <div class="text-rose-500 text-sm font-black uppercase tracking-widest mb-1">FLEW AWAY!</div>
              <div class="text-5xl sm:text-6xl font-black tracking-tight text-rose-600 drop-shadow-[0_0_15px_rgba(239,68,68,0.4)]">
                {{ (parseFloat(activeRound.crash_multiplier) || 1.00).toFixed(2) }}x
              </div>
              <div class="text-[9px] font-black text-gray-500 uppercase tracking-wider mt-2">Next flight starting shortly...</div>
            </div>

          </div>

          <!-- Bottom Screen Guidelines bar -->
          <div class="absolute bottom-4 left-6 right-6 z-10 flex items-center justify-between text-[10px] text-gray-500 font-bold uppercase tracking-wider pointer-events-none">
            <span>Grid Elevation: 3D Simulator v2.0</span>
            <span>Speed: {{ phase === 'fighting' ? (liveMultiplier * 100).toFixed(0) : '0' }}km/h</span>
          </div>

        </div>

        <!-- DUAL BETTING PANEL CONTAINER -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          
          <!-- SLOT 1 BET PANEL -->
          <div class="bg-[#121218]/95 border border-[#23233a] rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 shadow-xl relative overflow-hidden flex flex-col justify-between">
            <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-amber-500 to-rose-600"></div>

            <div class="flex items-center justify-between mb-2 sm:mb-4 pl-1 sm:pl-2">
              <div class="flex items-center space-x-2">
                <span class="text-xs font-black text-gray-300 uppercase tracking-wider">Flight Slot A</span>
                <span v-if="slot1.booked" class="bg-amber-500/10 border border-amber-500/30 text-[9px] font-black uppercase tracking-wider text-amber-400 px-2 py-0.5 rounded">Booked</span>
                <span v-else-if="slotBetActive(1)" class="bg-emerald-500/10 border border-emerald-500/30 text-[9px] font-black uppercase tracking-wider text-emerald-400 px-2 py-0.5 rounded">Placed</span>
              </div>
              
              <!-- Auto Cashout Switch -->
              <div class="flex items-center space-x-2">
                <span class="text-[10px] text-gray-400 uppercase font-black tracking-wider">Auto Cash</span>
                <button 
                  @click="toggleAuto(1)"
                  :disabled="slotBetActive(1)"
                  :class="[
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-40',
                    slot1.auto ? 'bg-amber-500' : 'bg-[#1b1b28]'
                  ]"
                >
                  <span :class="['pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', slot1.auto ? 'translate-x-4' : 'translate-x-0']" />
                </button>
              </div>
            </div>

            <!-- Inputs Panel -->
            <div class="grid grid-cols-2 gap-2 sm:gap-3 mb-2.5 sm:mb-4 pl-1 sm:pl-2">
              <!-- Bet Amount -->
              <div class="flex flex-col space-y-1">
                <label class="text-[9px] uppercase font-black text-gray-400 tracking-wider">Bet Amount (Rs.)</label>
                <div class="relative bg-[#171725] rounded-xl border border-[#2c2c40] focus-within:border-amber-500/50 flex items-center px-2.5 py-1.5">
                  <input 
                    type="number" 
                    v-model.number="slot1.amount"
                    :disabled="slotBetActive(1)"
                    min="10"
                    max="10000"
                    class="bg-transparent w-full border-none text-xs sm:text-sm font-black text-white outline-none"
                  />
                </div>
              </div>

              <!-- Auto Multiplier Target -->
              <div class="flex flex-col space-y-1">
                <label class="text-[9px] uppercase font-black text-gray-400 tracking-wider">Multiplier</label>
                <div class="relative bg-[#171725] rounded-xl border border-[#2c2c40] focus-within:border-amber-500/50 flex items-center px-2.5 py-1.5">
                  <input 
                    type="number" 
                    v-model.number="slot1.autoMultiplier"
                    :disabled="!slot1.auto || slotBetActive(1)"
                    step="0.05"
                    min="1.01"
                    class="bg-transparent w-full border-none text-xs sm:text-sm font-black text-white outline-none disabled:text-gray-600"
                  />
                  <span class="text-xs text-gray-400 font-bold ml-1">x</span>
                </div>
              </div>
            </div>

            <!-- Chips Selector -->
            <div class="grid grid-cols-4 gap-1.5 sm:gap-2 mb-2.5 sm:mb-4 pl-1 sm:pl-2">
              <button 
                v-for="chip in [50, 100, 200, 500]" 
                :key="chip"
                @click="addAmount(1, chip)"
                :disabled="slotBetActive(1)"
                class="bg-[#1b1b28] hover:bg-[#252538] border border-[#2a2a3e] disabled:opacity-40 text-[10px] sm:text-xs font-black rounded-lg py-1.5 transition text-gray-300 duration-150"
              >
                +{{ chip }}
              </button>
            </div>

            <!-- Action Trigger Button -->
            <div class="pl-1 sm:pl-2">
              <!-- PLACE BET (BETTING PHASE) -->
              <button 
                v-if="phase === 'betting'"
                @click="triggerPlaceBet(1)"
                :class="[
                  'w-full py-3 sm:py-4 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-black uppercase tracking-wider transition duration-200 flex flex-col items-center justify-center shadow-lg',
                  slotBetActive(1)
                    ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-900/20 text-white'
                    : 'bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 shadow-emerald-950/20 text-white'
                ]"
              >
                <span>{{ slotBetActive(1) ? 'CANCEL BET' : 'PLACE BET' }}</span>
                <span class="text-[10px] opacity-75 font-bold tracking-normal mt-0.5">Rs. {{ slot1.amount }}</span>
              </button>

              <!-- LIVE CASHOUT / BOOK BET (FLIGHT ACTIVE PHASE) -->
              <button 
                v-else-if="phase === 'fighting'"
                @click="slotBetActive(1) ? triggerCashout(1) : triggerPlaceBet(1)"
                :disabled="slotBetActive(1) && slot1.cashed"
                :class="[
                  'w-full py-3 sm:py-4 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-black uppercase tracking-wider transition duration-200 flex flex-col items-center justify-center shadow-lg',
                  slotBetActive(1) && !slot1.cashed
                    ? 'bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 shadow-amber-900/30 text-white cursor-pointer'
                    : !slotBetActive(1)
                      ? 'bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 shadow-emerald-950/20 text-white cursor-pointer'
                      : 'bg-[#1b1b2a] border border-[#2b2b42] text-gray-600 cursor-not-allowed'
                ]"
              >
                <template v-if="slot1.cashed">
                  <span class="text-emerald-400">CASHED OUT</span>
                  <span class="text-[10px] text-emerald-400/85 font-black tracking-normal">Rs. {{ slot1.cashedAmount.toFixed(2) }}</span>
                </template>
                <template v-else-if="slot1.booked">
                  <span class="text-amber-400">BOOKED FOR NEXT</span>
                  <span class="text-[10px] text-amber-400/85 font-black tracking-normal">Rs. {{ slot1.amount }}</span>
                </template>
                <template v-else-if="slotBetActive(1)">
                  <span>CASH OUT</span>
                  <span class="text-[10px] opacity-80 font-black tracking-normal">Rs. {{ (slot1.amount * liveMultiplier).toFixed(2) }}</span>
                </template>
                <template v-else>
                  <span>PLACE BET (NEXT ROUND)</span>
                  <span class="text-[10px] opacity-80 font-black tracking-normal">Rs. {{ slot1.amount }}</span>
                </template>
              </button>

              <!-- COOLDOWN / BOOK BET PHASE -->
              <button 
                v-else
                @click="slot1.booked ? null : triggerPlaceBet(1)"
                :disabled="slot1.booked"
                :class="[
                  'w-full py-3 sm:py-4 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-black uppercase tracking-wider transition duration-200 flex flex-col items-center justify-center shadow-lg',
                  slot1.booked
                    ? 'bg-[#1b1b2a] border border-[#2b2b42] text-amber-500/80 cursor-not-allowed'
                    : 'bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 shadow-emerald-950/20 text-white cursor-pointer'
                ]"
              >
                <span v-if="slot1.booked">BOOKED FOR NEXT (Rs. {{ slot1.amount }})</span>
                <span v-else>PLACE BET (NEXT ROUND)</span>
              </button>
            </div>

          </div>

          <!-- SLOT 2 BET PANEL -->
          <div class="bg-[#121218]/95 border border-[#23233a] rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 shadow-xl relative overflow-hidden flex flex-col justify-between">
            <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-rose-500 to-violet-600"></div>

            <div class="flex items-center justify-between mb-2 sm:mb-4 pl-1 sm:pl-2">
              <div class="flex items-center space-x-2">
                <span class="text-xs font-black text-gray-300 uppercase tracking-wider">Flight Slot B</span>
                <span v-if="slot2.booked" class="bg-amber-500/10 border border-amber-500/30 text-[9px] font-black uppercase tracking-wider text-amber-400 px-2 py-0.5 rounded">Booked</span>
                <span v-else-if="slotBetActive(2)" class="bg-emerald-500/10 border border-emerald-500/30 text-[9px] font-black uppercase tracking-wider text-emerald-400 px-2 py-0.5 rounded">Placed</span>
              </div>
              
              <!-- Auto Cashout Switch -->
              <div class="flex items-center space-x-2">
                <span class="text-[10px] text-gray-400 uppercase font-black tracking-wider">Auto Cash</span>
                <button 
                  @click="toggleAuto(2)"
                  :disabled="slotBetActive(2)"
                  :class="[
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-40',
                    slot2.auto ? 'bg-[#ac24ee]' : 'bg-[#1b1b28]'
                  ]"
                >
                  <span :class="['pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', slot2.auto ? 'translate-x-4' : 'translate-x-0']" />
                </button>
              </div>
            </div>

            <!-- Inputs Panel -->
            <div class="grid grid-cols-2 gap-2 sm:gap-3 mb-2.5 sm:mb-4 pl-1 sm:pl-2">
              <!-- Bet Amount -->
              <div class="flex flex-col space-y-1">
                <label class="text-[9px] uppercase font-black text-gray-400 tracking-wider">Bet Amount (Rs.)</label>
                <div class="relative bg-[#171725] rounded-xl border border-[#2c2c40] focus-within:border-[#ac24ee]/50 flex items-center px-2.5 py-1.5">
                  <input 
                    type="number" 
                    v-model.number="slot2.amount"
                    :disabled="slotBetActive(2)"
                    min="10"
                    max="10000"
                    class="bg-transparent w-full border-none text-xs sm:text-sm font-black text-white outline-none"
                  />
                </div>
              </div>

              <!-- Auto Multiplier Target -->
              <div class="flex flex-col space-y-1">
                <label class="text-[9px] uppercase font-black text-gray-400 tracking-wider">Multiplier</label>
                <div class="relative bg-[#171725] rounded-xl border border-[#2c2c40] focus-within:border-[#ac24ee]/50 flex items-center px-2.5 py-1.5">
                  <input 
                    type="number" 
                    v-model.number="slot2.autoMultiplier"
                    :disabled="!slot2.auto || slotBetActive(2)"
                    step="0.05"
                    min="1.01"
                    class="bg-transparent w-full border-none text-xs sm:text-sm font-black text-white outline-none disabled:text-gray-600"
                  />
                  <span class="text-xs text-gray-400 font-bold ml-1">x</span>
                </div>
              </div>
            </div>

            <!-- Chips Selector -->
            <div class="grid grid-cols-4 gap-1.5 sm:gap-2 mb-2.5 sm:mb-4 pl-1 sm:pl-2">
              <button 
                v-for="chip in [50, 100, 200, 500]" 
                :key="chip"
                @click="addAmount(2, chip)"
                :disabled="slotBetActive(2)"
                class="bg-[#1b1b28] hover:bg-[#252538] border border-[#2a2a3e] disabled:opacity-40 text-[10px] sm:text-xs font-black rounded-lg py-1.5 transition text-gray-300 duration-150"
              >
                +{{ chip }}
              </button>
            </div>

            <!-- Action Trigger Button -->
            <div class="pl-1 sm:pl-2">
              <!-- PLACE BET (BETTING PHASE) -->
              <button 
                v-if="phase === 'betting'"
                @click="triggerPlaceBet(2)"
                :class="[
                  'w-full py-3 sm:py-4 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-black uppercase tracking-wider transition duration-200 flex flex-col items-center justify-center shadow-lg',
                  slotBetActive(2)
                    ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-900/20 text-white'
                    : 'bg-gradient-to-r from-violet-600 to-indigo-500 hover:from-violet-700 hover:to-indigo-600 shadow-indigo-950/20 text-white'
                ]"
              >
                <span>{{ slotBetActive(2) ? 'CANCEL BET' : 'PLACE BET' }}</span>
                <span class="text-[10px] opacity-75 font-bold tracking-normal mt-0.5">Rs. {{ slot2.amount }}</span>
              </button>

              <!-- LIVE CASHOUT / BOOK BET (FLIGHT ACTIVE PHASE) -->
              <button 
                v-else-if="phase === 'fighting'"
                @click="slotBetActive(2) ? triggerCashout(2) : triggerPlaceBet(2)"
                :disabled="slotBetActive(2) && slot2.cashed"
                :class="[
                  'w-full py-3 sm:py-4 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-black uppercase tracking-wider transition duration-200 flex flex-col items-center justify-center shadow-lg',
                  slotBetActive(2) && !slot2.cashed
                    ? 'bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 shadow-amber-900/30 text-white cursor-pointer'
                    : !slotBetActive(2)
                      ? 'bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 shadow-emerald-950/20 text-white cursor-pointer'
                      : 'bg-[#1b1b2a] border border-[#2b2b42] text-gray-600 cursor-not-allowed'
                ]"
              >
                <template v-if="slot2.cashed">
                  <span class="text-emerald-400">CASHED OUT</span>
                  <span class="text-[10px] text-emerald-400/85 font-black tracking-normal">Rs. {{ slot2.cashedAmount.toFixed(2) }}</span>
                </template>
                <template v-else-if="slot2.booked">
                  <span class="text-amber-400">BOOKED FOR NEXT</span>
                  <span class="text-[10px] text-amber-400/85 font-black tracking-normal">Rs. {{ slot2.amount }}</span>
                </template>
                <template v-else-if="slotBetActive(2)">
                  <span>CASH OUT</span>
                  <span class="text-[10px] opacity-80 font-black tracking-normal">Rs. {{ (slot2.amount * liveMultiplier).toFixed(2) }}</span>
                </template>
                <template v-else>
                  <span>PLACE BET (NEXT ROUND)</span>
                  <span class="text-[10px] opacity-80 font-black tracking-normal">Rs. {{ slot2.amount }}</span>
                </template>
              </button>

              <!-- COOLDOWN / BOOK BET PHASE -->
              <button 
                v-else
                @click="slot2.booked ? null : triggerPlaceBet(2)"
                :disabled="slot2.booked"
                :class="[
                  'w-full py-3 sm:py-4 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-black uppercase tracking-wider transition duration-200 flex flex-col items-center justify-center shadow-lg',
                  slot2.booked
                    ? 'bg-[#1b1b2a] border border-[#2b2b42] text-amber-500/80 cursor-not-allowed'
                    : 'bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 shadow-emerald-950/20 text-white cursor-pointer'
                ]"
              >
                <span v-if="slot2.booked">BOOKED FOR NEXT (Rs. {{ slot2.amount }})</span>
                <span v-else>PLACE BET (NEXT ROUND)</span>
              </button>
            </div>

          </div>

        </div>

      </div>

      <!-- RIGHT COLUMN: Active Bets Board & Lobby Stats -->
      <div class="space-y-6">
        
        <!-- Tabbed Active list -->
        <div class="bg-[#121218]/90 border border-[#232338] rounded-3xl p-5 shadow-xl flex flex-col h-[520px] sm:h-[580px]">
          
          <div class="flex items-center space-x-2 border-b border-[#202035] pb-3 mb-4">
            <button 
              @click="activeTab = 'all'"
              :class="['flex-grow pb-2 text-xs font-black uppercase tracking-widest border-b-2 transition duration-200', activeTab === 'all' ? 'text-amber-400 border-amber-500' : 'text-gray-500 border-transparent hover:text-gray-300']"
            >
              All Players
            </button>
            <button 
              @click="activeTab = 'my'"
              :class="['flex-grow pb-2 text-xs font-black uppercase tracking-widest border-b-2 transition duration-200', activeTab === 'my' ? 'text-amber-400 border-amber-500' : 'text-gray-500 border-transparent hover:text-gray-300']"
            >
              My Flights
            </button>
          </div>

          <!-- TAB CONTENT: ALL PLAYERS -->
          <div v-show="activeTab === 'all'" class="flex-grow overflow-y-auto scrollbar-none flex flex-col space-y-2">
            <div class="flex items-center justify-between text-[10px] text-gray-500 font-bold uppercase tracking-wider px-2 pb-1.5">
              <span>Player</span>
              <span>Multiplier / Payout</span>
            </div>

            <!-- Simulated Multiplayer activity list -->
            <div 
              v-for="(p, idx) in allBets" 
              :key="idx"
              :class="[
                'flex items-center justify-between p-3 rounded-xl border transition-all duration-200',
                p.cashed 
                  ? 'bg-emerald-950/20 border-emerald-900/30' 
                  : 'bg-[#181825] border-[#222238]'
              ]"
            >
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-[#212133] flex items-center justify-center text-xs font-black border border-[#2b2b44] text-gray-300">
                  {{ p.username.substring(0, 2).toUpperCase() }}
                </div>
                <div class="flex flex-col">
                  <span class="text-xs font-black text-gray-200">{{ p.username }}</span>
                  <span class="text-[9px] text-gray-400 font-bold">Bet: Rs. {{ p.amount.toFixed(0) }}</span>
                </div>
              </div>

              <div class="flex flex-col items-end">
                <span 
                  v-if="p.cashed" 
                  class="text-xs font-black text-emerald-400 flex items-center space-x-1"
                >
                  <span>{{ p.multiplier.toFixed(2) }}x</span>
                  <span class="text-[9px] opacity-75 font-semibold"> (Rs. {{ p.payout.toFixed(0) }})</span>
                </span>
                <span v-else class="text-[10px] font-bold text-gray-500 uppercase tracking-widest animate-pulse">Flying...</span>
              </div>
            </div>

            <div v-if="allBets.length === 0" class="flex-grow flex flex-col items-center justify-center text-center text-gray-500 py-12">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-600 mb-2 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <span class="text-xs font-bold uppercase tracking-wider">No bets placed yet</span>
              <span class="text-[10px] text-gray-600">Waiting for players to lock amounts</span>
            </div>

          </div>

          <!-- TAB CONTENT: MY FLIGHTS HISTORY -->
          <div v-show="activeTab === 'my'" class="flex-grow overflow-y-auto scrollbar-none flex flex-col space-y-2.5">
            <div class="flex items-center justify-between text-[10px] text-gray-500 font-bold uppercase tracking-wider px-2 pb-1.5 border-b border-[#1b1b2f]">
              <span>Round #</span>
              <span>Outcome</span>
            </div>

            <div 
              v-for="b in userBets" 
              :key="b.id"
              class="flex items-center justify-between p-3 bg-[#151522] border border-[#24243a] rounded-xl"
            >
              <div class="flex flex-col">
                <span class="text-xs font-black text-gray-200">Round {{ roundIndex }}</span>
                <span class="text-[9px] text-gray-400 font-black">STAKE: Rs. {{ b.amount }}</span>
              </div>

              <div class="flex flex-col items-end">
                <template v-if="b.status === 'completed'">
                  <div v-if="b.result === 'win'" class="flex flex-col items-end">
                    <span class="text-xs font-black text-emerald-400 flex items-center space-x-0.5">
                      <span>WIN</span>
                      <span class="text-[10px] font-bold"> ({{ b.cashout_multiplier.toFixed(2) }}x)</span>
                    </span>
                    <span class="text-[9px] text-emerald-500 font-black">Rs. {{ (b.amount * b.cashout_multiplier * 0.9).toFixed(2) }}</span>
                  </div>
                  <div v-else class="text-xs font-black text-rose-500">
                    LOST (CRASHED)
                  </div>
                </template>
                <template v-else>
                  <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest animate-pulse">Flying...</span>
                </template>
              </div>
            </div>

            <div v-if="userBets.length === 0" class="flex-grow flex flex-col items-center justify-center text-center text-gray-500 py-12">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="text-xs font-bold uppercase tracking-wider">No active bets</span>
              <span class="text-[10px] text-gray-600">Your recent bets will appear here</span>
            </div>

          </div>

        </div>

      </div>

    </main>

    <!-- RULES / MODAL INFO SHEET -->
    <div v-if="showRules" class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/80 backdrop-blur-sm">
      <div class="bg-[#12121c] border border-[#2b2b48] rounded-3xl p-6 max-w-lg w-full relative shadow-2xl text-gray-300">
        <button @click="showRules = false" class="absolute top-4 right-4 text-gray-400 hover:text-white transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <h3 class="text-lg font-black uppercase text-amber-400 tracking-wider mb-4">Aviator 3D Guidelines</h3>
        
        <div class="space-y-3.5 text-xs leading-relaxed text-gray-400">
          <p>
            <strong class="text-gray-200">1. Betting Window (12 seconds)</strong>: Before each takeoff, a countdown circle appears. Select your bet amount and/or enable the optional <span class="text-amber-400">Auto Cash</span> multiplier.
          </p>
          <p>
            <strong class="text-gray-200">2. The Flight (up to 43 seconds)</strong>: The plane takes off, climbing exponentially. The multiplier starts at <span class="text-amber-400">1.00x</span> and grows according to $M(t) = e^{0.08t}$.
          </p>
          <p>
            <strong class="text-gray-200">3. Cash Out</strong>: Click <span class="text-amber-400">Cash Out</span> to secure your payout before the plane crashes (flies away). Your payout is equal to your bet amount multiplied by the cash out multiplier, minus a 10% commission.
          </p>
          <p>
            <strong class="text-gray-200">4. Auto Cash out</strong>: If enabled, the game automatically cashes you out at the exact multiplier you specified, provided the plane does not crash beforehand.
          </p>
          <p class="border-t border-[#23233c] pt-3 text-[10px] text-gray-500 font-bold uppercase">
            Platform Security: Multipliers are calculated on server timers. Auto-settlement processes immediately on round completions.
          </p>
        </div>

        <button @click="showRules = false" class="w-full mt-6 py-3 bg-amber-500 hover:bg-amber-600 text-black font-black uppercase tracking-wider rounded-xl transition duration-200 text-xs">
          Got it!
        </button>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';

export default {
  name: 'AviatorView',
  setup() {
    // API State variables
    const balance = ref(0.00);
    const roundIndex = ref(0);
    const secondsPassed = ref(0);
    const phase = ref('betting'); // betting, fighting, cooldown
    const liveMultiplier = ref(1.00);
    const history = ref([]);
    const userBets = ref([]);
    const totals = ref({ players_count: 0, bet_amount: 0.00 });
    const activeRound = ref({ crash_multiplier: null, settled: false });

    // UI State variables
    const showRules = ref(false);
    const activeTab = ref('all');
    const allBets = ref([]);

    // Two custom betting slot bindings (with 'booked' field)
    const slot1 = ref({ amount: 100, auto: false, autoMultiplier: 2.00, betId: null, cashed: false, cashedAmount: 0.00, booked: false });
    const slot2 = ref({ amount: 200, auto: false, autoMultiplier: 3.50, betId: null, cashed: false, cashedAmount: 0.00, booked: false });

    // Clock offset for high-precision time synchronization
    const serverTimeOffset = ref(0);

    // Premium realistic visual assets preloading
    const spaceBgImg = new Image();
    spaceBgImg.src = '/space_background.png';

    const rocketImg = new Image();
    rocketImg.src = '/aviator_rocket.png';

    let rocketSprite = null;

    // Color-keying script to filter out dark pixels from rocket sprite for transparency
    const processTransparentRocket = (img) => {
      const tempCanvas = document.createElement('canvas');
      tempCanvas.width = img.width;
      tempCanvas.height = img.height;
      const tempCtx = tempCanvas.getContext('2d');
      tempCtx.drawImage(img, 0, 0);

      const imgData = tempCtx.getImageData(0, 0, img.width, img.height);
      const data = imgData.data;

      // Filter out pure black / very dark pixels
      for (let i = 0; i < data.length; i += 4) {
        const r = data[i];
        const g = data[i + 1];
        const b = data[i + 2];
        if (r < 25 && g < 25 && b < 25) {
          data[i + 3] = 0; // set alpha channel to 0
        }
      }

      tempCtx.putImageData(imgData, 0, 0);
      return tempCanvas;
    };

    rocketImg.onload = () => {
      rocketSprite = processTransparentRocket(rocketImg);
    };

    // HTML5 Canvas variables for 3D simulation
    const flightCanvas = ref(null);
    let canvasCtx = null;
    let animationFrameId = null;
    let gridOffset = 0;
    let starfield = [];
    const maxStars = 65;

    // Timer handles
    let serverSyncInterval = null;
    let localClockTicker = null;

    // Helper functions
    const slotBetActive = (slotNum) => {
      const slot = slotNum === 1 ? slot1.value : slot2.value;
      return slot.betId !== null;
    };

    const addAmount = (slotNum, amt) => {
      const slot = slotNum === 1 ? slot1.value : slot2.value;
      slot.amount = Math.min(10000, Math.max(10, slot.amount + amt));
    };

    const toggleAuto = (slotNum) => {
      const slot = slotNum === 1 ? slot1.value : slot2.value;
      slot.auto = !slot.auto;
    };

    // Initialize 3D Starfield particles
    const initStarfield = (width, height) => {
      starfield = [];
      for (let i = 0; i < maxStars; i++) {
        starfield.push({
          x: (Math.random() - 0.5) * 800,
          y: (Math.random() - 0.5) * 600,
          z: Math.random() * 30 + 1, // depth
          size: Math.random() * 2.5 + 0.5,
          color: `hsl(${220 + Math.random() * 60}, 95%, ${75 + Math.random() * 25}%)`
        });
      }
    };

    // Draw Spacecraft Vector / Real Rocket Sprite
    const drawSpacecraft = (ctx, x, y, size, elapsed) => {
      const pulse = Math.sin(elapsed * 0.2) * 1.5;
      
      ctx.save();
      
      // Exhaust fire trails
      ctx.beginPath();
      const fireGrad = ctx.createRadialGradient(x - 30, y + 8, 2, x - 45, y + 10, 20);
      fireGrad.addColorStop(0, '#fff');
      fireGrad.addColorStop(0.3, '#f59e0b');
      fireGrad.addColorStop(0.8, '#ef4444');
      fireGrad.addColorStop(1, 'rgba(239, 68, 68, 0)');
      ctx.fillStyle = fireGrad;
      ctx.arc(x - 28, y + 10 + pulse, 15, 0, Math.PI * 2);
      ctx.fill();

      // Wing glow trail aura
      ctx.shadowBlur = 20;
      ctx.shadowColor = '#f43f5e';
      
      if (rocketSprite) {
        // Draw the high-quality keyed transparent rocket image!
        const width = 90;
        const height = 45;
        // Offset to align center of rocket with (x, y)
        ctx.drawImage(rocketSprite, x - width / 2, y - height / 2 + pulse, width, height);
      } else {
        // Fallback Vector Wings drawing
        ctx.shadowBlur = 15;
        ctx.shadowColor = '#d946ef';

        ctx.beginPath();
        ctx.moveTo(x - 15, y - 5);
        ctx.lineTo(x + 25, y + 5); // tip
        ctx.lineTo(x - 15, y + 18);
        ctx.lineTo(x - 22, y + 8);
        ctx.closePath();
        
        const shipGrad = ctx.createLinearGradient(x - 20, y, x + 25, y);
        shipGrad.addColorStop(0, '#f43f5e');
        shipGrad.addColorStop(1, '#ec4899');
        ctx.fillStyle = shipGrad;
        ctx.strokeStyle = '#f472b6';
        ctx.lineWidth = 2.5;
        ctx.fill();
        ctx.stroke();

        // Cockpit dome
        ctx.shadowBlur = 5;
        ctx.shadowColor = '#6366f1';
        ctx.beginPath();
        ctx.ellipse(x - 2, y + 5, 8, 4, Math.PI / 8, 0, Math.PI * 2);
        ctx.fillStyle = '#6366f1';
        ctx.fill();
      }
      
      ctx.restore();
    };

    // 3D Rendering Game Loop
    const draw3DSimulator = () => {
      if (!flightCanvas.value) return;
      const canvas = flightCanvas.value;
      const ctx = canvasCtx;
      const w = canvas.width;
      const h = canvas.height;

      // Clear Screen with deep space background image!
      if (spaceBgImg.complete && spaceBgImg.naturalWidth !== 0) {
        ctx.drawImage(spaceBgImg, 0, 0, w, h);
      } else {
        ctx.fillStyle = '#09090c';
        ctx.fillRect(0, 0, w, h);
      }

      const horizonX = w / 2;
      const horizonY = h * 0.15;

      // Set velocity based on flight multiplier
      const velocityMultiplier = phase.value === 'fighting' ? liveMultiplier.value : 0.8;
      const velocity = 0.08 * velocityMultiplier;

      // --- DRAW 3D PERSPECTIVE GRID FLOOR ---
      gridOffset = (gridOffset + velocity * 15) % 80;

      ctx.strokeStyle = 'rgba(124, 58, 237, 0.12)';
      ctx.lineWidth = 1.5;

      // Perspective longitudinal lines
      const gridWidth = w * 2.5;
      const cols = 22;
      for (let i = -cols/2; i <= cols/2; i++) {
        const targetX = horizonX + (i * (gridWidth / cols));
        ctx.beginPath();
        ctx.moveTo(horizonX, horizonY);
        ctx.lineTo(targetX, h);
        ctx.stroke();
      }

      // Moving horizontal lines
      for (let yOffset = gridOffset; yOffset < h; yOffset += 35) {
        const ratio = (yOffset) / h;
        const projectedY = horizonY + (h - horizonY) * Math.pow(ratio, 2.5);
        
        ctx.strokeStyle = `rgba(124, 58, 237, ${0.05 + ratio * 0.22})`;
        ctx.beginPath();
        ctx.moveTo(horizonX - ratio * gridWidth * 0.5, projectedY);
        ctx.lineTo(horizonX + ratio * gridWidth * 0.5, projectedY);
        ctx.stroke();
      }

      // --- DRAW STARFIELD PARTICLES ---
      const fov = 320; // field of view

      for (let i = 0; i < starfield.length; i++) {
        const star = starfield[i];
        star.z -= velocity;

        if (star.z <= 0) {
          star.x = (Math.random() - 0.5) * w * 2.2;
          star.y = (Math.random() - 0.5) * h * 1.5;
          star.z = 30;
        }

        const px = horizonX + (star.x / star.z) * fov;
        const py = horizonY + (star.y / star.z) * fov;
        const sz = (star.size / star.z) * fov * 0.45;

        if (px >= 0 && px <= w && py >= 0 && py <= h) {
          ctx.beginPath();
          ctx.fillStyle = star.color;
          ctx.arc(px, py, sz, 0, Math.PI * 2);
          ctx.fill();
        }
      }

      // --- FLIGHT ARCS TRAIL AND SPACECRAFT ANIMATIONS ---
      if (phase.value === 'fighting') {
        const elapsed = secondsPassed.value - 12;
        
        // Map airplane path
        const planeX = w * 0.15 + (w * 0.65) * (Math.min(30, elapsed) / 30);
        const planeY = h * 0.85 - (h * 0.65) * (Math.min(25, liveMultiplier.value - 1.0) / 25);

        // Draw trail curve
        const curveGrad = ctx.createLinearGradient(w * 0.15, h * 0.85, planeX, planeY);
        curveGrad.addColorStop(0, 'rgba(245, 158, 11, 0.05)');
        curveGrad.addColorStop(0.5, 'rgba(236, 72, 153, 0.45)');
        curveGrad.addColorStop(1, 'rgba(217, 70, 239, 0.85)');

        ctx.strokeStyle = curveGrad;
        ctx.lineWidth = 5;
        ctx.shadowBlur = 15;
        ctx.shadowColor = '#d946ef';

        ctx.beginPath();
        ctx.moveTo(w * 0.15, h * 0.85);
        ctx.quadraticCurveTo(w * 0.45, h * 0.88, planeX - 10, planeY + 5);
        ctx.stroke();
        ctx.shadowBlur = 0;

        // Draw Spacecraft rocket
        drawSpacecraft(ctx, planeX, planeY, 35, elapsed * 1.5);
      }

      animationFrameId = requestAnimationFrame(draw3DSimulator);
    };

    // Setup Canvas DOM bounds
    const resizeCanvas = () => {
      if (!flightCanvas.value) return;
      const canvas = flightCanvas.value;
      canvas.width = canvas.parentElement.clientWidth;
      canvas.height = canvas.parentElement.clientHeight;
      initStarfield(canvas.width, canvas.height);
    };

    // Synchronize Client clock state with Laravel API
    const syncServerState = async () => {
      try {
        const token = localStorage.getItem('token');
        const headers = token ? { Authorization: `Bearer ${token}` } : {};

        const res = await axios.get('/api/games/aviator/state', { headers });
        const data = res.data;

        // Synchronize server time offset (NTP)
        if (data.server_time) {
          serverTimeOffset.value = (data.server_time * 1000) - Date.now();
        }

        const serverTime = (Date.now() + serverTimeOffset.value) / 1000;
        const roundIndexVal = Math.floor(serverTime / 60);
        const startTimeVal = roundIndexVal * 60;
        
        secondsPassed.value = parseFloat((serverTime - startTimeVal).toFixed(2));
        roundIndex.value = roundIndexVal;
        
        phase.value = data.phase;
        activeRound.value = data.active_round;
        totals.value = data.totals;
        history.value = data.history;

        // Prevent phase flicker if server says fighting but client already calculated a crash
        if (phase.value === 'fighting' && data.active_round && data.active_round.settled && data.active_round.crash_multiplier !== null) {
          const flightTime = secondsPassed.value - 12;
          const calcMult = 1.00 * Math.exp(0.08 * flightTime);
          if (calcMult >= parseFloat(data.active_round.crash_multiplier)) {
            phase.value = 'cooldown';
          }
        }

        // Reset slots at start of a brand new betting phase
        if (phase.value === 'betting') {
          if (secondsPassed.value < 1.5) {
            // Keep slot active if it was booked for this round
            if (slot1.value.booked) {
              slot1.value.booked = false;
            } else {
              slot1.value.betId = null;
              slot1.value.cashed = false;
              slot1.value.cashedAmount = 0.00;
            }

            if (slot2.value.booked) {
              slot2.value.booked = false;
            } else {
              slot2.value.betId = null;
              slot2.value.cashed = false;
              slot2.value.cashedAmount = 0.00;
            }
          }
        }

        // Sync active user bets
        if (data.user_bets && data.user_bets.length > 0) {
          userBets.value = data.user_bets;
          
          data.user_bets.forEach((bet) => {
            if (slot1.value.amount === bet.amount && slot1.value.betId === null) {
              slot1.value.betId = bet.id;
              slot1.value.cashed = bet.cashed_out;
              slot1.value.cashedAmount = bet.cashout_multiplier ? bet.amount * bet.cashout_multiplier * 0.9 : 0.00;
              slot1.value.booked = false;
            } else if (slot2.value.amount === bet.amount && slot2.value.betId === null) {
              slot2.value.betId = bet.id;
              slot2.value.cashed = bet.cashed_out;
              slot2.value.cashedAmount = bet.cashout_multiplier ? bet.amount * bet.cashout_multiplier * 0.9 : 0.00;
              slot2.value.booked = false;
            } else {
              if (slot1.value.betId === null) {
                slot1.value.betId = bet.id;
                slot1.value.cashed = bet.cashed_out;
                slot1.value.cashedAmount = bet.cashout_multiplier ? bet.amount * bet.cashout_multiplier * 0.9 : 0.00;
                slot1.value.booked = false;
              } else if (slot2.value.betId === null) {
                slot2.value.betId = bet.id;
                slot2.value.cashed = bet.cashed_out;
                slot2.value.cashedAmount = bet.cashout_multiplier ? bet.amount * bet.cashout_multiplier * 0.9 : 0.00;
                slot2.value.booked = false;
              }
            }
          });
        }

        fetchUserBalance();
        updateLiveMultiplier();

      } catch (err) {
        console.error('Error synchronizing server state:', err);
      }
    };

    // Calculate flight multiplier
    const updateLiveMultiplier = () => {
      if (phase.value === 'fighting') {
        const flightTime = secondsPassed.value - 12;
        const calcMult = 1.00 * Math.exp(0.08 * flightTime);

        // Client-side crash detection
        if (activeRound.value && activeRound.value.settled && activeRound.value.crash_multiplier !== null) {
          const crashM = parseFloat(activeRound.value.crash_multiplier);
          if (calcMult >= crashM) {
            liveMultiplier.value = crashM;
            phase.value = 'cooldown';
            return;
          }
        }

        liveMultiplier.value = calcMult;
        checkAutoCashoutTriggers();
      } else if (phase.value === 'cooldown') {
        if (activeRound.value && activeRound.value.crash_multiplier !== null) {
          liveMultiplier.value = parseFloat(activeRound.value.crash_multiplier);
        } else {
          liveMultiplier.value = 1.00;
        }
      } else {
        liveMultiplier.value = 1.00;
      }
    };

    // Process auto-cashout thresholds in background
    const checkAutoCashoutTriggers = () => {
      if (slot1.value.auto && slot1.value.betId && !slot1.value.cashed && !slot1.value.booked && liveMultiplier.value >= slot1.value.autoMultiplier) {
        triggerCashout(1);
      }
      if (slot2.value.auto && slot2.value.betId && !slot2.value.cashed && !slot2.value.booked && liveMultiplier.value >= slot2.value.autoMultiplier) {
        triggerCashout(2);
      }
    };

    const fetchUserBalance = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;

        const res = await axios.get('/api/user', {
          headers: { Authorization: `Bearer ${token}` }
        });
        balance.value = parseFloat(res.data.balance);
      } catch (err) {
        console.error('Error fetching balance:', err);
      }
    };

    // Place bet API
    const triggerPlaceBet = async (slotNum) => {
      const slot = slotNum === 1 ? slot1.value : slot2.value;
      const token = localStorage.getItem('token');
      if (!token) return;

      if (slot.betId !== null) {
        alert("Cannot cancel a placed bet in live multi-player mode!");
        return;
      }

      try {
        const res = await axios.post('/api/games/aviator/bet', {
          amount: slot.amount,
          auto_multiplier: slot.auto ? slot.autoMultiplier : null
        }, {
          headers: { Authorization: `Bearer ${token}` }
        });

        slot.betId = res.data.bet.id;
        slot.booked = res.data.bet.booked || false;
        balance.value = res.data.new_balance;
        syncServerState();
      } catch (err) {
        alert(err.response?.data?.message || 'Error placing bet');
      }
    };

    // Cash out API
    const triggerCashout = async (slotNum) => {
      const slot = slotNum === 1 ? slot1.value : slot2.value;
      const token = localStorage.getItem('token');
      if (!token || !slot.betId || slot.cashed || slot.booked) return;

      try {
        const res = await axios.post('/api/games/aviator/cashout', {
          bet_log_id: slot.betId
        }, {
          headers: { Authorization: `Bearer ${token}` }
        });

        slot.cashed = true;
        slot.cashedAmount = res.data.payout;
        balance.value = res.data.new_balance;
        syncServerState();
      } catch (err) {
        console.error('Error executing cashout:', err);
        if (err.response?.data?.crashed) {
          slot.betId = null;
        }
      }
    };

    // Generate simulated lobby bets for visual multiplayer activity
    const generateSimulatedLobbyBets = () => {
      if (phase.value === 'betting') {
        allBets.value = [];
        const names = ['CryptoKing', 'MoonRider', 'RocketGold', 'SolanaBull', 'WinMax', 'LuckyJet', 'EtherShark', 'ApexGamer', 'NovaBet', 'RiskTaker'];
        const activeCount = Math.floor(Math.random() * 5) + 3;

        for (let i = 0; i < activeCount; i++) {
          allBets.value.push({
            username: names[Math.floor(Math.random() * names.length)] + Math.floor(Math.random() * 99),
            amount: [50, 100, 200, 500, 1000][Math.floor(Math.random() * 5)],
            cashed: false,
            multiplier: 0.00,
            payout: 0.00,
            cashThreshold: (Math.random() * 3 + 1.1)
          });
        }
      } else if (phase.value === 'fighting') {
        allBets.value.forEach((p) => {
          if (!p.cashed && liveMultiplier.value >= p.cashThreshold) {
            p.cashed = true;
            p.multiplier = liveMultiplier.value;
            p.payout = p.amount * liveMultiplier.value;
          }
        });
      }
    };

    onMounted(() => {
      if (flightCanvas.value) {
        canvasCtx = flightCanvas.value.getContext('2d');
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        draw3DSimulator();
      }

      syncServerState();
      serverSyncInterval = setInterval(syncServerState, 2500);

      // Local high-precision sub-second clock ticks
      localClockTicker = setInterval(() => {
        const serverTime = (Date.now() + serverTimeOffset.value) / 1000;
        const roundIndexVal = Math.floor(serverTime / 60);
        const startTimeVal = roundIndexVal * 60;
        secondsPassed.value = parseFloat((serverTime - startTimeVal).toFixed(2));
        roundIndex.value = roundIndexVal;
        
        // phase roll-over
        if (secondsPassed.value >= 60) {
          phase.value = 'betting';
          // reset UI slots if not booked
          if (!slot1.value.booked) {
            slot1.value.betId = null;
            slot1.value.cashed = false;
            slot1.value.cashedAmount = 0.00;
          } else {
            slot1.value.booked = false;
          }
          if (!slot2.value.booked) {
            slot2.value.betId = null;
            slot2.value.cashed = false;
            slot2.value.cashedAmount = 0.00;
          } else {
            slot2.value.booked = false;
          }
        } else if (secondsPassed.value >= 55) {
          phase.value = 'cooldown';
        } else if (secondsPassed.value >= 12) {
          // Check if it already crashed based on settled crash multiplier
          let isCrashed = false;
          if (activeRound.value && activeRound.value.settled && activeRound.value.crash_multiplier !== null) {
            const flightTime = secondsPassed.value - 12;
            const calcMult = 1.00 * Math.exp(0.08 * flightTime);
            if (calcMult >= parseFloat(activeRound.value.crash_multiplier)) {
              isCrashed = true;
            }
          }
          if (isCrashed) {
            phase.value = 'cooldown';
          } else {
            phase.value = 'fighting';
          }
        } else {
          phase.value = 'betting';
        }

        updateLiveMultiplier();
        generateSimulatedLobbyBets();
      }, 100);
    });

    onUnmounted(() => {
      window.removeEventListener('resize', resizeCanvas);
      cancelAnimationFrame(animationFrameId);
      clearInterval(serverSyncInterval);
      clearInterval(localClockTicker);
    });

    return {
      balance,
      roundIndex,
      secondsPassed,
      phase,
      liveMultiplier,
      totals,
      activeRound,
      history,
      userBets,
      showRules,
      activeTab,
      allBets,
      slot1,
      slot2,
      flightCanvas,
      slotBetActive,
      addAmount,
      toggleAuto,
      triggerPlaceBet,
      triggerCashout
    };
  }
};
</script>

<style scoped>
.bg-radial-gradient {
  background-image: radial-gradient(circle at center, #1b1731 0%, #0c0b11 75%);
}
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
