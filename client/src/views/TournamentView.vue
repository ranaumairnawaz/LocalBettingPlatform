<template>
  <div class="space-y-6 pb-10">

    <!-- Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0d0c15] via-[#121623] to-[#0a0a12] border border-white/5 p-6 md:p-8">
      <div class="absolute -right-16 -top-16 w-72 h-72 bg-neon-pink/8 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -left-16 -bottom-16 w-72 h-72 bg-neon-cyan/8 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-neon-cyan/40 to-transparent"></div>

      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-2">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] font-black uppercase bg-neon-pink/15 text-neon-pink border border-neon-pink/25">
              <span class="w-1.5 h-1.5 rounded-full bg-neon-pink animate-ping"></span>
              LIVE ARENA
            </span>
          </div>
          <h1 class="text-3xl md:text-4xl font-black text-white italic tracking-wide">
            TOURNAMENT <span class="text-transparent bg-clip-text bg-gradient-to-r from-neon-cyan to-neon-pink">HUB</span>
          </h1>
          <p class="text-slate-400 text-sm font-semibold max-w-lg">Compete in knockout brackets, earn prize money, and climb the leaderboard. Single &amp; Double Elimination formats available.</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <div class="p-4 bg-white/5 border border-white/5 rounded-2xl text-center min-w-[90px]">
            <span class="text-[9px] uppercase tracking-widest text-slate-400 font-bold block">Active</span>
            <span class="text-2xl font-black text-neon-cyan">{{ activeTournaments.length }}</span>
          </div>
          <div class="p-4 bg-white/5 border border-white/5 rounded-2xl text-center min-w-[90px]">
            <span class="text-[9px] uppercase tracking-widest text-slate-400 font-bold block">Open</span>
            <span class="text-2xl font-black text-neon-pink">{{ openTournaments.length }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        class="shrink-0 px-4 py-2 rounded-xl text-[11px] font-black uppercase tracking-wider transition-all duration-200"
        :class="activeTab === tab.key
          ? 'bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/40'
          : 'bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 hover:text-slate-200'"
      >
        {{ tab.label }}
        <span v-if="tab.count > 0" class="ml-1.5 px-1.5 py-0.5 rounded-md bg-neon-pink/20 text-neon-pink text-[9px]">{{ tab.count }}</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="flex flex-col items-center gap-4">
        <div class="relative w-16 h-16">
          <div class="absolute inset-0 rounded-full border-2 border-neon-cyan/20 animate-ping"></div>
          <div class="absolute inset-2 rounded-full border-2 border-t-neon-cyan border-neon-cyan/20 animate-spin"></div>
          <Trophy class="absolute inset-0 m-auto text-neon-cyan" :size="20" />
        </div>
        <p class="text-slate-400 text-sm font-semibold">Loading tournaments...</p>
      </div>
    </div>

    <!-- Tournament Grid -->
    <div v-else-if="filteredTournaments.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div
        v-for="t in filteredTournaments"
        :key="t.id"
        @click="openTournament(t)"
        class="group relative overflow-hidden rounded-2xl bg-[#121623]/60 border border-white/8 hover:border-neon-cyan/30 transition-all duration-300 cursor-pointer hover:shadow-[0_0_25px_rgba(0,242,254,0.08)]"
      >
        <!-- Game Image -->
        <div class="relative aspect-video overflow-hidden">
          <img :src="getGameImage(t)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-75" />
          <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-[#121623] to-transparent"></div>
          <!-- Status & Badge -->
          <div class="absolute top-3 left-3 flex gap-2">
            <span class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase border" :class="statusClasses(t)">
              {{ statusLabel(t) }}
            </span>
            <span v-if="t.bracket_type === 'double_elimination'" class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">
              ⚔️ Double Elim
            </span>
          </div>
          <!-- Timer -->
          <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-md px-2.5 py-1.5 rounded-lg border border-white/10">
            <div class="flex items-center gap-1.5">
              <Timer :size="11" class="text-neon-cyan" />
              <span class="text-[10px] font-black text-white font-mono">{{ getTimeRemaining(t) }}</span>
            </div>
          </div>
        </div>

        <!-- Info -->
        <div class="p-4 space-y-3">
          <div>
            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">{{ t.game?.title || 'Tournament' }}</span>
            <h3 class="text-base font-black text-white italic group-hover:text-neon-cyan transition-colors mt-0.5">{{ t.title }}</h3>
          </div>

          <div class="grid grid-cols-3 gap-2">
            <div class="text-center">
              <span class="text-[8px] text-slate-400 font-bold uppercase block">Entry</span>
              <span class="text-sm font-black text-neon-cyan">{{ currencySymbol }}{{ dynamicFee(t) }}</span>
              <span v-if="t.is_dynamic_fee" class="text-[8px] text-orange-400 font-bold animate-pulse block">⚡ Live</span>
            </div>
            <div class="text-center">
              <span class="text-[8px] text-slate-400 font-bold uppercase block">Pool</span>
              <span class="text-sm font-black text-white">{{ currencySymbol }}{{ t.total_pool || (t.entry_fee * t.registered_players_count).toFixed(0) }}</span>
            </div>
            <div class="text-center">
              <span class="text-[8px] text-slate-400 font-bold uppercase block">Players</span>
              <span class="text-sm font-black text-white">{{ t.registered_players_count }}/{{ t.max_players }}</span>
            </div>
          </div>

          <!-- Player fill bar -->
          <div class="relative h-1.5 bg-white/5 rounded-full overflow-hidden">
            <div
              class="absolute left-0 top-0 h-full rounded-full transition-all duration-500"
              :class="fillPercent(t) >= 80 ? 'bg-gradient-to-r from-orange-500 to-red-500' : 'bg-gradient-to-r from-neon-cyan to-neon-pink'"
              :style="{ width: fillPercent(t) + '%' }"
            ></div>
          </div>

          <!-- FOMO Banner for dynamic fee -->
          <div v-if="t.is_dynamic_fee && t.status === 'open'" class="flex items-center gap-2 p-2.5 rounded-lg bg-orange-500/10 border border-orange-500/25">
            <span class="text-lg">🔥</span>
            <p class="text-[10px] text-orange-300 font-bold leading-tight">
              Price rises every {{ t.fee_increment_every_n_players }} registrations · Next: {{ currencySymbol }}{{ nextDynamicFee(t) }}
            </p>
          </div>

          <button
            @click.stop="openTournament(t)"
            class="w-full py-2.5 rounded-xl text-[11px] font-black uppercase tracking-wider transition-all"
            :class="t.is_open_for_registration && !t.is_full
              ? 'bg-gradient-to-r from-neon-cyan/20 to-neon-pink/20 border border-neon-cyan/30 text-neon-cyan hover:from-neon-cyan/30 hover:to-neon-pink/30'
              : 'bg-white/5 border border-white/5 text-slate-400'"
          >
            {{ t.status === 'in_progress' ? '👁 Watch Live' : t.is_open_for_registration && !t.is_full ? '⚡ Register Now' : 'View Details' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="flex flex-col items-center justify-center py-20 gap-4">
      <Trophy :size="56" class="text-slate-600" />
      <div class="text-center">
        <p class="text-slate-300 font-black text-lg">No Tournaments Found</p>
        <p class="text-slate-500 text-sm mt-1">Check back soon — new tournaments are scheduled regularly!</p>
      </div>
    </div>

    <!-- Registration Modal -->
    <TournamentRegistrationModal
      v-if="selectedTournament && showModal"
      :tournament="selectedTournament"
      :is-open="showModal"
      :currency-symbol="currencySymbol"
      @close="showModal = false"
      @registered="onRegistered"
    />

    <!-- Bracket / Detail Drawer -->
    <transition name="slide-up">
      <div v-if="showBracket && selectedTournament" class="fixed inset-0 z-50 flex flex-col">
        <div class="absolute inset-0 bg-black/85 backdrop-blur-md" @click="showBracket = false"></div>
        <div class="relative mt-auto w-full max-h-[90vh] bg-[#0d0c15] border-t border-white/10 rounded-t-3xl flex flex-col overflow-hidden">
          <!-- Drawer Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 shrink-0">
            <div>
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">{{ selectedTournament.game?.title }}</span>
              <h2 class="text-lg font-black text-white italic">{{ selectedTournament.title }}</h2>
            </div>
            <div class="flex items-center gap-2">
              <button
                v-for="tab in bracketTabs"
                :key="tab.key"
                @click="bracketActiveTab = tab.key"
                class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all"
                :class="bracketActiveTab === tab.key ? 'bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/30' : 'bg-white/5 text-slate-400'"
              >{{ tab.label }}</button>
              <button @click="showBracket = false" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 transition-colors ml-2">
                <X :size="18" class="text-white" />
              </button>
            </div>
          </div>

          <!-- Bracket Content Tabs -->
          <div class="flex-1 overflow-y-auto p-6">
            <!-- My Match Tab -->
            <div v-if="bracketActiveTab === 'my-match'" class="space-y-4">
              <div v-if="!userCurrentMatch" class="text-center py-10 bg-white/3 rounded-2xl border border-white/5 p-6">
                <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                  <Trophy size="28" class="text-slate-400" />
                </div>
                
                <div v-if="isUserEliminated">
                  <h3 class="text-lg font-black text-rose-500 italic uppercase tracking-wider">Eliminated</h3>
                  <p class="text-slate-400 text-sm mt-2 max-w-md mx-auto">
                    You have been eliminated from this tournament. Final Rank: 
                    <span class="text-white font-bold">#{{ userFinalRank }}</span>.
                  </p>
                  <p v-if="parseFloat(userPrizeWon) > 0" class="text-green-400 font-bold text-lg mt-3">
                    Winnings: {{ currencySymbol }}{{ userPrizeWon }}
                  </p>
                </div>
                
                <div v-else-if="selectedTournament.status === 'completed' && isUserWinner">
                  <h3 class="text-lg font-black text-yellow-500 italic uppercase tracking-wider animate-bounce">🏆 Winner!</h3>
                  <p class="text-slate-300 text-sm mt-2 max-w-md mx-auto">
                    Congratulations! You won the tournament!
                  </p>
                  <p class="text-yellow-400 font-black text-2xl mt-3">
                    Winnings: {{ currencySymbol }}{{ userPrizeWon }}
                  </p>
                </div>
                
                <div v-else>
                  <h3 class="text-lg font-black text-neon-cyan italic uppercase tracking-wider">Next Match Pending</h3>
                  <p class="text-slate-400 text-sm mt-2 max-w-md mx-auto">
                    You have successfully advanced! Waiting for your next opponent to finish their match in the current round.
                  </p>
                  <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-neon-cyan/10 border border-neon-cyan/20 text-neon-cyan text-xs">
                    <span class="w-2 h-2 rounded-full bg-neon-cyan animate-pulse"></span>
                    Waiting for bracket generation...
                  </div>
                </div>
                
                <div v-if="userLastMatch" class="mt-6 border-t border-white/5 pt-6 text-left">
                  <h4 class="text-xs font-black uppercase text-slate-500 mb-3">Your Last Match Result</h4>
                  <MatchCard :match="userLastMatch" :currency-symbol="currencySymbol" />
                </div>
              </div>
              
              <div v-else class="space-y-4">
                <div class="p-5 bg-gradient-to-br from-[#121623] to-[#0a0a12] border border-white/10 rounded-2xl space-y-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <span class="inline-flex px-2 py-0.5 rounded text-[8px] font-black uppercase"
                        :class="userCurrentMatch.status === 'in_progress' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : 'bg-slate-500/20 text-slate-400 border border-white/5'"
                      >
                        {{ userCurrentMatch.status }}
                      </span>
                      <h3 class="text-base font-black text-white italic mt-1">Round {{ userCurrentMatch.round_number }} Match {{ userCurrentMatch.match_number }}</h3>
                    </div>
                    
                    <div v-if="userCurrentMatch.status === 'scheduled'" class="text-right">
                      <span class="text-[9px] font-black text-slate-500 uppercase block">Ready Check</span>
                      <span class="text-xs font-mono font-bold text-white animate-pulse">{{ readyTimerText }}</span>
                    </div>
                  </div>
                  
                  <div class="flex items-center justify-between gap-4 py-4 px-3 bg-white/5 rounded-xl border border-white/5">
                    <div class="flex-1 flex flex-col items-center text-center space-y-2">
                      <div class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center font-black text-lg text-white">
                        {{ userCurrentMatch.player1?.username?.charAt(0)?.toUpperCase() || '?' }}
                      </div>
                      <div class="max-w-[100px] truncate font-bold text-sm" :class="userCurrentMatch.player1?.id === auth.user?.id ? 'text-neon-cyan' : 'text-slate-300'">
                        {{ userCurrentMatch.player1?.username || 'TBD' }}
                        <span v-if="userCurrentMatch.player1?.id === auth.user?.id" class="block text-[8px] uppercase tracking-wider text-neon-cyan/85 font-black">(You)</span>
                      </div>
                      <div class="text-[10px]">
                        <span v-if="userCurrentMatch.player1_ready" class="text-emerald-400 font-bold">✓ Ready</span>
                        <span v-else class="text-slate-500 font-semibold">Not Ready</span>
                      </div>
                    </div>
                    
                    <div class="shrink-0 flex flex-col items-center">
                      <span class="text-slate-600 font-black italic text-lg">VS</span>
                      <div class="h-8 w-px bg-white/5 my-1"></div>
                    </div>
                    
                    <div class="flex-1 flex flex-col items-center text-center space-y-2">
                      <div class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center font-black text-lg text-white">
                        {{ userCurrentMatch.player2?.username?.charAt(0)?.toUpperCase() || '?' }}
                      </div>
                      <div class="max-w-[100px] truncate font-bold text-sm" :class="userCurrentMatch.player2?.id === auth.user?.id ? 'text-neon-cyan' : 'text-slate-300'">
                        {{ userCurrentMatch.player2?.username || 'TBD' }}
                        <span v-if="userCurrentMatch.player2?.id === auth.user?.id" class="block text-[8px] uppercase tracking-wider text-neon-cyan/85 font-black">(You)</span>
                      </div>
                      <div class="text-[10px]">
                        <span v-if="userCurrentMatch.player2_ready" class="text-emerald-400 font-bold">✓ Ready</span>
                        <span v-else class="text-slate-500 font-semibold">Not Ready</span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="space-y-3 pt-2">
                    <div v-if="userCurrentMatch.status === 'scheduled'">
                      <button
                        v-if="!isUserReady"
                        @click="markReady"
                        :disabled="actionLoading"
                        class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-green-600 text-white font-black uppercase tracking-wider rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50 text-sm"
                      >
                        ⚡ Mark Myself as Ready
                      </button>
                      <div v-else class="text-center p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-bold rounded-xl text-sm">
                        ✓ You are ready! Waiting for opponent...
                      </div>
                    </div>
                    
                    <div v-if="userCurrentMatch.status === 'in_progress'" class="space-y-3">
                      <p class="text-xs text-orange-400/90 text-center font-semibold animate-pulse">
                        ⚠️ Leaving or forfeiting the match will count as a loss.
                      </p>
                      
                      <button
                        @click="launchTournamentGame"
                        class="w-full py-3.5 bg-gradient-to-r from-neon-cyan to-neon-pink text-white font-black uppercase tracking-wider rounded-xl hover:opacity-90 transition-opacity text-sm"
                      >
                        🎮 Launch Match Room
                      </button>
                      
                      <div class="p-3 bg-white/5 border border-white/5 rounded-xl space-y-2">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block">Report Result</span>
                        <div class="grid grid-cols-2 gap-2">
                          <button
                            @click="reportWinner(auth.user?.id)"
                            :disabled="actionLoading"
                            class="py-2 bg-emerald-500/20 text-emerald-400 font-bold text-xs uppercase rounded-lg hover:bg-emerald-500/30 transition-colors border border-emerald-500/25"
                          >
                            I Won
                          </button>
                          <button
                            @click="reportWinner(opponentUser?.id)"
                            :disabled="actionLoading"
                            class="py-2 bg-rose-500/20 text-rose-400 font-bold text-xs uppercase rounded-lg hover:bg-rose-500/30 transition-colors border border-rose-500/25"
                          >
                            I Lost
                          </button>
                        </div>
                      </div>
                    </div>
                    
                    <button
                      v-if="userCurrentMatch.status === 'scheduled' || userCurrentMatch.status === 'in_progress'"
                      @click="forfeitMatch"
                      :disabled="actionLoading"
                      class="w-full py-2.5 bg-red-500/10 hover:bg-red-500/20 border border-red-500/25 text-red-400 font-bold uppercase tracking-wider text-[10px] rounded-xl transition-all"
                    >
                      🏳️ Forfeit Match
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Players Tab -->
            <div v-if="bracketActiveTab === 'players'" class="space-y-4">
              <h3 class="text-sm font-black text-neon-cyan uppercase tracking-wider mb-2">Registered Players</h3>
              <div v-if="!selectedTournament.registrations || selectedTournament.registrations.length === 0" class="text-center py-8">
                <p class="text-slate-400 text-sm">No players registered yet.</p>
              </div>
              <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <div
                  v-for="reg in selectedTournament.registrations"
                  :key="reg.id"
                  class="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-white/5 hover:border-white/10 transition-colors"
                >
                  <div class="w-8 h-8 rounded-full bg-gradient-to-r from-neon-cyan/20 to-neon-pink/20 flex items-center justify-center font-black text-sm text-neon-cyan border border-neon-cyan/30">
                    {{ reg.user?.username?.charAt(0)?.toUpperCase() || reg.user?.name?.charAt(0)?.toUpperCase() || 'P' }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <h4 class="text-sm font-bold text-white leading-tight truncate">
                      {{ reg.user?.username || reg.user?.name || 'Player' }}
                      <span v-if="reg.user?.id === auth.user?.id" class="text-[9px] bg-neon-cyan/20 text-neon-cyan px-1.5 py-0.5 rounded ml-1 uppercase font-black">You</span>
                    </h4>
                    <p class="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wide">
                      Status: 
                      <span :class="reg.status === 'eliminated' ? 'text-rose-400 font-bold' : 'text-emerald-400 font-bold'">
                        {{ reg.status }}
                      </span>
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bracket Tab -->
            <div v-if="bracketActiveTab === 'bracket'" class="space-y-4">
              <div v-if="bracketLoading" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-neon-cyan"></div>
              </div>
              <div v-else-if="bracketData">
                <!-- Double Elimination Info -->
                <div v-if="selectedTournament.bracket_type === 'double_elimination'" class="mb-4 p-3 rounded-xl bg-purple-500/10 border border-purple-500/20">
                  <p class="text-purple-300 text-xs font-bold">⚔️ Double Elimination — Losers get a second chance via the Lower Bracket</p>
                </div>

                <!-- Upper Bracket -->
                <div class="space-y-2">
                  <h4 class="text-xs font-black uppercase tracking-wider text-neon-cyan mb-3">
                    {{ selectedTournament.bracket_type === 'double_elimination' ? '🏆 Upper Bracket' : '🏆 Bracket' }}
                  </h4>
                  <BracketRound
                    v-for="(round, idx) in upperBracketRounds"
                    :key="'upper-' + idx"
                    :round="round"
                    :round-number="idx + 1"
                    :currency-symbol="currencySymbol"
                  />
                </div>

                <!-- Lower Bracket (Double Elim) -->
                <div v-if="selectedTournament.bracket_type === 'double_elimination' && lowerBracketRounds.length > 0" class="space-y-2 mt-6">
                  <h4 class="text-xs font-black uppercase tracking-wider text-purple-300 mb-3">⚔️ Lower Bracket (Losers)</h4>
                  <BracketRound
                    v-for="(round, idx) in lowerBracketRounds"
                    :key="'lower-' + idx"
                    :round="round"
                    :round-number="idx + 1"
                    :currency-symbol="currencySymbol"
                    bracket-type="lower"
                  />
                </div>

                <!-- Grand Final -->
                <div v-if="grandFinalMatch" class="mt-6 p-4 rounded-2xl bg-gradient-to-r from-yellow-500/10 via-orange-500/10 to-red-500/10 border border-yellow-500/20">
                  <h4 class="text-xs font-black uppercase tracking-wider text-yellow-400 mb-3">🏆 Grand Final</h4>
                  <MatchCard :match="grandFinalMatch" :currency-symbol="currencySymbol" is-final />
                </div>
              </div>
              <div v-else class="text-center py-8">
                <p class="text-slate-400 text-sm">Bracket not yet generated</p>
              </div>
            </div>

            <!-- Leaderboard Tab -->
            <div v-if="bracketActiveTab === 'leaderboard'">
              <TournamentLeaderboard :tournament-id="selectedTournament.id" :currency-symbol="currencySymbol" />
            </div>

            <!-- Info Tab -->
            <div v-if="bracketActiveTab === 'info'" class="space-y-4">
              <!-- Prize Breakdown -->
              <div class="p-4 bg-white/5 rounded-2xl space-y-3">
                <h4 class="text-xs font-black uppercase text-neon-cyan tracking-wider">Prize Distribution</h4>
                <div class="space-y-2">
                  <div class="flex justify-between items-center py-2 border-b border-white/5">
                    <span class="flex items-center gap-2 text-sm text-white font-bold"><Crown :size="14" class="text-yellow-400" /> Winner</span>
                    <span class="text-neon-cyan font-black">{{ currencySymbol }}{{ prizeAmount(selectedTournament, selectedTournament.winner_prize_percentage) }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2 border-b border-white/5">
                    <span class="flex items-center gap-2 text-sm text-white font-bold"><Medal :size="14" class="text-slate-400" /> Runner-up</span>
                    <span class="text-neon-cyan font-black">{{ currencySymbol }}{{ prizeAmount(selectedTournament, selectedTournament.runner_up_prize_percentage) }}</span>
                  </div>
                  <div v-if="selectedTournament.include_semifinalist_prizes" class="flex justify-between items-center py-2">
                    <span class="flex items-center gap-2 text-sm text-white font-bold"><Award :size="14" class="text-orange-400" /> Semifinalists</span>
                    <span class="text-neon-cyan font-black">{{ currencySymbol }}{{ prizeAmount(selectedTournament, selectedTournament.semifinalist_prize_percentage) }}</span>
                  </div>
                </div>
              </div>

              <!-- Tournament Stats -->
              <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-white/5 rounded-xl">
                  <span class="text-[9px] text-slate-400 uppercase font-bold block mb-1">Format</span>
                  <span class="text-white font-black text-sm">{{ selectedTournament.bracket_type === 'double_elimination' ? '⚔️ Double Elim' : '🎯 Single Elim' }}</span>
                </div>
                <div class="p-3 bg-white/5 rounded-xl">
                  <span class="text-[9px] text-slate-400 uppercase font-bold block mb-1">Max Players</span>
                  <span class="text-white font-black text-sm">{{ selectedTournament.max_players }}</span>
                </div>
                <div class="p-3 bg-white/5 rounded-xl">
                  <span class="text-[9px] text-slate-400 uppercase font-bold block mb-1">Commission</span>
                  <span class="text-white font-black text-sm">{{ selectedTournament.platform_commission_percentage }}%</span>
                </div>
                <div class="p-3 bg-white/5 rounded-xl">
                  <span class="text-[9px] text-slate-400 uppercase font-bold block mb-1">Total Pool</span>
                  <span class="text-neon-cyan font-black text-sm">{{ currencySymbol }}{{ selectedTournament.total_pool || 0 }}</span>
                </div>
              </div>

              <!-- Register Button -->
              <button
                v-if="selectedTournament.is_open_for_registration && !selectedTournament.is_full"
                @click="showModal = true; showBracket = false"
                class="w-full py-3.5 rounded-xl bg-gradient-to-r from-neon-cyan to-neon-pink text-white font-black uppercase tracking-wider text-sm hover:opacity-90 transition-opacity"
              >
                Register for {{ currencySymbol }}{{ dynamicFee(selectedTournament) }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Trophy, Timer, X, Crown, Medal, Award } from 'lucide-vue-next'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import TournamentRegistrationModal from '../components/TournamentRegistrationModal.vue'
import TournamentLeaderboard from '../components/TournamentLeaderboard.vue'
import BracketRound from '../components/BracketRound.vue'
import MatchCard from '../components/MatchCard.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const tournaments = ref([])
const loading = ref(true)
const currencySymbol = ref('Rs.')
const activeTab = ref('all')
const showModal = ref(false)
const showBracket = ref(false)
const selectedTournament = ref(null)
const bracketActiveTab = ref('bracket')
const bracketData = ref(null)
const bracketLoading = ref(false)
const nowTime = ref(new Date())
const actionLoading = ref(false)

const tabs = computed(() => [
  { key: 'all', label: 'All', count: tournaments.value.length },
  { key: 'open', label: 'Open', count: openTournaments.value.length },
  { key: 'in_progress', label: 'Live 🔴', count: tournaments.value.filter(t => t.status === 'in_progress').length },
  { key: 'completed', label: 'Completed', count: 0 }
])

const bracketTabs = computed(() => {
  const tabsList = []
  if (!selectedTournament.value) return tabsList

  const isStarted = selectedTournament.value.status === 'in_progress' || selectedTournament.value.status === 'completed'
  const isRegistered = selectedTournament.value.is_registered

  if (selectedTournament.value.status === 'in_progress' && isRegistered) {
    tabsList.push({ key: 'my-match', label: '🎯 My Match' })
  }

  if (isStarted) {
    tabsList.push({ key: 'bracket', label: '🏆 Bracket' })
    tabsList.push({ key: 'leaderboard', label: '📊 Leaderboard' })
  }

  tabsList.push({ key: 'players', label: `👥 Players (${selectedTournament.value.registered_players_count || 0})` })
  tabsList.push({ key: 'info', label: 'ℹ️ Info & Prizes' })

  return tabsList
})

const activeTournaments = computed(() => tournaments.value.filter(t => t.status === 'in_progress'))
const openTournaments = computed(() => tournaments.value.filter(t => t.status === 'open'))

const filteredTournaments = computed(() => {
  if (activeTab.value === 'all') return tournaments.value
  return tournaments.value.filter(t => t.status === activeTab.value)
})

const upperBracketRounds = computed(() => {
  if (!bracketData.value) return []
  const matches = bracketData.value.filter(m => m.bracket_tier !== 'lower')
  const rounds = {}
  matches.forEach(m => {
    if (!rounds[m.round]) rounds[m.round] = []
    rounds[m.round].push(m)
  })
  return Object.values(rounds).filter(r => !r.some(m => m.is_grand_final))
})

const lowerBracketRounds = computed(() => {
  if (!bracketData.value) return []
  const matches = bracketData.value.filter(m => m.bracket_tier === 'lower')
  const rounds = {}
  matches.forEach(m => {
    if (!rounds[m.round]) rounds[m.round] = []
    rounds[m.round].push(m)
  })
  return Object.values(rounds)
})

const grandFinalMatch = computed(() => {
  if (!bracketData.value) return null
  return bracketData.value.find(m => m.is_grand_final)
})

const fetchTournaments = async () => {
  try {
    loading.value = true
    const { data } = await axios.get('/api/tournaments/active')
    tournaments.value = data.tournaments || []
  } catch (e) {
    console.error('Failed to fetch tournaments:', e)
  } finally {
    loading.value = false
  }
}

const fetchSettings = async () => {
  try {
    const { data } = await axios.get('/api/settings')
    if (data.currency_symbol) currencySymbol.value = data.currency_symbol
  } catch (e) {}
}

const fetchBracket = async (tournamentId) => {
  try {
    bracketLoading.value = true
    bracketData.value = null
    const { data } = await axios.get(`/api/tournaments/${tournamentId}/bracket`)
    bracketData.value = data.matches || data || []
  } catch (e) {
    console.error('Failed to fetch bracket:', e)
    bracketData.value = []
  } finally {
    bracketLoading.value = false
  }
}

const openTournament = async (t) => {
  selectedTournament.value = t
  
  try {
    bracketLoading.value = true
    const { data } = await axios.get(`/api/tournaments/${t.id}`)
    selectedTournament.value = { ...t, ...data.tournament }
  } catch (e) {
    console.error('Failed to fetch tournament details:', e)
  } finally {
    bracketLoading.value = false
  }

  if (selectedTournament.value.is_registered || selectedTournament.value.status === 'in_progress' || selectedTournament.value.status === 'completed') {
    if (selectedTournament.value.status === 'in_progress' && selectedTournament.value.is_registered) {
      bracketActiveTab.value = 'my-match'
    } else if (selectedTournament.value.status === 'in_progress' || selectedTournament.value.status === 'completed') {
      bracketActiveTab.value = 'bracket'
    } else {
      bracketActiveTab.value = 'players'
    }
    showBracket.value = true
    fetchBracket(t.id)
  } else {
    showModal.value = true
  }
}

const onRegistered = () => {
  fetchTournaments()
  if (selectedTournament.value) {
    openTournament(selectedTournament.value)
  }
}

const statusClasses = (t) => {
  switch (t.status) {
    case 'open': return 'bg-neon-cyan/15 text-neon-cyan border-neon-cyan/25'
    case 'registration_closed': return 'bg-neon-pink/15 text-neon-pink border-neon-pink/25'
    case 'in_progress': return 'bg-green-500/15 text-green-400 border-green-500/25'
    case 'completed': return 'bg-slate-500/15 text-slate-400 border-slate-500/25'
    default: return 'bg-slate-500/15 text-slate-400 border-slate-500/25'
  }
}

const statusLabel = (t) => {
  switch (t.status) {
    case 'open': return 'Open'
    case 'registration_closed': return 'Starting Soon'
    case 'in_progress': return '🔴 Live'
    case 'completed': return 'Completed'
    default: return 'Draft'
  }
}

const dynamicFee = (t) => {
  if (t.is_dynamic_fee && t.base_entry_fee) {
    const increments = Math.floor(t.registered_players_count / (t.fee_increment_every_n_players || 1))
    return (parseFloat(t.base_entry_fee) + increments * parseFloat(t.fee_increment_amount || 0)).toFixed(2)
  }
  return t.entry_fee
}

const nextDynamicFee = (t) => {
  if (!t.is_dynamic_fee) return t.entry_fee
  const increments = Math.floor(t.registered_players_count / (t.fee_increment_every_n_players || 1))
  return (parseFloat(t.base_entry_fee) + (increments + 1) * parseFloat(t.fee_increment_amount || 0)).toFixed(2)
}

const fillPercent = (t) => {
  return Math.min(100, Math.round((t.registered_players_count / t.max_players) * 100))
}

const getGameImage = (t) => {
  if (t.game?.thumbnail) return `http://localhost:8000/storage/${t.game.thumbnail}`
  if (t.game?.image) return `http://localhost:8000/storage/${t.game.image}`
  return 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=600'
}

const prizeAmount = (t, pct) => {
  const pool = parseFloat(t.total_pool || (t.entry_fee * t.registered_players_count))
  const commission = pool * ((t.platform_commission_percentage || 10) / 100)
  return ((pool - commission) * (pct / 100)).toFixed(2)
}

// Countdown timers
const timers = ref({})
let timerInterval = null

const getTimeRemaining = (t) => {
  return timers.value[t.id] || '--'
}

const updateAllTimers = () => {
  const now = new Date()
  nowTime.value = now
  tournaments.value.forEach(t => {
    let target
    if (t.status === 'open') target = new Date(t.registration_closes_at)
    else if (t.status === 'registration_closed') target = new Date(t.starts_at)
    else { timers.value[t.id] = t.status === 'in_progress' ? 'Live' : 'Done'; return }

    const diff = target - now
    if (diff <= 0) { timers.value[t.id] = 'Ended'; return }

    if (diff > 86400000) timers.value[t.id] = `${Math.floor(diff / 86400000)}d`
    else if (diff > 3600000) timers.value[t.id] = `${Math.floor(diff / 3600000)}h ${Math.floor((diff % 3600000) / 60000)}m`
    else if (diff > 60000) timers.value[t.id] = `${Math.floor(diff / 60000)}m ${Math.floor((diff % 60000) / 1000)}s`
    else timers.value[t.id] = `${Math.floor(diff / 1000)}s`
  })
}

const allMatches = computed(() => {
  if (!selectedTournament.value || !selectedTournament.value.matches) return []
  return Object.values(selectedTournament.value.matches).flat()
})

const userCurrentMatch = computed(() => {
  if (!auth.user) return null
  return allMatches.value.find(m => 
    (m.player1?.id === auth.user.id || m.player2?.id === auth.user.id) && 
    m.status !== 'completed'
  )
})

const userLastMatch = computed(() => {
  if (!auth.user) return null
  const completedMatches = allMatches.value.filter(m => 
    (m.player1?.id === auth.user.id || m.player2?.id === auth.user.id) && 
    m.status === 'completed'
  )
  return completedMatches.sort((a, b) => b.round_number - a.round_number)[0] || null
})

const isUserEliminated = computed(() => {
  if (!auth.user || !selectedTournament.value) return false
  const reg = selectedTournament.value.registrations?.find(r => r.user?.id === auth.user.id)
  return reg?.status === 'eliminated'
})

const isUserWinner = computed(() => {
  if (!auth.user || !selectedTournament.value) return false
  const reg = selectedTournament.value.registrations?.find(r => r.user?.id === auth.user.id)
  return reg?.status === 'winner' || reg?.final_rank === 1
})

const userFinalRank = computed(() => {
  if (!auth.user || !selectedTournament.value) return 0
  const reg = selectedTournament.value.registrations?.find(r => r.user?.id === auth.user.id)
  return reg?.final_rank || 0
})

const userPrizeWon = computed(() => {
  if (!auth.user || !selectedTournament.value) return '0.00'
  const reg = selectedTournament.value.registrations?.find(r => r.user?.id === auth.user.id)
  return reg?.prize_won || '0.00'
})

const opponentUser = computed(() => {
  if (!userCurrentMatch.value || !auth.user) return null
  return userCurrentMatch.value.player1?.id === auth.user.id
    ? userCurrentMatch.value.player2
    : userCurrentMatch.value.player1
})

const isUserReady = computed(() => {
  if (!userCurrentMatch.value || !auth.user) return false
  return userCurrentMatch.value.player1?.id === auth.user.id
    ? userCurrentMatch.value.player1_ready
    : userCurrentMatch.value.player2_ready
})

const readyTimerText = computed(() => {
  if (!userCurrentMatch.value || !userCurrentMatch.value.ready_check_started_at) return '30s'
  const start = new Date(userCurrentMatch.value.ready_check_started_at)
  const deadline = new Date(start.getTime() + 30000)
  const diff = Math.max(0, Math.floor((deadline - nowTime.value) / 1000))
  return `${diff}s`
})

const markReady = async () => {
  if (!userCurrentMatch.value) return
  try {
    actionLoading.value = true
    await axios.post(`/api/tournament-matches/${userCurrentMatch.value.id}/ready`)
    if (selectedTournament.value) {
      await openTournament(selectedTournament.value)
    }
  } catch (e) {
    console.error('Failed to mark ready:', e)
    alert(e.response?.data?.error || 'Failed to mark ready. Please try again.')
  } finally {
    actionLoading.value = false
  }
}

const forfeitMatch = async () => {
  if (!userCurrentMatch.value) return
  if (!confirm('Are you sure you want to forfeit this match? This will count as an immediate loss.')) return
  try {
    actionLoading.value = true
    await axios.post(`/api/tournament-matches/${userCurrentMatch.value.id}/forfeit`)
    if (selectedTournament.value) {
      await openTournament(selectedTournament.value)
    }
  } catch (e) {
    console.error('Failed to forfeit match:', e)
    alert(e.response?.data?.error || 'Failed to forfeit. Please try again.')
  } finally {
    actionLoading.value = false
  }
}

const reportWinner = async (winnerId) => {
  if (!userCurrentMatch.value) return
  const playerLabel = winnerId === auth.user?.id ? 'you won' : 'you lost'
  if (!confirm(`Are you reporting that ${playerLabel}?`)) return
  try {
    actionLoading.value = true
    await axios.post(`/api/tournament-matches/${userCurrentMatch.value.id}/result`, {
      winner_id: winnerId
    })
    if (selectedTournament.value) {
      await openTournament(selectedTournament.value)
    }
  } catch (e) {
    console.error('Failed to report result:', e)
    alert(e.response?.data?.error || 'Failed to report result. Please try again.')
  } finally {
    actionLoading.value = false
  }
}

const launchTournamentGame = () => {
  if (!userCurrentMatch.value || !selectedTournament.value) return
  const slug = selectedTournament.value.game?.slug
  const room = userCurrentMatch.value.game_room_id
  
  if (slug === 'pool_1v1' || slug === 'pool_snooker') {
    router.push(`/pool/${slug}?room=${room}`)
  } else if (slug === 'ludo_1v1') {
    router.push(`/ludo?room=${room}`)
  } else if (slug === 'carrom') {
    router.push(`/carrom?room=${room}`)
  } else if (slug === 'cricket') {
    router.push(`/cricket?room=${room}`)
  } else {
    router.push(`/game/${slug}?room=${room}`)
  }
}

let drawerPollInterval = null

const startDrawerPolling = () => {
  stopDrawerPolling()
  drawerPollInterval = setInterval(async () => {
    if (showBracket.value && selectedTournament.value && selectedTournament.value.status === 'in_progress') {
      try {
        const { data } = await axios.get(`/api/tournaments/${selectedTournament.value.id}`)
        selectedTournament.value = { ...selectedTournament.value, ...data.tournament }
        const res = await axios.get(`/api/tournaments/${selectedTournament.value.id}/bracket`)
        bracketData.value = res.data.matches || res.data || []
      } catch (e) {
        console.error('Failed to poll tournament drawer details:', e)
      }
    }
  }, 5000)
}

const stopDrawerPolling = () => {
  if (drawerPollInterval) {
    clearInterval(drawerPollInterval)
    drawerPollInterval = null
  }
}

watch(showBracket, (newVal) => {
  if (newVal) {
    startDrawerPolling()
  } else {
    stopDrawerPolling()
  }
})

onMounted(async () => {
  await fetchSettings()
  await fetchTournaments()
  updateAllTimers()
  timerInterval = setInterval(updateAllTimers, 1000)
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateY(100%);
}
</style>
