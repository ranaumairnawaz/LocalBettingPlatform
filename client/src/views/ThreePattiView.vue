<template>
  <div class="min-h-screen bg-[#060e0a] text-gray-100 flex flex-col font-sans select-none pb-12">
    <!-- Header Branding -->
    <header class="bg-[#0b1710]/90 border-b border-[#1b3b28] fixed top-0 left-0 right-0 z-50 backdrop-blur-md px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-amber-500 flex items-center justify-center shadow-lg shadow-emerald-950/50">
          <span class="text-xl font-bold text-white">♠</span>
        </div>
        <div>
          <h1 class="text-xl font-black uppercase tracking-widest bg-gradient-to-r from-emerald-400 via-amber-400 to-yellow-500 bg-clip-text text-transparent">
            Teen Patti Live
          </h1>
          <p class="text-[10px] text-amber-500 font-bold uppercase tracking-wider">High-Roller Neon Casino</p>
        </div>
      </div>

      <div class="flex items-center space-x-6">
        <!-- Balance Display -->
        <div class="flex items-center space-x-3 bg-gradient-to-r from-[#0d1c13] to-[#122b1b] px-4 py-2 rounded-xl border border-emerald-500/20 shadow-md">
          <div class="bg-emerald-500/10 p-1 rounded-lg">
            <span class="text-emerald-400 text-sm font-bold">Rs.</span>
          </div>
          <div class="flex flex-col">
            <span class="text-[9px] text-gray-400 uppercase font-black tracking-wider">Wallet Balance</span>
            <span class="text-base font-black text-emerald-300 tracking-tight">
              {{ balance.toFixed(2) }}
            </span>
          </div>
        </div>

        <button @click="leaveOrQuit" class="bg-[#1a0e0f] hover:bg-[#2e1416] text-rose-400 px-3 py-2 rounded-xl border border-rose-500/20 transition duration-200 text-xs font-black uppercase">
          {{ table ? 'Leave' : 'Back' }}
        </button>
      </div>
    </header>

    <!-- LOBBY / TABLE SELECTION PHASE -->
    <main v-if="!table" class="flex-grow max-w-5xl mx-auto w-full px-4 pt-[100px] flex flex-col justify-center items-center">
      <div class="text-center max-w-lg mb-10">
        <h2 class="text-3xl font-black bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent uppercase tracking-wider mb-2">Select Table Boot</h2>
        <p class="text-xs text-gray-400">Choose from dynamic tables configured by the administrator. Seen players bet double the Blind rate.</p>
      </div>

      <div v-if="bootTiers.length > 0" class="flex items-center justify-center gap-6 w-full max-w-4xl">
        <!-- Previous Button -->
        <button 
          @click="prevTable" 
          :disabled="currentBootIndex === 0"
          class="p-4 rounded-full bg-[#12281a] border border-[#1f4a30] hover:bg-emerald-900/50 hover:border-emerald-500/50 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-lg"
        >
          <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>

        <!-- Carousel Card -->
        <div class="bg-gradient-to-b from-[#0b1b11] to-[#050e09] border-2 border-[#1f4a30] hover:border-emerald-500/80 rounded-[40px] p-10 shadow-[0_0_40px_rgba(16,185,129,0.15)] relative overflow-hidden flex flex-col justify-between transform transition duration-300 w-full max-w-md mx-auto">
          <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-emerald-500 via-amber-400 to-emerald-500"></div>

          <div>
            <div class="flex justify-between items-center mb-8">
              <span class="text-sm font-black text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-4 py-1.5 rounded-full uppercase tracking-widest shadow-[0_0_15px_rgba(16,185,129,0.2)]">Table Live</span>
              <span class="text-sm font-black text-gray-400 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg> 6 Seats Max</span>
            </div>
            
            <h3 class="text-gray-400 text-sm font-black uppercase tracking-widest text-center mb-2">Table Boot Amount</h3>
            <div class="text-6xl font-black text-white tracking-tighter mb-8 flex items-baseline justify-center">
              <span class="text-2xl text-emerald-400 font-bold mr-2">Rs.</span>{{ bootTiers[currentBootIndex] }}
            </div>

            <div class="space-y-4 border-t border-[#12281a] pt-6 mb-10 text-sm text-gray-400 font-medium bg-[#050e09]/50 rounded-2xl p-6">
              <div class="flex justify-between items-center border-b border-white/5 pb-3">
                <span class="uppercase tracking-wider text-[10px] font-black">Blind Chaal Unit:</span>
                <span class="text-white font-bold text-lg">Rs. {{ bootTiers[currentBootIndex] }}</span>
              </div>
              <div class="flex justify-between items-center border-b border-white/5 pb-3">
                <span class="uppercase tracking-wider text-[10px] font-black">Seen Chaal Unit:</span>
                <span class="text-white font-bold text-lg">Rs. {{ bootTiers[currentBootIndex] * 2 }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="uppercase tracking-wider text-[10px] font-black">Raise Limit Multiplier:</span>
                <span class="text-amber-400 font-bold text-lg">2x current</span>
              </div>
            </div>
          </div>

          <button 
            @click="joinTable(bootTiers[currentBootIndex])" 
            class="w-full py-5 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 shadow-[0_0_25px_rgba(16,185,129,0.4)] text-white rounded-2xl text-base font-black uppercase tracking-widest transition-all duration-300 flex items-center justify-center space-x-2 hover:scale-[1.02]"
          >
            <span class="text-2xl leading-none">♠</span>
            <span>Join Table Now</span>
          </button>
        </div>

        <!-- Next Button -->
        <button 
          @click="nextTable" 
          :disabled="currentBootIndex === bootTiers.length - 1"
          class="p-4 rounded-full bg-[#12281a] border border-[#1f4a30] hover:bg-emerald-900/50 hover:border-emerald-500/50 transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-lg"
        >
          <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>
      </div>
    </main>

    <!-- LIVE CASINO VELVET CARD TABLE PHASE -->
    <main v-else class="flex-grow max-w-7xl mx-auto w-full px-4 pt-[100px] flex flex-col justify-between items-center">
      
      <!-- GAME VELVET CARD TABLE COMPONENT -->
      <div class="relative w-full max-w-4xl h-[400px] sm:h-[460px] rounded-[180px]/[220px] bg-gradient-radial from-[#052d15] to-[#041009] border-[12px] border-[#22160d] shadow-[0_25px_50px_rgba(0,0,0,0.8)] border-double flex flex-col items-center justify-center overflow-visible mt-6">
        <div class="absolute inset-2 rounded-[170px]/[210px] border border-emerald-500/10 pointer-events-none"></div>

        <!-- 3D Neon lines decoration -->
        <div class="absolute inset-8 rounded-[150px]/[190px] border border-dashed border-amber-500/5 pointer-events-none"></div>

        <!-- QUEUED OVERLAY (for mid-game joiners) -->
        <div v-if="isWaitingNextRound && table.status === 'playing'" class="absolute inset-0 bg-black/70 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center rounded-[170px]/[210px] pointer-events-none">
          <div class="animate-pulse w-12 h-12 flex items-center justify-center mb-2">
            <svg class="w-10 h-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <h2 class="text-xl font-black text-white uppercase tracking-widest">Match In Progress</h2>
          <p class="text-amber-400 font-bold text-sm mt-1">You are queued for the next round.</p>
        </div>

        <!-- Dynamic Game Status overlays -->
        <div class="flex flex-col items-center z-10 select-none text-center bg-black/40 border border-emerald-500/20 px-6 py-4 rounded-3xl backdrop-blur-md shadow-2xl">
          <div v-if="table.status === 'waiting'">
            <div v-if="countdownSecs !== null" class="flex flex-col items-center">
              <span class="text-3xl font-black text-amber-400 tracking-tight mb-1 animate-pulse">
                {{ countdownSecs }}s
              </span>
              <span class="text-[9px] font-black uppercase tracking-widest text-emerald-400">Dealing Shortly</span>
            </div>
            <div v-else class="flex flex-col items-center">
              <span class="text-sm font-black text-emerald-400 uppercase tracking-widest mb-1">Lobby Waiting</span>
              <span class="text-[10px] text-gray-400 font-bold">Waiting for players ({{ seats.length }}/6 Joined)...</span>
            </div>
          </div>

          <div v-else-if="table.status === 'playing'" class="flex flex-col items-center">
            <span class="text-[9px] font-black uppercase text-emerald-400 tracking-widest mb-1 bg-emerald-500/10 px-2.5 py-0.5 rounded border border-emerald-500/20">LIVE POT PILE</span>
            <div class="text-3xl sm:text-4xl font-black tracking-tight text-white flex items-baseline">
              <span class="text-xs text-emerald-400 font-bold mr-1">Rs.</span>{{ table.pot }}
            </div>
            <div class="text-[10px] font-black text-amber-400 uppercase tracking-wider mt-1.5 flex items-center space-x-1">
              <span>Unit Bet: Rs. {{ table.current_bet_unit }}</span>
            </div>
          </div>

          <div v-else-if="table.status === 'finished'" class="flex flex-col items-center">
            <span class="text-rose-500 text-xs font-black uppercase tracking-widest mb-1 bg-rose-500/10 px-3 py-0.5 rounded border border-rose-500/20 animate-bounce">Showdown Win!</span>
            <div class="text-xl font-black text-amber-300 tracking-wide uppercase">{{ table.winner }} wins</div>
            <div class="text-2xl font-black text-white mt-1">Rs. {{ table.payout }}</div>
            <span v-if="nextRoundSecs !== null" class="text-[9px] text-amber-500 font-bold uppercase tracking-wider mt-1 animate-pulse">Next hand in {{ nextRoundSecs }}s...</span>
            <span v-else class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mt-1">Calculating next round...</span>
          </div>
        </div>

        <!-- SIDESHOW DIALOG PROPOSAL OVERLAY -->
        <div v-if="table.sideshow_request" class="absolute z-30 inset-0 flex items-center justify-center bg-black/60 rounded-[180px]/[220px] backdrop-blur-sm">
          <div class="bg-[#0b1710] border border-amber-500/30 rounded-3xl p-5 max-w-sm w-full mx-6 text-center shadow-2xl">
            <h4 class="text-sm font-black uppercase text-amber-400 tracking-wider mb-2">Sideshow Proposed</h4>
            
            <p v-if="isTargetOfSideshow" class="text-xs text-gray-300 mb-5 leading-relaxed">
              <strong>{{ getUsernameById(table.sideshow_request.requester_id) }}</strong> wants to compare cards with you. If you accept, the player with the weaker hand is immediately folded.
            </p>
            <p v-else class="text-xs text-gray-300 mb-5 leading-relaxed">
              Waiting for <strong>{{ getUsernameById(table.sideshow_request.target_id) }}</strong> to accept the compromise request...
            </p>

            <div v-if="isTargetOfSideshow" class="flex space-x-3">
              <button @click="respondSideshow(true)" class="flex-grow py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition">
                Accept Comparison
              </button>
              <button @click="respondSideshow(false)" class="flex-grow py-2.5 bg-rose-950 hover:bg-rose-900 border border-rose-500/20 text-rose-400 rounded-xl text-xs font-black uppercase tracking-wider transition">
                Decline
              </button>
            </div>
          </div>
        </div>

        <!-- DYNAMIC SEAT POSITIONS ON VELVET FELT TABLE (UP TO 6 SEATS) -->
        <div 
          v-for="seat in seats" 
          :key="seat.seat_index" 
          :class="[
            'absolute z-20 flex flex-col items-center w-28 sm:w-32',
            getSeatPositionClass(seat.seat_index)
          ]"
        >
          <!-- Player Avatar Frame -->
          <div 
            :class="[
              'relative rounded-2xl p-2.5 border flex flex-col items-center w-full transition-all duration-300 shadow-lg',
              seat.user_id === table.turn_user_id && table.status === 'playing'
                ? 'bg-emerald-950/30 border-emerald-400 shadow-emerald-950/80 scale-105 ring-2 ring-emerald-500/20 animate-pulse'
                : seat.is_folded 
                  ? 'bg-black/50 border-zinc-800 opacity-40 shadow-none'
                  : 'bg-[#0b1710]/95 border-[#1b3b28]'
            ]"
          >
            <!-- Turn Outline Progress Bar -->
            <div v-if="seat.user_id === table.turn_user_id && table.status === 'playing'" class="absolute -top-1 left-2 bg-emerald-500 text-[8px] font-black text-black px-1.5 py-0.2 rounded uppercase">
              Turn
            </div>

            <!-- Folded badge -->
            <div v-if="seat.is_folded" class="absolute -top-1 left-2 bg-rose-600 text-[8px] font-black text-white px-1.5 py-0.2 rounded uppercase">
              Fold
            </div>

            <span class="text-xs font-black truncate max-w-full text-white">{{ seat.username }}</span>
            <span class="text-[9px] text-emerald-400 font-bold mt-0.5">Rs. {{ seat.bet_contributed.toFixed(0) }}</span>

            <!-- Status Indicator Badges -->
            <div class="flex space-x-1.5 mt-1.5">
              <span 
                v-if="seat.is_seen" 
                class="bg-emerald-500/10 border border-emerald-500/30 text-[7px] font-black uppercase text-emerald-400 px-1.5 py-0.2 rounded"
              >
                Seen
              </span>
              <span 
                v-else 
                class="bg-amber-500/10 border border-amber-500/30 text-[7px] font-black uppercase text-amber-500 px-1.5 py-0.2 rounded"
              >
                Blind
              </span>
            </div>
          </div>

          <!-- dealt cards sprite containers -->
          <div class="flex space-x-1 mt-2.5 justify-center z-30">
            <!-- 3 card positions dealt to player -->
            <div 
              v-for="(card, cardIdx) in 3" 
              :key="cardIdx"
              :class="[
                'w-7 h-10 sm:w-8 sm:h-12 rounded border flex items-center justify-center shadow-md transform transition duration-300',
                seat.cards && seat.cards[cardIdx]
                  ? 'bg-white border-white scale-105 rotate-0'
                  : seat.is_folded
                    ? 'bg-zinc-800 border-zinc-900 opacity-20'
                    : 'bg-gradient-to-b from-rose-800 to-rose-950 border-rose-600 -rotate-3'
              ]"
            >
              <template v-if="seat.cards && seat.cards[cardIdx]">
                <div class="flex flex-col items-center justify-between h-full p-1 leading-none">
                  <span 
                    :class="[
                      'text-xs font-black tracking-tighter', 
                      ['H', 'D'].includes(getCardSuit(seat.cards[cardIdx])) ? 'text-rose-600' : 'text-zinc-900'
                    ]"
                  >
                    {{ getCardRankLabel(seat.cards[cardIdx]) }}
                  </span>
                  <span 
                    :class="[
                      'text-sm font-bold',
                      ['H', 'D'].includes(getCardSuit(seat.cards[cardIdx])) ? 'text-rose-600' : 'text-zinc-900'
                    ]"
                  >
                    {{ getCardSuitSymbol(seat.cards[cardIdx]) }}
                  </span>
                </div>
              </template>
              <template v-else-if="table.status === 'playing' && !seat.is_folded">
                <span class="text-[10px] font-black text-rose-500/60 uppercase">♠</span>
              </template>
            </div>
          </div>

          <!-- Hand rank label on showdown win -->
          <span v-if="seat.card_rank_name" class="text-[8px] font-black bg-amber-500 text-black px-1.5 py-0.2 rounded mt-1.5 uppercase">
            {{ seat.card_rank_name }}
          </span>
        </div>

      </div>

      <!-- IN-GAME ACTIONS & BETTING CONSOLE FOOTER -->
      <footer class="w-full max-w-4xl bg-[#0b1710]/95 border border-[#1b3b28]/80 rounded-3xl p-4 sm:p-5 mt-8 shadow-2xl flex flex-col sm:flex-row justify-between items-center gap-4">
        
        <!-- Interactive Bet unit slider/indicator -->
        <div class="flex items-center space-x-4 w-full sm:w-auto">
          <div class="flex flex-col">
            <span class="text-[9px] uppercase font-black text-gray-400 tracking-wider">Unit Bet Value</span>
            <span class="text-base font-black text-white">Rs. {{ table.current_bet_unit }}</span>
          </div>

          <!-- Raise Slider (Active on user turn) -->
          <div v-if="isUserTurn" class="flex-grow sm:w-44 flex items-center space-x-2">
            <span class="text-[9px] font-bold text-gray-400">Raise:</span>
            <input 
              type="range" 
              v-model.number="raiseUnit"
              :min="table.current_bet_unit + 5"
              :max="table.current_bet_unit * 2"
              step="5"
              class="w-full h-1 bg-emerald-950 rounded-lg appearance-none cursor-pointer accent-emerald-500"
            />
            <span class="text-[10px] font-black text-emerald-400">Rs. {{ raiseUnit }}</span>
          </div>
        </div>

        <!-- Glassmorphism Controls Console -->
        <div class="flex flex-wrap gap-2 justify-end w-full sm:w-auto">
          <!-- Seen (Look Cards) action -->
          <button 
            v-if="table.status === 'playing' && isParticipant && !localSeen"
            @click="lookAtCards"
            class="px-4 py-3 bg-amber-500 hover:bg-amber-600 text-black font-black uppercase text-xs rounded-xl tracking-wider transition shadow-md shadow-amber-950/20"
          >
            Look Cards
          </button>

          <!-- Pack (Fold) action -->
          <button 
            v-if="table.status === 'playing' && isParticipant"
            @click="packHand"
            class="px-4 py-3 bg-[#1e0d0f] hover:bg-[#341215] border border-rose-500/20 text-rose-400 font-black uppercase text-xs rounded-xl tracking-wider transition shadow-md"
          >
            Pack (Fold)
          </button>

          <!-- Sideshow compromise trigger -->
          <button 
            v-if="table.status === 'playing' && isUserTurn && canRequestSideshow"
            @click="requestSideshow"
            class="px-4 py-3 bg-[#11231a] hover:bg-[#1a3829] border border-amber-500/20 text-amber-400 font-black uppercase text-xs rounded-xl tracking-wider transition shadow-md"
          >
            Sideshow
          </button>

          <!-- Showdown Card reveal trigger -->
          <button 
            v-if="table.status === 'playing' && isUserTurn && canTriggerShow"
            @click="triggerShow"
            class="px-4 py-3 bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-black font-black uppercase text-xs rounded-xl tracking-wider transition shadow-md"
          >
            Show (Rs. {{ localSeen ? table.current_bet_unit * 2 : table.current_bet_unit }})
          </button>

          <!-- Chaal (Match bet) action -->
          <button 
            v-if="table.status === 'playing' && isUserTurn"
            @click="placeChaal"
            class="px-5 py-3 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 text-white font-black uppercase text-xs rounded-xl tracking-wider transition shadow-lg shadow-emerald-950/30 flex flex-col items-center justify-center leading-none"
          >
            <span>Chaal</span>
            <span class="text-[9px] font-bold text-emerald-300 mt-1">Rs. {{ localSeen ? table.current_bet_unit * 2 : table.current_bet_unit }}</span>
          </button>

          <!-- Raise (Bet unit raise) action -->
          <button 
            v-if="table.status === 'playing' && isUserTurn"
            @click="placeRaise"
            class="px-5 py-3 bg-gradient-to-r from-violet-600 to-purple-500 hover:from-violet-700 hover:to-purple-600 text-white font-black uppercase text-xs rounded-xl tracking-wider transition shadow-lg flex flex-col items-center justify-center leading-none"
          >
            <span>Raise To</span>
            <span class="text-[9px] font-bold text-violet-300 mt-1">Rs. {{ raiseUnit }}</span>
          </button>
        </div>

      </footer>

    </main>

  </div>

  <!-- Leave / Fold Confirmation Modal -->
  <ConfirmModal
    :visible="leaveModal"
    title="Leave Table?"
    message="If the game is active, leaving will fold your hand and you will lose any amount you have put in the pot."
    confirmLabel="Leave Table"
    cancelLabel="Keep Playing"
    accentColor="#10b981"
    accentColorEnd="#f59e0b"
    @confirm="confirmLeaveTable"
    @cancel="leaveModal = false"
  />
</template>

<script>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import ConfirmModal from '../components/ConfirmModal.vue';

export default {
  name: 'ThreePattiView',
  setup() {
    const router = useRouter();
    
    // API & Room state bindings
    const balance = ref(0.00);
    const bootTiers = ref([10, 50, 100, 500, 1000]); // Dynamic boots list
    const currentBootIndex = ref(0);
    const table = ref(null);
    const seats = ref([]);
    const countdownSecs = ref(null);
    const nextRoundSecs = ref(null);

    const isWaitingNextRound = computed(() => {
      if (!table.value || !activeUser.value) return false;
      const mySeat = seats.value.find(s => parseInt(s.user_id) === parseInt(activeUser.value));
      return mySeat ? mySeat.is_waiting_next_round : false;
    });

    const nextTable = () => {
      if (currentBootIndex.value < bootTiers.value.length - 1) {
        currentBootIndex.value++;
      }
    };

    const prevTable = () => {
      if (currentBootIndex.value > 0) {
        currentBootIndex.value--;
      }
    };

    // Current local seen status and raise input values
    const localSeen = ref(false);
    const raiseUnit = ref(10);

    // Leave confirmation modal
    const leaveModal = ref(false);

    // Dynamic timer handles
    let countdownInterval = null;
    let tableSyncInterval = null;
    let roomChannel = null;

    // Computed states
    const activeUser = computed(() => {
      const token = localStorage.getItem('token');
      if (!token) return null;
      try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        return payload.sub; // Map sub value or fetch user
      } catch (err) {
        return null;
      }
    });

    const isUserTurn = computed(() => {
      if (!table.value || !table.value.turn_user_id || !activeUser.value) return false;
      return parseInt(table.value.turn_user_id) === parseInt(activeUser.value);
    });

    const isParticipant = computed(() => {
      if (!seats.value || !activeUser.value) return false;
      return seats.value.some(s => parseInt(s.user_id) === parseInt(activeUser.value));
    });

    const isTargetOfSideshow = computed(() => {
      if (!table.value || !table.value.sideshow_request || !activeUser.value) return false;
      return parseInt(table.value.sideshow_request.target_id) === parseInt(activeUser.value);
    });

    const canRequestSideshow = computed(() => {
      if (!table.value || !isParticipant.value || !localSeen.value) return false;
      
      // Can only sideshow if previous player is active and is also SEEN
      const activeSeats = seats.value.filter(s => !s.is_folded);
      if (activeSeats.length < 3) return false; // Show showdown instead

      const mySeat = seats.value.find(s => parseInt(s.user_id) === parseInt(activeUser.value));
      if (!mySeat) return false;

      // Find previous active seat index
      const seatCount = seats.value.length;
      let prevSeatIndex = null;
      for (let i = 1; i <= seatCount; i++) {
        const checkIdx = (mySeat.seat_index - i + seatCount) % seatCount;
        const player = seats.value.find(s => s.seat_index === checkIdx);
        if (player && !player.is_folded) {
          prevSeatIndex = player;
          break;
        }
      }

      return prevSeatIndex && prevSeatIndex.is_seen;
    });

    const canTriggerShow = computed(() => {
      if (!table.value || !isParticipant.value) return false;
      const activeSeats = seats.value.filter(s => !s.is_folded);
      return activeSeats.length === 2; // Can only Show when exactly 2 players remain
    });

    // Seat placement mapping helper (clockwise 0 to 5)
    const getSeatPositionClass = (seatIdx) => {
      const positions = {
        0: 'bottom-2 left-1/2 -translate-x-1/2 translate-y-10 sm:translate-y-12', // Bottom (Player)
        1: 'left-6 top-1/2 -translate-y-1/2 -translate-x-10 sm:-translate-x-12', // Left-middle
        2: 'left-24 top-6 -translate-y-10 sm:-translate-y-12', // Left-top
        3: 'right-24 top-6 -translate-y-10 sm:-translate-y-12', // Right-top
        4: 'right-6 top-1/2 -translate-y-1/2 translate-x-10 sm:translate-x-12', // Right-middle
        5: 'bottom-16 right-1/4 translate-x-12 translate-y-12' // Bottom-right extra
      };
      return positions[seatIdx] || positions[0];
    };

    // Card Rank String Formatter
    const getCardRankLabel = (card) => {
      return card.slice(0, -1);
    };

    // Card Suit Unicode Symbol Formatter
    const getCardSuit = (card) => {
      return card.slice(-1);
    };

    const getCardSuitSymbol = (card) => {
      const suit = card.slice(-1);
      const symbols = { 'H': '♥', 'D': '♦', 'S': '♠', 'C': '♣' };
      return symbols[suit] || suit;
    };

    const getUsernameById = (id) => {
      const seat = seats.value.find(s => parseInt(s.user_id) === parseInt(id));
      return seat ? seat.username : 'Player';
    };

    // Dynamic Boots configurations fetching
    const fetchBoots = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        const res = await axios.get('/api/games/three-patti/boots', {
          headers: { Authorization: `Bearer ${token}` }
        });
        bootTiers.value = res.data;
      } catch (err) {
        console.error('Error fetching boot configurations:', err);
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

    // Join a live table matching selected Boot amount
    const joinTable = async (boot) => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        const res = await axios.post('/api/games/three-patti/join', {
          boot_amount: boot
        }, {
          headers: { Authorization: `Bearer ${token}` }
        });
        
        syncRoomState(res.data.room);
        subscribeToRoomChannel(res.data.room.room_code);
        fetchUserBalance();
      } catch (err) {
        alert(err.response?.data?.message || 'Error joining table.');
      }
    };

    // Place turn Chaal (match bet unit)
    const placeChaal = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        await axios.post('/api/games/three-patti/chaal', {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
        fetchTableUpdate();
        fetchUserBalance();
      } catch (err) {
        alert(err.response?.data?.message || 'Error placing Chaal.');
      }
    };

    // Raise turn Bet unit value
    const placeRaise = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        await axios.post('/api/games/three-patti/raise', {
          new_bet_unit: raiseUnit.value
        }, {
          headers: { Authorization: `Bearer ${token}` }
        });
        fetchTableUpdate();
        fetchUserBalance();
      } catch (err) {
        alert(err.response?.data?.message || 'Error placing Raise.');
      }
    };

    // Seen (Look cards privately)
    const lookAtCards = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        const res = await axios.post('/api/games/three-patti/seen', {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
        localSeen.value = true;
        syncRoomState(res.data.room);
      } catch (err) {
        alert(err.response?.data?.message || 'Error looking at cards.');
      }
    };

    // Fold hand (Pack)
    const packHand = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        await axios.post('/api/games/three-patti/fold', {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
        fetchTableUpdate();
      } catch (err) {
        alert(err.response?.data?.message || 'Error folding hand.');
      }
    };

    // Request private sideshow compromise
    const requestSideshow = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        await axios.post('/api/games/three-patti/sideshow/request', {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
        fetchTableUpdate();
        fetchUserBalance();
      } catch (err) {
        alert(err.response?.data?.message || 'Sideshow request failed.');
      }
    };

    // Accept / Decline sideshow request
    const respondSideshow = async (accept) => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        await axios.post('/api/games/three-patti/sideshow/respond', {
          accept: accept
        }, {
          headers: { Authorization: `Bearer ${token}` }
        });
        fetchTableUpdate();
      } catch (err) {
        alert(err.response?.data?.message || 'Sideshow response failed.');
      }
    };

    // Trigger Final Showdown
    const triggerShow = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        await axios.post('/api/games/three-patti/show', {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
        fetchTableUpdate();
        fetchUserBalance();
      } catch (err) {
        alert(err.response?.data?.message || 'Showdown failed.');
      }
    };

    // Secure leave/quit
    const leaveOrQuit = async () => {
      if (!table.value) {
        router.push('/');
        return;
      }
      leaveModal.value = true;
    };

    const confirmLeaveTable = async () => {
      leaveModal.value = false;
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        await axios.post('/api/games/three-patti/leave', {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
        
        // Clear table and unsubscribe
        if (roomChannel) {
          window.Echo.leave('teen-patti-room.' + table.value.id);
          roomChannel = null;
        }
        table.value = null;
        seats.value = [];
        localSeen.value = false;
        fetchUserBalance();
      } catch (err) {
        alert('Error leaving table.');
      }
    };

    // Fetch active table updates manually
    const fetchTableUpdate = async () => {
      if (!table.value) return;
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        const res = await axios.get('/api/games/three-patti/table/' + table.value.room_code, {
          headers: { Authorization: `Bearer ${token}` }
        });
        syncRoomState(res.data.room);
      } catch (err) {
        console.error('Error fetching table state updates:', err);
      }
    };

    // Synchronize state values
    const syncRoomState = (roomData) => {
      table.value = roomData;
      seats.value = roomData.seats || [];
      
      // Update local seen state
      if (activeUser.value && roomData.seats) {
        const mySeat = roomData.seats.find(s => parseInt(s.user_id) === parseInt(activeUser.value));
        localSeen.value = mySeat ? mySeat.is_seen : false;
      }

      // Initialize Raise bounds
      if (roomData.current_bet_unit) {
        raiseUnit.value = Math.max(roomData.current_bet_unit + 5, raiseUnit.value);
      }

      // Sync waiting countdown expiration timer
      if (roomData.countdown_expires_at) {
        startCountdownTimer(roomData.countdown_expires_at);
      } else {
        clearInterval(countdownInterval);
        countdownSecs.value = null;
      }
    };

    // Sync room waiting countdown
    const startCountdownTimer = (expiresAt) => {
      clearInterval(countdownInterval);
      const updateTimer = () => {
        const diff = expiresAt - Math.floor(Date.now() / 1000);
        if (diff <= 0) {
          countdownSecs.value = 0;
          clearInterval(countdownInterval);
          fetchTableUpdate();
        } else {
          countdownSecs.value = diff;
        }
      };
      updateTimer();
      countdownInterval = setInterval(updateTimer, 1000);
    };

    // Laravel Echo PresenceChannel Subscriptions
    const subscribeToRoomChannel = (roomCode) => {
      if (!window.Echo || !table.value) return;

      if (roomChannel) {
        window.Echo.leave('teen-patti-room.' + table.value.id);
      }

      roomChannel = window.Echo.join('teen-patti-room.' + table.value.id)
        .here((users) => {
          console.log('Joined Echo PresenceChannel. Active users:', users);
        })
        .joining((user) => {
          console.log('User joined room:', user);
          fetchTableUpdate();
        })
        .leaving((user) => {
          console.log('User left room:', user);
          fetchTableUpdate();
        })
        .listen('ThreePattiTableUpdated', (e) => {
          console.log('Received ThreePattiTableUpdated Reverb event:', e);
          syncRoomState(e.gameState);
        });
    };

    let ticker = null;
    onMounted(() => {
      fetchBoots();
      fetchUserBalance();
      ticker = setInterval(() => {
        if (table.value && table.value.countdown_expires_at) {
          const rem = Math.max(0, table.value.countdown_expires_at - Math.floor(Date.now() / 1000));
          countdownSecs.value = rem;
        } else {
          countdownSecs.value = null;
        }

        if (table.value && table.value.next_round_at) {
          const rem = Math.max(0, table.value.next_round_at - Math.floor(Date.now() / 1000));
          nextRoundSecs.value = rem;
          if (rem === 0 && table.value.status === 'finished') {
            fetchTableUpdate(); // Fetch new round automatically
          }
        } else {
          nextRoundSecs.value = null;
        }
      }, 1000);
    });

    onUnmounted(() => {
      if (ticker) clearInterval(ticker);
      clearInterval(countdownInterval);
      if (roomChannel && table.value) {
        window.Echo.leave('teen-patti-room.' + table.value.id);
      }
    });

    return {
      balance,
      bootTiers,
      table,
      seats,
      countdownSecs,
      nextRoundSecs,
      localSeen,
      raiseUnit,
      isUserTurn,
      isParticipant,
      isWaitingNextRound,
      isTargetOfSideshow,
      canRequestSideshow,
      canTriggerShow,
      getSeatPositionClass,
      getCardRankLabel,
      getCardSuit,
      getCardSuitSymbol,
      getUsernameById,
      joinTable,
      currentBootIndex,
      nextTable,
      prevTable,
      lookAtCards,
      placeChaal,
      placeRaise,
      packHand,
      requestSideshow,
      respondSideshow,
      triggerShow,
      leaveOrQuit,
      leaveModal,
      confirmLeaveTable
    };
  }
};
</script>

<style scoped>
.bg-gradient-radial {
  background-image: radial-gradient(circle at center, var(--tw-gradient-from) 0%, var(--tw-gradient-to) 75%);
}
</style>
