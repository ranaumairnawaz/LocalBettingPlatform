<template>
  <div class="min-h-screen bg-[#0a0c14] text-white flex flex-col font-sans overflow-x-hidden pb-12">
    <!-- Header (Fixed-style premium bar) -->
    <header class="w-full bg-[#0f111a] border-b border-cyan-500/10 py-4 px-6 flex justify-between items-center fixed top-0 left-0 right-0 z-50 shadow-[0_0_20px_rgba(6,182,212,0.05)]">
      <div class="flex items-center gap-3">
        <button 
          @click="handleBack" 
          class="p-2 hover:bg-cyan-500/10 active:scale-95 transition rounded-full text-zinc-400 hover:text-cyan-400"
        >
          <ArrowLeft size="20" />
        </button>
        <div class="flex flex-col">
          <h1 class="text-lg font-black italic tracking-wider text-cyan-500">TOM & JERRY RACE</h1>
          <span class="text-[9px] text-cyan-600 font-bold uppercase tracking-widest">LIVE MULTIPLAYER</span>
        </div>
      </div>
      <div class="bg-[#151825] border border-cyan-500/20 px-4 py-2 rounded-2xl flex items-center gap-3 shadow-inner">
        <div class="flex flex-col items-end">
          <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Your Balance</span>
          <span class="text-cyan-400 font-black text-sm sm:text-base">Rs. {{ parseFloat(auth.user?.balance || 0).toFixed(2) }}</span>
        </div>
        <button 
          @click="$router.push('/wallet')" 
          class="bg-cyan-500 text-black hover:bg-cyan-400 active:scale-95 rounded-xl p-1.5 transition-all shadow-[0_0_10px_rgba(6,182,212,0.3)]"
        >
          <Plus size="18" />
        </button>
      </div>
    </header>

    <div class="max-w-6xl w-full mx-auto p-4 flex-1 pt-[80px]">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Column: Video, Stats, Roadmap -->
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
                      :stroke="phase === 'betting' ? '#06b6d4' : '#f43f5e'" 
                      stroke-width="8" 
                      fill="transparent" 
                      :stroke-dasharray="351.8" 
                      :stroke-dashoffset="dashOffset" 
                      stroke-linecap="round"
                      class="transition-all duration-200"
                    />
                  </svg>
                  <div class="flex flex-col items-center justify-center z-10">
                    <span class="text-4xl font-black tracking-tight" :class="phase === 'betting' ? 'text-cyan-400' : 'text-rose-500'">
                      {{ countdownDisplay }}
                    </span>
                    <span class="text-[9px] text-zinc-400 font-black uppercase tracking-widest mt-1">SECONDS</span>
                  </div>
                </div>

                <!-- Context Banners -->
                <div class="mt-4 text-center">
                  <div 
                    v-if="phase === 'cooldown'" 
                    class="bg-rose-500/10 border border-rose-500/20 px-6 py-2 rounded-2xl flex flex-col items-center text-rose-500 animate-pulse"
                  >
                    <span class="text-xs font-black uppercase tracking-widest">COOLDOWN / SETTLING</span>
                    <span class="text-[9px] text-zinc-400 font-bold uppercase mt-0.5">Transactions processing in backend</span>
                  </div>
                  <div 
                    v-else 
                    class="bg-cyan-500/10 border border-cyan-500/20 px-6 py-2 rounded-2xl flex flex-col items-center text-cyan-400 animate-pulse"
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
                  <span class="w-2.5 h-2.5 bg-yellow-500 rounded-full animate-ping"></span>
                  <span class="text-[10px] font-black tracking-widest text-yellow-500 uppercase">RACE IN PROGRESS</span>
                </div>
                <div class="bg-black/60 backdrop-blur-md px-3 py-1 rounded-full border border-white/10 flex items-center gap-2">
                  <Tv size="12" class="text-cyan-400 animate-pulse" />
                  <span class="text-[10px] font-black uppercase text-zinc-300">ROUND #{{ roundIndex }}</span>
                </div>
              </div>

              <!-- Race HUD HP/Stamina Bars -->
              <div class="absolute inset-x-4 bottom-4 z-20 grid grid-cols-2 gap-4">
                <!-- Left Character Stamina -->
                <div class="bg-black/65 backdrop-blur-md border border-white/5 p-3 rounded-2xl flex flex-col gap-1.5">
                  <div class="flex justify-between items-center">
                    <span class="text-[10px] font-black uppercase text-rose-500">TOM (RED CAR)</span>
                    <span class="text-[9px] font-black text-zinc-400">STAMINA {{ activeWinner === 'tom' ? winnerStaminaPct : loserStaminaPct }}%</span>
                  </div>
                  <div class="w-full h-2 bg-zinc-800 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-rose-500 transition-all duration-1000 rounded-full shadow-[0_0_8px_rgba(244,63,94,0.5)]"
                      :style="{ width: (activeWinner === 'tom' ? winnerStaminaPct : loserStaminaPct) + '%' }"
                    ></div>
                  </div>
                </div>

                <!-- Right Character Stamina -->
                <div class="bg-black/65 backdrop-blur-md border border-white/5 p-3 rounded-2xl flex flex-col gap-1.5">
                  <div class="flex justify-between items-center">
                    <span class="text-[10px] font-black uppercase text-cyan-400">JERRY (BLUE CAR)</span>
                    <span class="text-[9px] font-black text-zinc-400">STAMINA {{ activeWinner === 'jerry' ? winnerStaminaPct : loserStaminaPct }}%</span>
                  </div>
                  <div class="w-full h-2 bg-zinc-800 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-cyan-500 transition-all duration-1000 rounded-full shadow-[0_0_8px_rgba(6,182,212,0.5)]"
                      :style="{ width: (activeWinner === 'jerry' ? winnerStaminaPct : loserStaminaPct) + '%' }"
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
          <div class="bg-[#0f111a] border border-cyan-500/10 p-4 rounded-3xl flex flex-col gap-3 shadow-md">
            <div class="flex justify-between items-center">
              <div class="flex flex-col">
                <span class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Live Bet Ratios</span>
                <span class="text-xs font-black uppercase tracking-widest text-cyan-400">Current Round Win Rate Estimation</span>
              </div>
              <div class="bg-[#0a0c14] border border-cyan-500/20 px-2 py-1 rounded-xl text-[9px] font-bold text-cyan-500">
                Total Bets: Rs. {{ formatAmount(displayTotalBet) }}
              </div>
            </div>
            <div class="w-full h-6 bg-[#0a0c14] rounded-2xl overflow-hidden relative flex items-stretch">
              <div 
                class="bg-rose-600/90 flex items-center px-4 transition-all duration-500 text-xs font-black italic text-white shadow-[inset_0_0_10px_rgba(225,29,72,0.3)]"
                :style="{ width: `${displayTomWinRate}%` }"
              >
                TOM {{ displayTomWinRate }}%
              </div>
              <div 
                class="bg-amber-500/90 flex items-center justify-center px-4 transition-all duration-500 text-xs font-black italic text-white shadow-[inset_0_0_10px_rgba(245,158,11,0.3)] border-x border-white/5"
                :style="{ width: `${displayTieWinRate}%` }"
              >
                TIE {{ displayTieWinRate }}%
              </div>
              <div 
                class="bg-cyan-600/90 flex items-center justify-end px-4 transition-all duration-500 text-xs font-black italic text-white shadow-[inset_0_0_10px_rgba(6,182,212,0.3)]"
                :style="{ width: `${displayJerryWinRate}%` }"
              >
                JERRY {{ displayJerryWinRate }}%
              </div>
            </div>
          </div>

          <!-- ROADMAP (HISTORICAL TREND PILLS) -->
          <div class="bg-[#0f111a] border border-cyan-500/10 p-6 rounded-[2rem] flex flex-col gap-4 shadow-md">
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <Compass class="text-cyan-500 w-4 h-4" />
                <span class="text-xs font-black uppercase tracking-wider text-zinc-300">Race Roadmap (Results History)</span>
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
                  item.winner === 'tom' 
                    ? 'bg-rose-950 border-rose-500 text-rose-400 hover:scale-105 transition-transform' 
                    : item.winner === 'jerry'
                      ? 'bg-cyan-950 border-cyan-500 text-cyan-400 hover:scale-105 transition-transform'
                      : 'bg-amber-950 border-amber-500 text-amber-400 hover:scale-105 transition-transform'
                ]"
              >
                {{ item.winner === 'tom' ? 'T' : (item.winner === 'jerry' ? 'J' : 'X') }}
                
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block bg-[#0a0c14] border border-cyan-500/20 text-white text-[9px] font-bold py-1 px-2 rounded shadow-xl whitespace-nowrap z-30 uppercase">
                  Round #{{ item.round_index }}<br/>
                  Winner: {{ item.winner === 'tom' ? 'Tom (Red Car)' : (item.winner === 'jerry' ? 'Jerry (Blue Car)' : 'Tie (Draw)') }}<br/>
                  Stamina status: {{ item.winner === 'tie' ? 'Balanced' : (item.winner_hp === 'over' ? 'Over 50%' : 'Under 50%') }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Bet Panels, Sub-bets, Action Chips, History -->
        <div class="lg:col-span-5 flex flex-col gap-6 w-full">
          <!-- MAIN BETTING PANELS -->
          <div class="grid grid-cols-3 gap-3">
            <!-- TOM MAIN PANEL -->
            <button 
              @click="submitBet('tom')"
              :disabled="phase !== 'betting' || placingBet"
              class="group border rounded-[1.5rem] p-4 text-left flex flex-col justify-between h-40 active:scale-[0.98] transition-all disabled:opacity-50 relative overflow-hidden"
              :class="[
                phase === 'betting' 
                  ? 'bg-rose-950/20 hover:bg-rose-950/30 border-rose-500/20 hover:border-rose-500/40 cursor-pointer shadow-[0_0_20px_rgba(244,63,94,0.05)]' 
                  : 'bg-zinc-900/40 border-zinc-800 cursor-not-allowed'
              ]"
            >
              <div class="absolute -right-10 -bottom-10 w-20 h-20 bg-rose-500/5 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>
              <div class="flex justify-between items-start w-full relative z-10">
                <div class="flex flex-col">
                  <span class="text-[8px] font-black text-rose-500 uppercase tracking-widest">SPEEDY CAT</span>
                  <span class="text-xl font-black tracking-tight italic text-rose-500 mt-0.5">TOM</span>
                </div>
                <span class="bg-rose-500/10 border border-rose-500/20 px-1.5 py-0.5 rounded-full text-[9px] font-black text-rose-500">
                  2.0x
                </span>
              </div>
              <div class="flex justify-between items-end w-full mt-auto relative z-10">
                <div class="flex flex-col items-end" v-if="getUserBetForChoice('tom') > 0">
                  <span class="text-[7px] font-bold text-rose-400 uppercase tracking-widest">Your Bet</span>
                  <span class="text-xs font-black text-rose-400">Rs. {{ formatAmount(getUserBetForChoice('tom')) }}</span>
                </div>
              </div>
            </button>

            <!-- TIE MAIN PANEL -->
            <button 
              @click="submitBet('tie')"
              :disabled="phase !== 'betting' || placingBet"
              class="group border rounded-[1.5rem] p-4 text-center flex flex-col justify-between h-40 active:scale-[0.98] transition-all disabled:opacity-50 relative overflow-hidden"
              :class="[
                phase === 'betting' 
                  ? 'bg-amber-950/20 hover:bg-amber-950/30 border-amber-500/20 hover:border-amber-500/40 cursor-pointer shadow-[0_0_20px_rgba(245,158,11,0.05)]' 
                  : 'bg-zinc-900/40 border-zinc-800 cursor-not-allowed'
              ]"
            >
              <div class="absolute -right-10 -bottom-10 w-20 h-20 bg-amber-500/5 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>
              <div class="flex flex-col items-center w-full relative z-10">
                <span class="text-[8px] font-black text-amber-500 uppercase tracking-widest">RACE DRAW</span>
                <span class="text-xl font-black tracking-tight italic text-amber-500 mt-0.5">TIE</span>
                <span class="mt-2 bg-amber-500/10 border border-amber-500/20 px-1.5 py-0.5 rounded-full text-[9px] font-black text-amber-500">
                  4.0x
                </span>
              </div>
              <div class="flex flex-col items-center w-full mt-auto relative z-10" v-if="getUserBetForChoice('tie') > 0">
                <span class="text-[7px] font-bold text-amber-400 uppercase tracking-widest">Your Bet</span>
                <span class="text-xs font-black text-amber-400">Rs. {{ formatAmount(getUserBetForChoice('tie')) }}</span>
              </div>
            </button>

            <!-- JERRY MAIN PANEL -->
            <button 
              @click="submitBet('jerry')"
              :disabled="phase !== 'betting' || placingBet"
              class="group border rounded-[1.5rem] p-4 text-right flex flex-col justify-between h-40 active:scale-[0.98] transition-all disabled:opacity-50 relative overflow-hidden"
              :class="[
                phase === 'betting' 
                  ? 'bg-cyan-950/20 hover:bg-cyan-950/30 border-cyan-500/20 hover:border-cyan-500/40 cursor-pointer shadow-[0_0_20px_rgba(6,182,212,0.05)]' 
                  : 'bg-zinc-900/40 border-zinc-800 cursor-not-allowed'
              ]"
            >
              <div class="absolute -right-10 -bottom-10 w-20 h-20 bg-cyan-500/5 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>
              <div class="flex justify-between items-start w-full relative z-10 flex-row-reverse">
                <div class="flex flex-col items-end">
                  <span class="text-[8px] font-black text-cyan-400 uppercase tracking-widest">CLEVER MOUSE</span>
                  <span class="text-xl font-black tracking-tight italic text-cyan-400 mt-0.5">JERRY</span>
                </div>
                <span class="bg-cyan-500/10 border border-cyan-500/20 px-1.5 py-0.5 rounded-full text-[9px] font-black text-cyan-400">
                  2.0x
                </span>
              </div>
              <div class="flex justify-between items-end w-full mt-auto relative z-10">
                <div class="flex flex-col items-start" v-if="getUserBetForChoice('jerry') > 0">
                  <span class="text-[7px] font-bold text-cyan-400 uppercase tracking-widest">Your Bet</span>
                  <span class="text-xs font-black text-cyan-400">Rs. {{ formatAmount(getUserBetForChoice('jerry')) }}</span>
                </div>
              </div>
            </button>
          </div>

          <!-- WINNER'S STAMINA SUB-BETS (GRID OUTCOMES) -->
          <div class="bg-[#0f111a] border border-cyan-500/10 p-6 rounded-[2rem] flex flex-col gap-4 shadow-md">
            <div class="flex items-center gap-2">
              <HeartPulse class="text-cyan-500 w-4 h-4" />
              <span class="text-xs font-black uppercase tracking-wider text-zinc-300">Winner's Stamina Bracket (3.80x Payout)</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- TOM SUB-BETS -->
              <div class="flex flex-col gap-3">
                <button 
                  @click="submitBet('tom_over')"
                  :disabled="phase !== 'betting' || placingBet"
                  class="w-full text-left p-4 border rounded-2xl active:scale-[0.98] transition-all disabled:opacity-50 flex flex-col justify-between gap-2"
                  :class="[
                    phase === 'betting' 
                      ? 'bg-rose-950/10 hover:bg-rose-950/20 border-rose-500/10 hover:border-rose-500/30 cursor-pointer' 
                      : 'bg-zinc-900/30 border-zinc-800/50 cursor-not-allowed'
                  ]"
                >
                  <div class="flex justify-between items-center w-full">
                    <span class="text-[10px] font-black uppercase tracking-wider text-rose-500">TOM OVER 50%</span>
                    <span class="text-[9px] font-black text-zinc-500 bg-zinc-900 border border-white/5 px-2 py-0.5 rounded-full">3.80x</span>
                  </div>
                  <div class="flex justify-between items-end w-full">
                    <div class="flex flex-col">
                      <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Pool</span>
                      <span class="text-xs font-black text-zinc-300">Rs. {{ formatAmount(totals.tom_over) }}</span>
                    </div>
                    <div class="flex flex-col items-end" v-if="getUserBetForChoice('tom_over') > 0">
                      <span class="text-[8px] font-bold text-rose-400 uppercase tracking-widest">Bet</span>
                      <span class="text-xs font-black text-rose-400">Rs. {{ formatAmount(getUserBetForChoice('tom_over')) }}</span>
                    </div>
                  </div>
                </button>

                <button 
                  @click="submitBet('tom_under')"
                  :disabled="phase !== 'betting' || placingBet"
                  class="w-full text-left p-4 border rounded-2xl active:scale-[0.98] transition-all disabled:opacity-50 flex flex-col justify-between gap-2"
                  :class="[
                    phase === 'betting' 
                      ? 'bg-rose-950/10 hover:bg-rose-950/20 border-rose-500/10 hover:border-rose-500/30 cursor-pointer' 
                      : 'bg-zinc-900/30 border-zinc-800/50 cursor-not-allowed'
                  ]"
                >
                  <div class="flex justify-between items-center w-full">
                    <span class="text-[10px] font-black uppercase tracking-wider text-rose-500">TOM UNDER 50%</span>
                    <span class="text-[9px] font-black text-zinc-500 bg-zinc-900 border border-white/5 px-2 py-0.5 rounded-full">3.80x</span>
                  </div>
                  <div class="flex justify-between items-end w-full">
                    <div class="flex flex-col">
                      <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Pool</span>
                      <span class="text-xs font-black text-zinc-300">Rs. {{ formatAmount(totals.tom_under) }}</span>
                    </div>
                    <div class="flex flex-col items-end" v-if="getUserBetForChoice('tom_under') > 0">
                      <span class="text-[8px] font-bold text-rose-400 uppercase tracking-widest">Bet</span>
                      <span class="text-xs font-black text-rose-400">Rs. {{ formatAmount(getUserBetForChoice('tom_under')) }}</span>
                    </div>
                  </div>
                </button>
              </div>

              <!-- JERRY SUB-BETS -->
              <div class="flex flex-col gap-3">
                <button 
                  @click="submitBet('jerry_over')"
                  :disabled="phase !== 'betting' || placingBet"
                  class="w-full text-left p-4 border rounded-2xl active:scale-[0.98] transition-all disabled:opacity-50 flex flex-col justify-between gap-2"
                  :class="[
                    phase === 'betting' 
                      ? 'bg-cyan-950/10 hover:bg-cyan-950/20 border-cyan-500/10 hover:border-cyan-500/30 cursor-pointer' 
                      : 'bg-zinc-900/30 border-zinc-800/50 cursor-not-allowed'
                  ]"
                >
                  <div class="flex justify-between items-center w-full">
                    <span class="text-[10px] font-black uppercase tracking-wider text-cyan-400">JERRY OVER 50%</span>
                    <span class="text-[9px] font-black text-zinc-500 bg-zinc-900 border border-white/5 px-2 py-0.5 rounded-full">3.80x</span>
                  </div>
                  <div class="flex justify-between items-end w-full">
                    <div class="flex flex-col">
                      <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Pool</span>
                      <span class="text-xs font-black text-zinc-300">Rs. {{ formatAmount(totals.jerry_over) }}</span>
                    </div>
                    <div class="flex flex-col items-end" v-if="getUserBetForChoice('jerry_over') > 0">
                      <span class="text-[8px] font-bold text-cyan-400 uppercase tracking-widest">Bet</span>
                      <span class="text-xs font-black text-cyan-400">Rs. {{ formatAmount(getUserBetForChoice('jerry_over')) }}</span>
                    </div>
                  </div>
                </button>

                <button 
                  @click="submitBet('jerry_under')"
                  :disabled="phase !== 'betting' || placingBet"
                  class="w-full text-left p-4 border rounded-2xl active:scale-[0.98] transition-all disabled:opacity-50 flex flex-col justify-between gap-2"
                  :class="[
                    phase === 'betting' 
                      ? 'bg-cyan-950/10 hover:bg-cyan-950/20 border-cyan-500/10 hover:border-cyan-500/30 cursor-pointer' 
                      : 'bg-zinc-900/30 border-zinc-800/50 cursor-not-allowed'
                  ]"
                >
                  <div class="flex justify-between items-center w-full">
                    <span class="text-[10px] font-black uppercase tracking-wider text-cyan-400">JERRY UNDER 50%</span>
                    <span class="text-[9px] font-black text-zinc-500 bg-zinc-900 border border-white/5 px-2 py-0.5 rounded-full">3.80x</span>
                  </div>
                  <div class="flex justify-between items-end w-full">
                    <div class="flex flex-col">
                      <span class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Pool</span>
                      <span class="text-xs font-black text-zinc-300">Rs. {{ formatAmount(totals.jerry_under) }}</span>
                    </div>
                    <div class="flex flex-col items-end" v-if="getUserBetForChoice('jerry_under') > 0">
                      <span class="text-[8px] font-bold text-cyan-400 uppercase tracking-widest">Bet</span>
                      <span class="text-xs font-black text-cyan-400">Rs. {{ formatAmount(getUserBetForChoice('jerry_under')) }}</span>
                    </div>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- CHIPS & ACTIONS BAR -->
          <div class="bg-[#0f111a] border border-cyan-500/10 p-4 rounded-3xl flex flex-wrap items-center justify-between gap-4 shadow-md">
            <!-- Chips list -->
            <div class="flex items-center gap-2 sm:gap-3">
              <button 
                v-for="val in chipValues" 
                :key="val"
                @click="selectedChip = val"
                class="relative w-10 h-10 sm:w-12 sm:h-12 rounded-full font-black text-xs sm:text-sm flex items-center justify-center border-4 select-none active:scale-90 transition-all cursor-pointer shadow-md"
                :style="{ transition: 'transform 0.1s' }"
                :class="[
                  selectedChip === val 
                    ? 'border-cyan-500 bg-[#151825] text-cyan-400 scale-110 shadow-cyan-500/20 ring-2 ring-cyan-500/45' 
                    : 'border-zinc-700 bg-[#0a0c14] text-zinc-400 hover:text-zinc-200'
                ]"
              >
                <div class="absolute inset-0.5 border border-dashed border-white/5 rounded-full"></div>
                {{ val }}
              </button>
            </div>

            <!-- Custom Actions -->
            <div class="flex items-center gap-2 shrink-0">
              <button 
                @click="doubleChip"
                class="bg-[#0a0c14] border border-cyan-500/20 text-cyan-500 font-bold px-3 py-2 rounded-xl text-xs hover:text-white active:scale-95 transition-all flex items-center gap-1 shadow-sm"
              >
                X2 DOUBLE
              </button>
              <button 
                @click="repeatLastBets"
                :disabled="phase !== 'betting' || !lastRoundBets.length"
                class="bg-[#0a0c14] border border-cyan-500/20 text-cyan-500 font-bold px-3 py-2 rounded-xl text-xs hover:text-white active:scale-95 transition-all disabled:opacity-30 disabled:pointer-events-none flex items-center gap-1 shadow-sm"
              >
                <RotateCcw size="12" /> AGAIN
              </button>
              <button 
                @click="showRulesModal = true"
                class="bg-[#0a0c14] border border-cyan-500/20 text-cyan-500 font-bold px-3 py-2 rounded-xl text-xs hover:text-white active:scale-95 transition-all flex items-center gap-1 shadow-sm"
              >
                <HelpCircle size="12" /> RULES
              </button>
            </div>
          </div>

          <!-- MY ACTIVE BETS / GAME STATS TABS -->
          <div class="bg-[#0f111a] border border-cyan-500/10 rounded-[2rem] overflow-hidden shadow-md">
            <div class="flex border-b border-cyan-500/10 bg-[#0a0c14]/50">
              <button 
                @click="activeTab = 'my-bets'"
                class="flex-1 py-3 text-xs font-black uppercase tracking-widest transition-all"
                :class="activeTab === 'my-bets' ? 'text-cyan-500 border-b-2 border-cyan-500 bg-[#0f111a]' : 'text-zinc-400 hover:text-white'"
              >
                My Active Bets
              </button>
              <button 
                @click="activeTab = 'live-activity'"
                class="flex-1 py-3 text-xs font-black uppercase tracking-widest transition-all"
                :class="activeTab === 'live-activity' ? 'text-cyan-500 border-b-2 border-cyan-500 bg-[#0f111a]' : 'text-zinc-400 hover:text-white'"
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
                        bet.choice.startsWith('tom') ? 'bg-rose-500' : (bet.choice.startsWith('jerry') ? 'bg-cyan-500' : 'bg-amber-500')
                      ]"
                    ></div>
                    <div class="flex flex-col">
                      <span class="text-[10px] font-black uppercase" :class="bet.choice.startsWith('tom') ? 'text-rose-400' : (bet.choice.startsWith('jerry') ? 'text-cyan-400' : 'text-amber-400')">
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
                    <span :class="act.choice.startsWith('tom') ? 'text-rose-400' : (act.choice.startsWith('jerry') ? 'text-cyan-400' : 'text-amber-400')">
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
          <h2 class="text-xl font-black italic tracking-wide">TOM & JERRY RACE RULES</h2>
        </div>

        <div class="space-y-4 text-xs text-zinc-400 font-bold uppercase tracking-wider leading-relaxed">
          <p>
            <span class="text-primary font-black">★ CONCEPTS</span>: Tom & Jerry Race is a live multiplayer chase featuring 
            <span class="text-cyan-400 font-black">TOM (Speedy Blue Cat)</span> and 
            <span class="text-orange-400 font-black">JERRY (Clever Orange Mouse)</span>.
          </p>
          <p>
            <span class="text-primary font-black">★ TIMING</span>: A new chase round takes place every minute:
            <ul class="list-disc pl-4 space-y-1 mt-1 text-[10px]">
              <li><strong class="text-red-500">0s - 10s Cooldown</strong>: Transactions are settled in the backend. Betting is closed.</li>
              <li><strong class="text-primary">10s - 30s Betting</strong>: Pick your runner and place bets! Countdown timer starts at 20 seconds.</li>
              <li><strong class="text-green-500">30s - 60s Race</strong>: Live race video plays showing the outcome of the race.</li>
            </ul>
          </p>
          <p>
            <span class="text-primary font-black">★ WINNER'S STAMINA Bracket</span>: Double down by betting on the winning character's stamina ratio (Over 50% or Under 50% Stamina) for an increased payout rate of <strong class="text-white">4.00x</strong>.
          </p>
          <p>
            <span class="text-amber-500 font-black">★ TIE OPTION</span>: Bet on a Draw/Tie for a massive <strong class="text-white">4.00x</strong> payout! A tie occurs when both racers cross the finish line simultaneously.
          </p>
          <p>
            <span class="text-primary font-black">★ SECURE SYNC</span>: All bets are resolved transparently. The system automatically seeks live frames based on server synchronization so all online players witness the exact same frame at the exact same millisecond.
          </p>
        </div>
      </div>
    </div>

    <!-- Leave Game Confirmation Modal -->
    <ConfirmModal
      :visible="leaveModal"
      title="Leave Game?"
      message="Are you sure you want to leave the Tom &amp; Jerry Race? Any active bets for this round will remain placed."
      confirmLabel="Leave"
      cancelLabel="Stay"
      accentColor="#06b6d4"
      accentColorEnd="#8b5cf6"
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
import preFightImg from '../assets/tom_and_jerry/create_an_image_where_both_202605220403.jpeg'
import tomWins from '../assets/tom_and_jerry/tom_wins.mp4'
import jerryWins from '../assets/tom_and_jerry/jerry_wins.mp4'
import tomJerryTie from '../assets/tom_and_jerry/tomandjerry_tie.mp4'

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
  tom: 0, jerry: 0,
  tom_over: 0, tom_under: 0,
  jerry_over: 0, jerry_under: 0
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
    const { data } = await axios.get('/api/games/tom-jerry/state')
    
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
    console.error('Failed to sync Tom & Jerry state:', e)
  }
}

// Compute values
const countdownDisplay = computed(() => {
  if (phase.value === 'cooldown') {
    return Math.ceil(10 - secondsPassed.value) // 10 down to 0
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
  if (activeRound.value.winner === 'tom') {
    return tomWins
  } else if (activeRound.value.winner === 'jerry') {
    return jerryWins
  } else if (activeRound.value.winner === 'tie') {
    return tomJerryTie
  }
  return ''
})

const activeWinner = computed(() => activeRound.value.winner)

// Math helpers
const totalBetAmount = computed(() => {
  return Object.values(totals.value).reduce((a, b) => a + (parseFloat(b) || 0), 0)
})

// --- Display-only randomized stats (obfuscated from real values) ---
const displayTomWinRate = ref(38)
const displayTieWinRate = ref(18)
const displayJerryWinRate = ref(44)
const randomBetOffset = ref(Math.floor(Math.random() * 95001) + 5000)
const winnerStaminaPct = ref(72)
const loserStaminaPct = ref(14)

const displayTotalBet = computed(() => totalBetAmount.value + randomBetOffset.value)

const randomizeDisplayStats = () => {
  // Generate three random win-rate values that always sum to 100
  const a = Math.floor(Math.random() * 50) + 25  // 25–74
  const b = Math.floor(Math.random() * 20) + 5   // 5–24
  const c = 100 - a - b                           // remainder (always >= 2)
  displayTomWinRate.value = a
  displayTieWinRate.value = b
  displayJerryWinRate.value = c

  // Random extra amount added to total bets (5,000 – 100,000+)
  randomBetOffset.value = Math.floor(Math.random() * 95001) + 5000

  // Random stamina %: winner looks dominant, loser looks exhausted
  winnerStaminaPct.value = Math.floor(Math.random() * 40) + 55  // 55–94%
  loserStaminaPct.value  = Math.floor(Math.random() * 20) + 5   // 5–24%
}

// Get user bet for specific choice
const getUserBetForChoice = (choice) => {
  const bets = userBets.value.filter(b => b.choice === choice)
  return bets.reduce((sum, b) => sum + (parseFloat(b.amount) || 0), 0)
}

// Format Name
const formatChoiceName = (choice) => {
  const mapping = {
    'tom': 'Tom (Cat Win)',
    'jerry': 'Jerry (Mouse Win)',
    'tie': 'Tie (Draw)',
    'tom_over': 'Tom Over 50% Stamina',
    'tom_under': 'Tom Under 50% Stamina',
    'jerry_over': 'Jerry Over 50% Stamina',
    'jerry_under': 'Jerry Under 50% Stamina',
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
    const { data } = await axios.post('/api/games/tom-jerry/bet', {
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
  if (!lastRoundBets.value.length) return

  placingBet.value = true
  try {
    for (const bet of lastRoundBets.value) {
      if (auth.user?.balance < bet.amount) {
        toast.show('Insufficient balance for all repeating bets.', 'error')
        break
      }
      await axios.post('/api/games/tom-jerry/bet', {
        choice: bet.choice,
        amount: bet.amount
      })
    }
    toast.show('Repeated all active bets from last round!')
    auth.fetchUser()
    syncTime()
  } catch (e) {
    toast.show(e.response?.data?.message || 'Failed to repeat bets', 'error')
  } finally {
    placingBet.value = false
  }
}

// Time seeking synchronization for video
const syncVideoPlayback = () => {
  if (!videoPlayer.value || phase.value !== 'fighting') return
  
  // Fighting is from 30s to 60s
  const expectedVideoTime = secondsPassed.value - 30
  
  if (expectedVideoTime >= 0 && expectedVideoTime < 30) {
    // If mismatch is greater than 1.5 seconds, force sync seeking
    if (Math.abs(videoPlayer.value.currentTime - expectedVideoTime) > 1.5) {
      videoPlayer.value.currentTime = expectedVideoTime
    }
    if (videoPlayer.value.paused) {
      videoPlayer.value.play().catch(err => console.log('Video play error:', err))
    }
  }
}

const handleVideoEnded = () => {
  console.log('Synchronized video play finished.')
}

// Live Dummy Feeds simulating dynamic room
const generateLiveActivity = () => {
  const users = ['alpha_runner', 'lucky_cat', 'mouse_trap', 'tommy_g', 'jerry_fan', 'casinoking', 'gold_miner', 'speedy', 'cyber_jock', 'blaster']
  const choices = ['tom', 'jerry', 'tie', 'tom_over', 'tom_under', 'jerry_over', 'jerry_under']
  const amounts = [10, 50, 100, 200, 500, 1000]

  const randomUser = users[Math.floor(Math.random() * users.length)]
  const randomChoice = choices[Math.floor(Math.random() * choices.length)]
  const randomAmt = amounts[Math.floor(Math.random() * amounts.length)]

  liveActivity.value.unshift({
    id: Date.now(),
    username: randomUser,
    choice: randomChoice,
    amount: randomAmt
  })

  if (liveActivity.value.length > 7) {
    liveActivity.value.pop()
  }
}

// Synchronize timing loop on clock tiks
onMounted(() => {
  syncTime()
  randomizeDisplayStats()

  // Dynamic state syncing with server
  statePollInterval = setInterval(syncTime, 8000)

  // Refresh randomized display stats every 4 seconds
  randomInterval = setInterval(randomizeDisplayStats, 4000)

  // Local client seconds tick
  clockInterval = setInterval(() => {
    secondsPassed.value = (secondsPassed.value + 1) % 60
    
    if (secondsPassed.value === 0) {
      phase.value = 'cooldown'
      syncTime()
    } else if (secondsPassed.value === 10) {
      phase.value = 'betting'
      syncTime()
    } else if (secondsPassed.value === 30) {
      phase.value = 'fighting'
      syncTime()
    }

    // Direct local seek correction
    if (phase.value === 'fighting') {
      syncVideoPlayback()
    }
  }, 1000)

  // Fake active lobby feeds
  activityInterval = setInterval(generateLiveActivity, 2500)
  for (let i = 0; i < 4; i++) {
    generateLiveActivity()
  }
})

onUnmounted(() => {
  clearInterval(clockInterval)
  clearInterval(statePollInterval)
  clearInterval(activityInterval)
  clearInterval(randomInterval)
})

// Keep video seeking correct if DOM switches or synchronizer updates values
watch(secondsPassed, () => {
  if (phase.value === 'fighting') {
    syncVideoPlayback()
  }
})
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
