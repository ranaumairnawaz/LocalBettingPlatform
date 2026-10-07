<template>
  <div class="min-h-screen bg-[#121212] text-white flex flex-col font-sans overflow-x-hidden pb-12">
    <!-- Header (Fixed-style premium bar) -->
    <header class="w-full bg-[#1a1a1a] border-b border-white/5 py-4 px-6 flex justify-between items-center fixed top-0 left-0 right-0 z-50 shadow-md">
      <div class="flex items-center gap-3">
        <button 
          @click="handleBack" 
          class="p-2 hover:bg-white/5 active:scale-95 transition rounded-full text-zinc-400 hover:text-white"
        >
          <ArrowLeft size="20" />
        </button>
        <div class="flex flex-col">
          <h1 class="text-lg font-black italic tracking-wider text-primary">SABONG FIGHT</h1>
          <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-widest">LIVE MULTIPLAYER</span>
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

    <div class="max-w-6xl w-full mx-auto p-4 flex-grow flex flex-col lg:grid lg:grid-cols-12 gap-6 items-start pt-[80px]">
      
      <!-- LEFT COLUMN: Game Screen, Live Win Ratios, and Roadmap -->
      <div class="lg:col-span-7 flex flex-col gap-6 w-full">
        <!-- GAME SCREEN & PLAYBACK AREA -->
        <div class="relative w-full aspect-video rounded-3xl overflow-hidden bg-black shadow-2xl border border-white/5 group">
          
          <!-- COOLDOWN OR BETTING STATE (STATIC IMAGE WITH COUNTDOWN OVERLAY) -->
          <div 
            v-if="phase === 'cooldown' || phase === 'betting'" 
            class="w-full h-full relative flex items-center justify-center bg-cover bg-center transition-all duration-500"
            :style="{ backgroundImage: `url(${preFightImg})` }"
          >
            <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]"></div>

            <!-- Circular Countdown Timer -->
            <div class="relative z-10 flex flex-col items-center animate-fade-in">
              <!-- Glowing Ring Circular Timer -->
              <div class="relative w-32 h-32 flex items-center justify-center">
                <svg class="absolute inset-0 w-full h-full transform -rotate-90">
                  <circle 
                    cx="64" 
                    cy="64" 
                    r="56" 
                    stroke="#2d2d2d" 
                    stroke-width="8" 
                    fill="transparent" 
                  />
                  <circle 
                    cx="64" 
                    cy="64" 
                    r="56" 
                    :stroke="phase === 'betting' ? '#ffd700' : '#ff3e3e'" 
                    stroke-width="8" 
                    fill="transparent" 
                    :stroke-dasharray="351.8" 
                    :stroke-dashoffset="dashOffset" 
                    stroke-linecap="round"
                    class="transition-all duration-200"
                  />
                </svg>
                <div class="flex flex-col items-center justify-center z-10">
                  <span class="text-4xl font-black tracking-tight" :class="phase === 'betting' ? 'text-primary' : 'text-red-500'">
                    {{ countdownDisplay }}
                  </span>
                  <span class="text-[9px] text-zinc-400 font-black uppercase tracking-widest mt-1">SECONDS</span>
                </div>
              </div>

              <!-- Context Banners -->
              <div class="mt-4 text-center">
                <div 
                  v-if="phase === 'cooldown'" 
                  class="bg-red-500/10 border border-red-500/20 px-6 py-2 rounded-2xl flex flex-col items-center text-red-500 animate-pulse"
                >
                  <span class="text-xs font-black uppercase tracking-widest">COOLDOWN / SETTLING</span>
                  <span class="text-[9px] text-zinc-400 font-bold uppercase mt-0.5">Transactions processing in backend</span>
                </div>
                <div 
                  v-else 
                  class="bg-primary/10 border border-primary/20 px-6 py-2 rounded-2xl flex flex-col items-center text-primary animate-pulse"
                >
                  <span class="text-xs font-black uppercase tracking-widest">PLEASE PLACE YOUR BETS!</span>
                  <span class="text-[9px] text-zinc-400 font-bold uppercase mt-0.5">Bets lock in {{ countdownDisplay }}s</span>
                </div>
              </div>
            </div>
          </div>

          <!-- FIGHTING PHASE (SYNCHRONIZED VIDEO PLAYBACK) -->
          <div v-else class="w-full h-full relative">
            <!-- Video Element -->
            <video 
              ref="videoPlayer"
              :src="activeVideoSrc"
              class="w-full h-full object-cover"
              playsinline
              muted
              autoplay
              @ended="handleVideoEnded"
            ></video>

            <!-- Glassmorphic HUD overlay during fight -->
            <div class="absolute inset-x-0 top-0 p-4 bg-gradient-to-b from-black/80 to-transparent flex justify-between items-center z-20">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></span>
                <span class="text-[10px] font-black tracking-widest text-red-500 uppercase">FIGHT IN PROGRESS</span>
              </div>
              <div class="bg-black/60 backdrop-blur-md px-3 py-1 rounded-full border border-white/10 flex items-center gap-2">
                <Tv size="12" class="text-primary animate-pulse" />
                <span class="text-[10px] font-black uppercase text-zinc-300">ROUND #{{ roundIndex }}</span>
              </div>
            </div>

            <!-- Fight HUD HP Bars (Authentic Premium Cockfight feel!) -->
            <div class="absolute inset-x-4 bottom-4 z-20 grid grid-cols-2 gap-4">
              <!-- Left Chicken HP -->
              <div class="bg-black/65 backdrop-blur-md border border-white/5 p-3 rounded-2xl flex flex-col gap-1.5">
                <div class="flex justify-between items-center">
                  <span class="text-[10px] font-black uppercase text-red-400">MERON / WHITE</span>
                  <span class="text-[9px] font-black text-zinc-400">HP {{ activeWinner === 'white' ? winnerHpPct : loserHpPct }}%</span>
                </div>
                <div class="w-full h-2 bg-zinc-800 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-red-500 transition-all duration-1000 rounded-full"
                    :style="{ width: (activeWinner === 'white' ? winnerHpPct : loserHpPct) + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Right Chicken HP -->
              <div class="bg-black/65 backdrop-blur-md border border-white/5 p-3 rounded-2xl flex flex-col gap-1.5">
                <div class="flex justify-between items-center">
                  <span class="text-[10px] font-black uppercase text-blue-400">WALA / BLACK</span>
                  <span class="text-[9px] font-black text-zinc-400">HP {{ activeWinner === 'black' ? winnerHpPct : loserHpPct }}%</span>
                </div>
                <div class="w-full h-2 bg-zinc-800 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-blue-500 transition-all duration-1000 rounded-full"
                    :style="{ width: (activeWinner === 'black' ? winnerHpPct : loserHpPct) + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sync Indicator at top corner of video screen -->
          <div class="absolute right-4 top-4 z-30" v-if="phase === 'fighting'">
            <span 
              class="bg-zinc-950/70 border border-white/10 px-2 py-0.5 rounded-md text-[8px] font-bold text-zinc-400 uppercase tracking-widest cursor-pointer hover:bg-zinc-900"
              @click="syncTime"
            >
              SYNC LIVE
            </span>
          </div>
        </div>

        <!-- LIVE WIN RATE STATISTICS (DYNAMIC BARS) -->
        <div class="bg-[#1a1a1a] border border-white/5 p-4 rounded-3xl flex flex-col gap-3 shadow-md">
          <div class="flex justify-between items-center">
            <div class="flex flex-col">
              <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Live Bet Ratios</span>
              <span class="text-xs font-black uppercase tracking-widest text-zinc-300">Current Round Win Rate Estimation</span>
            </div>
            <div class="bg-zinc-900 border border-white/5 px-2 py-1 rounded-xl text-[9px] font-bold text-zinc-400">
              Total Bets: Rs. {{ formatAmount(displayTotalBet) }}
            </div>
          </div>
          <div class="w-full h-6 bg-zinc-900 rounded-2xl overflow-hidden relative flex items-stretch">
            <div 
              class="bg-red-600/90 flex items-center px-4 transition-all duration-500 text-xs font-black italic text-white"
              :style="{ width: `${displayWhiteWinRate}%` }"
            >
              MERON {{ displayWhiteWinRate }}%
            </div>
            <div 
              class="bg-blue-600/90 flex items-center justify-end px-4 transition-all duration-500 text-xs font-black italic text-white"
              :style="{ width: `${displayBlackWinRate}%` }"
            >
              WALA {{ displayBlackWinRate }}%
            </div>
          </div>
        </div>

        <!-- ROADMAP (HISTORICAL TREND PILLS) -->
        <div class="bg-[#1a1a1a] border border-white/5 p-6 rounded-[2rem] flex flex-col gap-4 shadow-md">
          <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
              <Compass class="text-primary w-4 h-4" />
              <span class="text-xs font-black uppercase tracking-wider text-zinc-300">Sabong Roadmap (Results History)</span>
            </div>
            <span class="text-[9px] font-bold text-zinc-500 uppercase">Recent Trends</span>
          </div>

          <div class="flex items-center gap-2 overflow-x-auto py-2 px-1 hide-scrollbar">
            <div v-if="!history.length" class="text-xs text-zinc-500 font-bold uppercase py-2">
              No rounds settled yet in this cycle.
            </div>
            <div 
              v-for="item in history" 
              :key="item.id"
              class="relative shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-black select-none shadow-md group cursor-pointer border-2"
              :class="[
                item.winner === 'white' 
                  ? 'bg-white border-red-500 text-red-500 hover:scale-105 transition-transform' 
                  : 'bg-zinc-950 border-blue-500 text-blue-500 hover:scale-105 transition-transform'
              ]"
            >
              {{ item.winner === 'white' ? 'W' : 'B' }}
              
              <!-- Tooltip -->
              <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-zinc-950 border border-white/10 text-white text-[9px] font-bold py-1 px-2 rounded shadow-xl whitespace-nowrap z-30 uppercase">
                Round #{{ item.round_index }}<br/>
                Winner: {{ item.winner === 'white' ? 'White Cockerel' : 'Black Rooster' }}<br/>
                HP status: {{ item.winner_hp === 'over' ? 'Over 50%' : 'Under 50%' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Betting Choice Panels, HP Brackets, Chips Selection, Action Tabs -->
      <div class="lg:col-span-5 flex flex-col gap-6 w-full">
        <!-- MAIN BETTING PANELS -->
        <div class="grid grid-cols-2 gap-4">
          <!-- MERON (WHITE) MAIN PANEL -->
          <button 
            @click="submitBet('white')"
            :disabled="phase !== 'betting' || placingBet"
            class="group border rounded-[2rem] p-6 text-left flex flex-col justify-between h-40 active:scale-[0.98] transition-all disabled:opacity-50 relative overflow-hidden"
            :class="[
              phase === 'betting' 
                ? 'bg-red-950/20 hover:bg-red-950/30 border-red-500/20 hover:border-red-500/40 cursor-pointer shadow-[0_0_20px_rgba(239,68,68,0.05)]' 
                : 'bg-zinc-900/40 border-zinc-800 cursor-not-allowed'
            ]"
          >
            <!-- Accent diagonal shadow -->
            <div class="absolute -right-10 -bottom-10 w-24 h-24 bg-red-500/5 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>

            <div class="flex justify-between items-start w-full relative z-10">
              <div class="flex flex-col">
                <span class="text-xs font-black text-red-500 uppercase tracking-widest">THE WHITE COCKEREL</span>
                <span class="text-2xl font-black tracking-tight italic text-red-400 mt-1">MERON</span>
              </div>
              <span class="bg-red-500/10 border border-red-500/20 px-2 py-0.5 rounded-full text-[10px] font-black text-red-500">
                1.95x
              </span>
            </div>

            <div class="flex justify-between items-end w-full mt-auto relative z-10">
              <div class="flex flex-col">
                <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Total Pool</span>
                <span class="text-sm font-black text-zinc-300">Rs. {{ formatAmount(totals.white) }}</span>
              </div>
              <div class="flex flex-col items-end" v-if="getUserBetForChoice('white') > 0">
                <span class="text-[8px] font-bold text-red-400 uppercase tracking-widest">Your Bet</span>
                <span class="text-sm font-black text-red-400">Rs. {{ formatAmount(getUserBetForChoice('white')) }}</span>
              </div>
            </div>
          </button>

          <!-- WALA (BLACK) MAIN PANEL -->
          <button 
            @click="submitBet('black')"
            :disabled="phase !== 'betting' || placingBet"
            class="group border rounded-[2rem] p-6 text-left flex flex-col justify-between h-40 active:scale-[0.98] transition-all disabled:opacity-50 relative overflow-hidden"
            :class="[
              phase === 'betting' 
                ? 'bg-blue-950/20 hover:bg-blue-950/30 border-blue-500/20 hover:border-blue-500/40 cursor-pointer shadow-[0_0_20px_rgba(59,130,246,0.05)]' 
                : 'bg-zinc-900/40 border-zinc-800 cursor-not-allowed'
            ]"
          >
            <!-- Accent diagonal shadow -->
            <div class="absolute -right-10 -bottom-10 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>

            <div class="flex justify-between items-start w-full relative z-10">
              <div class="flex flex-col">
                <span class="text-xs font-black text-blue-500 uppercase tracking-widest">THE BLACK ROOSTER</span>
                <span class="text-2xl font-black tracking-tight italic text-blue-400 mt-1">WALA</span>
              </div>
              <span class="bg-blue-500/10 border border-blue-500/20 px-2 py-0.5 rounded-full text-[10px] font-black text-blue-500">
                1.95x
              </span>
            </div>

            <div class="flex justify-between items-end w-full mt-auto relative z-10">
              <div class="flex flex-col">
                <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Total Pool</span>
                <span class="text-sm font-black text-zinc-300">Rs. {{ formatAmount(totals.black) }}</span>
              </div>
              <div class="flex flex-col items-end" v-if="getUserBetForChoice('black') > 0">
                <span class="text-[8px] font-bold text-blue-400 uppercase tracking-widest">Your Bet</span>
                <span class="text-sm font-black text-blue-400">Rs. {{ formatAmount(getUserBetForChoice('black')) }}</span>
              </div>
            </div>
          </button>
        </div>

        <!-- WINNER'S HP SUB-BETS (GRID OUTCOMES) -->
        <div class="bg-[#1a1a1a] border border-white/5 p-6 rounded-[2rem] flex flex-col gap-4 shadow-md">
          <div class="flex items-center gap-2">
            <HeartPulse class="text-primary w-4 h-4" />
            <span class="text-xs font-black uppercase tracking-wider text-zinc-300">Winner's HP Status Bracket (3.80x Payout)</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- MERON HP SUB-BETS -->
            <div class="flex flex-col gap-3">
              <button 
                @click="submitBet('white_over')"
                :disabled="phase !== 'betting' || placingBet"
                class="w-full text-left p-4 border rounded-2xl active:scale-[0.98] transition-all disabled:opacity-50 flex flex-col justify-between gap-2"
                :class="[
                  phase === 'betting' 
                    ? 'bg-red-950/10 hover:bg-red-950/20 border-red-500/10 hover:border-red-500/30 cursor-pointer' 
                    : 'bg-zinc-900/30 border-zinc-800/50 cursor-not-allowed'
                ]"
              >
                <div class="flex justify-between items-center w-full">
                  <span class="text-[10px] font-black uppercase tracking-wider text-red-400">WHITE OVER 50% HP</span>
                  <span class="text-[9px] font-black text-zinc-500 bg-zinc-900 border border-white/5 px-2 py-0.5 rounded-full">3.80x</span>
                </div>
                <div class="flex justify-between items-end w-full">
                  <div class="flex flex-col">
                    <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Pool</span>
                    <span class="text-xs font-black text-zinc-300">Rs. {{ formatAmount(totals.white_over) }}</span>
                  </div>
                  <div class="flex flex-col items-end" v-if="getUserBetForChoice('white_over') > 0">
                    <span class="text-[8px] font-bold text-red-400 uppercase tracking-widest">Bet</span>
                    <span class="text-xs font-black text-red-400">Rs. {{ formatAmount(getUserBetForChoice('white_over')) }}</span>
                  </div>
                </div>
              </button>

              <button 
                @click="submitBet('white_under')"
                :disabled="phase !== 'betting' || placingBet"
                class="w-full text-left p-4 border rounded-2xl active:scale-[0.98] transition-all disabled:opacity-50 flex flex-col justify-between gap-2"
                :class="[
                  phase === 'betting' 
                    ? 'bg-red-950/10 hover:bg-red-950/20 border-red-500/10 hover:border-red-500/30 cursor-pointer' 
                    : 'bg-zinc-900/30 border-zinc-800/50 cursor-not-allowed'
                ]"
              >
                <div class="flex justify-between items-center w-full">
                  <span class="text-[10px] font-black uppercase tracking-wider text-red-400">WHITE UNDER 50% HP</span>
                  <span class="text-[9px] font-black text-zinc-500 bg-zinc-900 border border-white/5 px-2 py-0.5 rounded-full">3.80x</span>
                </div>
                <div class="flex justify-between items-end w-full">
                  <div class="flex flex-col">
                    <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Pool</span>
                    <span class="text-xs font-black text-zinc-300">Rs. {{ formatAmount(totals.white_under) }}</span>
                  </div>
                  <div class="flex flex-col items-end" v-if="getUserBetForChoice('white_under') > 0">
                    <span class="text-[8px] font-bold text-red-400 uppercase tracking-widest">Bet</span>
                    <span class="text-xs font-black text-red-400">Rs. {{ formatAmount(getUserBetForChoice('white_under')) }}</span>
                  </div>
                </div>
              </button>
            </div>

            <!-- WALA HP SUB-BETS -->
            <div class="flex flex-col gap-3">
              <button 
                @click="submitBet('black_over')"
                :disabled="phase !== 'betting' || placingBet"
                class="w-full text-left p-4 border rounded-2xl active:scale-[0.98] transition-all disabled:opacity-50 flex flex-col justify-between gap-2"
                :class="[
                  phase === 'betting' 
                    ? 'bg-blue-950/10 hover:bg-blue-950/20 border-blue-500/10 hover:border-blue-500/30 cursor-pointer' 
                    : 'bg-zinc-900/30 border-zinc-800/50 cursor-not-allowed'
                ]"
              >
                <div class="flex justify-between items-center w-full">
                  <span class="text-[10px] font-black uppercase tracking-wider text-blue-400">BLACK OVER 50% HP</span>
                  <span class="text-[9px] font-black text-zinc-500 bg-zinc-900 border border-white/5 px-2 py-0.5 rounded-full">3.80x</span>
                </div>
                <div class="flex justify-between items-end w-full">
                  <div class="flex flex-col">
                    <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Pool</span>
                    <span class="text-xs font-black text-zinc-300">Rs. {{ formatAmount(totals.black_over) }}</span>
                  </div>
                  <div class="flex flex-col items-end" v-if="getUserBetForChoice('black_over') > 0">
                    <span class="text-[8px] font-bold text-blue-400 uppercase tracking-widest">Bet</span>
                    <span class="text-xs font-black text-blue-400">Rs. {{ formatAmount(getUserBetForChoice('black_over')) }}</span>
                  </div>
                </div>
              </button>

              <button 
                @click="submitBet('black_under')"
                :disabled="phase !== 'betting' || placingBet"
                class="w-full text-left p-4 border rounded-2xl active:scale-[0.98] transition-all disabled:opacity-50 flex flex-col justify-between gap-2"
                :class="[
                  phase === 'betting' 
                    ? 'bg-blue-950/10 hover:bg-blue-950/20 border-blue-500/10 hover:border-blue-500/30 cursor-pointer' 
                    : 'bg-zinc-900/30 border-zinc-800/50 cursor-not-allowed'
                ]"
              >
                <div class="flex justify-between items-center w-full">
                  <span class="text-[10px] font-black uppercase tracking-wider text-blue-400">BLACK UNDER 50% HP</span>
                  <span class="text-[9px] font-black text-zinc-500 bg-zinc-900 border border-white/5 px-2 py-0.5 rounded-full">3.80x</span>
                </div>
                <div class="flex justify-between items-end w-full">
                  <div class="flex flex-col">
                    <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Pool</span>
                    <span class="text-xs font-black text-zinc-300">Rs. {{ formatAmount(totals.black_under) }}</span>
                  </div>
                  <div class="flex flex-col items-end" v-if="getUserBetForChoice('black_under') > 0">
                    <span class="text-[8px] font-bold text-blue-400 uppercase tracking-widest">Bet</span>
                    <span class="text-xs font-black text-blue-400">Rs. {{ formatAmount(getUserBetForChoice('black_under')) }}</span>
                  </div>
                </div>
              </button>
            </div>
          </div>
        </div>

        <!-- CHIPS & ACTIONS BAR -->
        <div class="bg-[#1a1a1a] border border-white/5 p-4 rounded-3xl flex flex-wrap items-center justify-between gap-4 shadow-md">
          <!-- Chips list -->
          <div class="flex items-center gap-2 sm:gap-3">
            <button 
              v-for="val in chipValues" 
              :key="val"
              @click="selectedChip = val"
              class="relative w-10 h-10 sm:w-12 sm:h-12 rounded-full font-black text-xs sm:text-sm flex items-center justify-center border-4 select-none active:scale-90 transition-all cursor-pointer shadow-md"
              :class="[
                selectedChip === val 
                  ? 'border-primary bg-zinc-800 text-primary scale-110 shadow-primary/20 ring-2 ring-primary/45' 
                  : 'border-zinc-700 bg-zinc-900 text-zinc-400 hover:text-zinc-200'
              ]"
            >
              <!-- Minor premium chip lines -->
              <div class="absolute inset-0.5 border border-dashed border-white/5 rounded-full"></div>
              {{ val }}
            </button>
          </div>

          <!-- Custom Actions -->
          <div class="flex items-center gap-2 shrink-0">
            <button 
              @click="doubleChip"
              class="bg-zinc-900 border border-white/5 text-zinc-300 font-bold px-3 py-2 rounded-xl text-xs hover:text-white active:scale-95 transition-all flex items-center gap-1 shadow-sm"
            >
              X2 DOUBLE
            </button>
            <button 
              @click="repeatLastBets"
              :disabled="phase !== 'betting' || !lastRoundBets.length"
              class="bg-zinc-900 border border-white/5 text-zinc-300 font-bold px-3 py-2 rounded-xl text-xs hover:text-white active:scale-95 transition-all disabled:opacity-30 disabled:pointer-events-none flex items-center gap-1 shadow-sm"
            >
              <RotateCcw size="12" /> AGAIN
            </button>
            <button 
              @click="showRulesModal = true"
              class="bg-zinc-900 border border-white/5 text-zinc-300 font-bold px-3 py-2 rounded-xl text-xs hover:text-white active:scale-95 transition-all flex items-center gap-1 shadow-sm"
            >
              <HelpCircle size="12" /> RULES
            </button>
          </div>
        </div>

        <!-- MY ACTIVE BETS / GAME STATS TABS -->
        <div class="bg-[#1a1a1a] border border-white/5 rounded-[2rem] overflow-hidden shadow-md">
          <div class="flex border-b border-white/5 bg-zinc-900/50">
            <button 
              @click="activeTab = 'my-bets'"
              class="flex-1 py-3 text-xs font-black uppercase tracking-widest transition-all"
              :class="activeTab === 'my-bets' ? 'text-primary border-b-2 border-primary bg-[#1a1a1a]' : 'text-zinc-400 hover:text-white'"
            >
              My Active Bets
            </button>
            <button 
              @click="activeTab = 'live-activity'"
              class="flex-1 py-3 text-xs font-black uppercase tracking-widest transition-all"
              :class="activeTab === 'live-activity' ? 'text-primary border-b-2 border-primary bg-[#1a1a1a]' : 'text-zinc-400 hover:text-white'"
            >
              Live Activity
            </button>
          </div>

          <div class="p-6">
            <!-- MY BETS TAB -->
            <div v-if="activeTab === 'my-bets'" class="flex flex-col gap-2">
              <div v-if="!userBets.length" class="text-center py-4 text-xs text-zinc-500 font-bold uppercase">
                No bets placed in this round yet.
              </div>
              <div 
                v-for="bet in userBets" 
                :key="bet.id"
                class="bg-zinc-900 border border-white/5 p-4 rounded-2xl flex justify-between items-center shadow-inner"
              >
                <div class="flex items-center gap-3">
                  <div 
                    class="w-3.5 h-3.5 rounded-full flex shrink-0"
                    :class="[
                      bet.choice.startsWith('white') ? 'bg-red-500' : 'bg-blue-500'
                    ]"
                  ></div>
                  <div class="flex flex-col">
                    <span class="text-[10px] font-black uppercase" :class="bet.choice.startsWith('white') ? 'text-red-400' : 'text-blue-400'">
                      {{ formatChoiceName(bet.choice) }}
                    </span>
                    <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest mt-0.5">Choice Code: {{ bet.choice }}</span>
                  </div>
                </div>

                <div class="flex items-center gap-6">
                  <div class="flex flex-col items-end">
                    <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Amount</span>
                    <span class="text-xs font-black text-zinc-300">Rs. {{ parseFloat(bet.amount).toFixed(2) }}</span>
                  </div>
                  <div class="flex flex-col items-end">
                    <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Status</span>
                    <span class="text-[9px] font-black uppercase tracking-wider text-yellow-400">
                      {{ bet.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- LIVE ACTIVITY TAB -->
            <div v-else class="flex flex-col gap-3">
              <div 
                v-for="act in liveActivity" 
                :key="act.id"
                class="flex justify-between items-center text-[10px] text-zinc-400 uppercase font-black tracking-wider animate-fade-in border-b border-white/5 pb-2"
              >
                <div class="flex items-center gap-2">
                  <Users size="10" class="text-zinc-500" />
                  <span class="text-zinc-300">{{ act.username }}</span>
                  <span class="text-zinc-500 font-bold">placed</span>
                  <span :class="act.choice.startsWith('white') ? 'text-red-400' : 'text-blue-400'">
                    {{ formatChoiceName(act.choice) }}
                  </span>
                </div>
                <span class="text-primary">Rs. {{ act.amount }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- RULES MODAL -->
    <div 
      v-if="showRulesModal" 
      class="fixed inset-0 bg-black/80 flex items-center justify-center p-6 z-[70] backdrop-blur-sm"
      @click.self="showRulesModal = false"
    >
      <div class="bg-[#1a1a1a] border border-white/10 p-6 md:p-8 rounded-[2rem] text-left max-w-lg w-full shadow-2xl animate-fade-in relative">
        <button 
          @click="showRulesModal = false" 
          class="absolute right-4 top-4 p-2 text-zinc-500 hover:text-white rounded-full hover:bg-white/5 active:scale-95 transition"
        >
          <X size="20" />
        </button>

        <div class="flex items-center gap-2 text-primary mb-4">
          <HelpCircle class="w-5 h-5" />
          <h2 class="text-xl font-black italic tracking-wide">SABONG FIGHT RULES</h2>
        </div>

        <div class="space-y-4 text-xs text-zinc-400 font-bold uppercase tracking-wider leading-relaxed">
          <p>
            <span class="text-primary font-black">★ CONCEPTS</span>: Sabong is a live cockfighting match featuring 
            <span class="text-red-400 font-black">MERON (White Cockerel)</span> and 
            <span class="text-blue-400 font-black">WALA (Black Rooster)</span>.
          </p>
          <p>
            <span class="text-primary font-black">★ TIMING</span>: A new fight round takes place every minute:
            <ul class="list-disc pl-4 space-y-1 mt-1 text-[10px]">
              <li><strong class="text-red-500">0s - 10s Cooldown</strong>: Transactions are settled in the backend. Betting is closed.</li>
              <li><strong class="text-primary">10s - 30s Betting</strong>: Pick your fighter and place bets! Countdown timer starts at 20 seconds.</li>
              <li><strong class="text-green-500">30s - 60s Fight</strong>: Live fight video plays showing the outcome of the battle.</li>
            </ul>
          </p>
          <p>
            <span class="text-primary font-black">★ WINNER'S HP Bracket</span>: Double down by betting on the surviving fighter's health ratio (Over 50% or Under 50% HP) for an increased payout rate of <strong class="text-white">3.80x</strong>.
          </p>
          <p>
            <span class="text-primary font-black">★ SECURE SYNC</span>: All bets are resolved transparently. The system automatically seeks live frames based on server synchronization so all online players witness the exact same frame at the exact same millisecond.
          </p>
        </div>
      </div>
    </div>

  </div>

  <!-- Leave Game Confirmation Modal -->
  <ConfirmModal
    :visible="leaveModal"
    title="Leave Game?"
    message="Are you sure you want to leave the Sabong Fight? Any active bets for this round will remain placed."
    confirmLabel="Leave"
    cancelLabel="Stay"
    accentColor="#ef4444"
    accentColorEnd="#f97316"
    @confirm="() => { leaveModal = false; router.push('/') }"
    @cancel="leaveModal = false"
  />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { ArrowLeft, Plus, Tv, HeartPulse, HelpCircle, Compass, X, Users, RotateCcw } from 'lucide-vue-next'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import { useRouter } from 'vue-router'
import axios from 'axios'
import ConfirmModal from '../components/ConfirmModal.vue'

// Asset imports via Vite
import preFightImg from '../assets/chicken_fight/pre-fight-img.jpeg'
import whiteChickenWin from '../assets/chicken_fight/white_chicken_win.mp4'
import blackChickenWin from '../assets/chicken_fight/black_chicken_win.mp4'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()

// Leave confirmation modal
const leaveModal = ref(false)

const handleBack = () => {
  leaveModal.value = true
}

// State
const serverTime = ref(Math.floor(Date.now() / 1000))
const clientTimeOffset = ref(0)
const roundIndex = ref(0)
const secondsPassed = ref(0)
const phase = ref('cooldown')
const minBet = ref(10)
const maxBet = ref(10000)
const commissionRate = ref(10)

const previousRound = ref(null)
const activeRound = ref({ winner: null, winner_hp: null })
const totals = ref({
  white: 0, black: 0,
  white_over: 0, white_under: 0,
  black_over: 0, black_under: 0
})
const userBets = ref([])
const history = ref([])

// UI
const chipValues = [10, 50, 100, 500, 1000]
const selectedChip = ref(100)
const activeTab = ref('my-bets')
const showRulesModal = ref(false)
const placingBet = ref(false)

const lastRoundBets = ref([]) // Holds unsubmitted backup to replicate bets
const liveActivity = ref([]) // Real-time feed

// Video Ref
const videoPlayer = ref(null)

// Tickers
let clockInterval = null
let statePollInterval = null
let activityInterval = null
let randomInterval = null

// Server Synced Loop calculation
const syncTime = async () => {
  try {
    const { data } = await axios.get('/api/games/chicken-fight/state')
    
    // Sync client clocks
    serverTime.value = data.server_time
    clientTimeOffset.value = data.server_time - Math.floor(Date.now() / 1000)
    
    roundIndex.value = data.round_index
    secondsPassed.value = data.seconds_passed
    phase.value = data.phase
    minBet.value = data.min_bet
    maxBet.value = data.max_bet
    commissionRate.value = data.commission_rate
    
    previousRound.value = data.previous_round
    activeRound.value = data.active_round
    totals.value = data.totals
    userBets.value = data.user_bets
    history.value = data.history

    // Sync active bets for double-up / again replication
    if (data.user_bets && data.user_bets.length) {
      lastRoundBets.value = data.user_bets
    }
  } catch (e) {
    console.error('Failed to sync chicken fight state:', e)
  }
}

// Compute values
const countdownDisplay = computed(() => {
  if (phase.value === 'cooldown') {
    return Math.ceil(30 - secondsPassed.value) // 30 down to 20
  } else if (phase.value === 'betting') {
    return Math.ceil(30 - secondsPassed.value) // 20 down to 0
  }
  return 0
})

// Circular dashoffset (Stroke total is 351.8px for 56px radius)
const dashOffset = computed(() => {
  if (phase.value === 'cooldown') {
    // 10s cooldown. Fraction completed inside [0, 10]
    const pct = secondsPassed.value / 10
    return 351.8 * (1 - pct)
  } else if (phase.value === 'betting') {
    // 20s betting. Fraction completed inside [10, 30]
    const pct = (secondsPassed.value - 10) / 20
    return 351.8 * (1 - pct)
  }
  return 351.8
})

const activeVideoSrc = computed(() => {
  if (activeRound.value.winner === 'white') {
    return whiteChickenWin
  } else if (activeRound.value.winner === 'black') {
    return blackChickenWin
  }
  return ''
})

const activeWinner = computed(() => activeRound.value.winner)

// Math helpers
const totalBetAmount = computed(() => {
  return Object.values(totals.value).reduce((a, b) => a + (parseFloat(b) || 0), 0)
})

// --- Display-only randomized stats (obfuscated from real values) ---
const displayWhiteWinRate = ref(52)
const displayBlackWinRate = ref(48)
const randomBetOffset = ref(Math.floor(Math.random() * 95001) + 5000)
const winnerHpPct = ref(70)
const loserHpPct = ref(11)

const displayTotalBet = computed(() => totalBetAmount.value + randomBetOffset.value)

const randomizeDisplayStats = () => {
  // Two values that sum to 100
  const a = Math.floor(Math.random() * 60) + 20  // 20–79
  const b = 100 - a
  displayWhiteWinRate.value = a
  displayBlackWinRate.value = b

  // Random extra amount added to total bets (5,000 – 100,000+)
  randomBetOffset.value = Math.floor(Math.random() * 95001) + 5000

  // Random HP %: winner looks dominant, loser looks near-dead
  winnerHpPct.value = Math.floor(Math.random() * 40) + 55  // 55–94%
  loserHpPct.value  = Math.floor(Math.random() * 20) + 5   // 5–24%
}

// Get user bet for specific choice
const getUserBetForChoice = (choice) => {
  const bets = userBets.value.filter(b => b.choice === choice)
  return bets.reduce((sum, b) => sum + (parseFloat(b.amount) || 0), 0)
}

// Format Name
const formatChoiceName = (choice) => {
  const mapping = {
    'white': 'Meron (White Win)',
    'black': 'Wala (Black Win)',
    'white_over': 'Meron Over 50% HP',
    'white_under': 'Meron Under 50% HP',
    'black_over': 'Wala Over 50% HP',
    'black_under': 'Wala Under 50% HP',
  }
  return mapping[choice] || choice
}

const formatAmount = (val) => {
  return parseFloat(val || 0).toFixed(0)
}

// Bet Chip actions
const doubleChip = () => {
  if (selectedChip.value * 2 <= maxBet.value) {
    selectedChip.value *= 2
  } else {
    selectedChip.value = maxBet.value
  }
}

// Placed bet
const submitBet = async (choice) => {
  if (phase.value !== 'betting') {
    toast.show('Betting is closed for this round.', 'error')
    return
  }

  const amt = selectedChip.value
  if (auth.user?.balance < amt) {
    toast.show('Insufficient balance.', 'error')
    return
  }

  placingBet.value = true
  try {
    const { data } = await axios.post('/api/games/chicken-fight/bet', {
      choice,
      amount: amt
    })

    toast.show(`Bet of Rs. ${amt} on ${formatChoiceName(choice)} placed!`)
    auth.fetchUser()
    syncTime() // Re-sync to fetch new ratios and user_bets
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to place bet', 'error')
  } finally {
    placingBet.value = false
  }
}

// AGAIN button to repeat previous round's bets
const repeatLastBets = async () => {
  if (phase.value !== 'betting') {
    toast.show('Betting is closed for this round.', 'error')
    return
  }
  if (!lastRoundBets.value.length) {
    toast.show('No previous bets to repeat.', 'error')
    return
  }

  // Calculate total needed
  const totalNeeded = lastRoundBets.value.reduce((sum, b) => sum + parseFloat(b.amount), 0)
  if (auth.user?.balance < totalNeeded) {
    toast.show('Insufficient balance to repeat all bets.', 'error')
    return
  }

  placingBet.value = true
  let successCount = 0
  for (const bet of lastRoundBets.value) {
    try {
      await axios.post('/api/games/chicken-fight/bet', {
        choice: bet.choice,
        amount: parseFloat(bet.amount)
      })
      successCount++
    } catch (e) {
      // ignore individual failures, handle gracefully
    }
  }

  if (successCount > 0) {
    toast.show(`Replicated ${successCount} bets!`)
    auth.fetchUser()
    syncTime()
  } else {
    toast.show('Failed to replicate bets.', 'error')
  }
  placingBet.value = false
}

// Clock loop that updates every 100ms
const startClockLoop = () => {
  clockInterval = setInterval(() => {
    const now = Math.floor(Date.now() / 1000) + clientTimeOffset.value
    const currentSec = now % 60
    
    // Check if phase has changed
    let nextPhase = 'cooldown'
    if (currentSec >= 10 && currentSec < 30) {
      nextPhase = 'betting'
    } else if (currentSec >= 30) {
      nextPhase = 'fighting'
    }

    const phaseChanged = (nextPhase !== phase.value)
    
    secondsPassed.value = currentSec
    phase.value = nextPhase
    serverTime.value = now

    // If we transition phases, trigger state fetch to synchronize instantly
    if (phaseChanged) {
      syncTime()
      if (nextPhase === 'cooldown') {
        auth.fetchUser() // Refresh user balance for winnings!
      }
    }

    // Sync fighting video seek
    if (phase.value === 'fighting' && videoPlayer.value) {
      const targetTime = currentSec - 30
      const drift = Math.abs(videoPlayer.value.currentTime - targetTime)
      if (drift > 1.5) {
        videoPlayer.value.currentTime = targetTime
      }
      if (videoPlayer.value.paused) {
        videoPlayer.value.play().catch(() => {})
      }
    }
  }, 100)
}

// Dummy live activity feed to make the game feel vibrant and multi-person!
const startLiveActivity = () => {
  const names = ['Siddharth', 'Fatima', 'Rohan', 'John_Casino', 'Wala_King', 'Meron_Boss', 'BetMaster', 'CockFighter99', 'SuperSabong', 'AviatorGod']
  const choices = ['white', 'black', 'white_over', 'white_under', 'black_over', 'black_under']
  const amounts = [10, 50, 100, 200, 500, 1000]

  liveActivity.value = Array.from({ length: 5 }, (_, i) => ({
    id: i,
    username: names[Math.floor(Math.random() * names.length)],
    choice: choices[Math.floor(Math.random() * choices.length)],
    amount: amounts[Math.floor(Math.random() * amounts.length)],
  }))

  activityInterval = setInterval(() => {
    // Add new feed, keep max 6
    const newFeed = {
      id: Date.now(),
      username: names[Math.floor(Math.random() * names.length)],
      choice: choices[Math.floor(Math.random() * choices.length)],
      amount: amounts[Math.floor(Math.random() * amounts.length)],
    }
    liveActivity.value = [newFeed, ...liveActivity.value.slice(0, 5)]
  }, 4000)
}

const handleVideoEnded = () => {
  syncTime()
}

onMounted(() => {
  syncTime()
  randomizeDisplayStats()
  startClockLoop()
  startLiveActivity()

  // Full state re-sync poll every 8 seconds in the background
  statePollInterval = setInterval(syncTime, 8000)

  // Refresh randomized display stats every 4 seconds
  randomInterval = setInterval(randomizeDisplayStats, 4000)
})

onUnmounted(() => {
  clearInterval(clockInterval)
  clearInterval(statePollInterval)
  clearInterval(activityInterval)
  clearInterval(randomInterval)
})

// Watch activeVideoSrc to reset video playback
watch(activeVideoSrc, () => {
  if (videoPlayer.value) {
    videoPlayer.value.load()
    const target = secondsPassed.value - 30
    if (target >= 0) {
      videoPlayer.value.currentTime = target
      videoPlayer.value.play().catch(() => {})
    }
  }
})
</script>

<style scoped>
/* Hide scrollbar utility */
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

@keyframes fade-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
  animation: fade-in 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
