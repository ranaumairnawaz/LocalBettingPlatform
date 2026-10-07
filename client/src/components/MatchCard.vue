<template>
  <div
    class="relative p-3 rounded-xl border transition-all"
    :class="[
      isFinal ? 'border-yellow-500/30 bg-gradient-to-r from-yellow-500/8 to-orange-500/8 shadow-[0_0_20px_rgba(234,179,8,0.1)]' :
      bracketType === 'lower' ? 'border-purple-500/20 bg-purple-500/5' :
      'border-white/8 bg-white/3'
    ]"
  >
    <!-- Match Status Badge -->
    <div v-if="match.status" class="absolute top-2 right-2">
      <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded"
        :class="match.status === 'completed' ? 'bg-green-500/20 text-green-400' :
                match.status === 'in_progress' ? 'bg-red-500/20 text-red-400' :
                'bg-slate-500/20 text-slate-400'"
      >{{ match.status === 'in_progress' ? '🔴' : '' }}{{ match.status }}</span>
    </div>

    <!-- Player 1 -->
    <div class="flex items-center justify-between gap-2 mb-1.5">
      <div class="flex items-center gap-2 min-w-0">
        <div class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black shrink-0"
          :class="match.winner_id === match.player1_id
            ? 'bg-green-500/20 text-green-400 ring-1 ring-green-500/40'
            : match.winner_id && match.winner_id !== match.player1_id
              ? 'bg-red-500/10 text-slate-500'
              : 'bg-white/10 text-slate-300'"
        >
          {{ match.player1?.username?.charAt(0)?.toUpperCase() || '?' }}
        </div>
        <span class="text-xs font-bold truncate"
          :class="match.winner_id === match.player1_id ? 'text-green-400' : match.player1 ? 'text-slate-200' : 'text-slate-500'">
          {{ match.player1?.username || 'TBD' }}
        </span>
        <span v-if="match.winner_id === match.player1_id" class="text-[9px] text-green-400 font-black shrink-0">✓ W</span>
      </div>
      <span v-if="match.score_player1 !== null && match.score_player1 !== undefined" class="text-xs font-black text-white shrink-0">
        {{ match.score_player1 }}
      </span>
    </div>

    <!-- Divider -->
    <div class="flex items-center gap-1.5 my-1">
      <div class="flex-1 h-px bg-white/5"></div>
      <span class="text-[8px] font-black text-slate-500">VS</span>
      <div class="flex-1 h-px bg-white/5"></div>
    </div>

    <!-- Player 2 -->
    <div class="flex items-center justify-between gap-2 mt-1.5">
      <div class="flex items-center gap-2 min-w-0">
        <div class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black shrink-0"
          :class="match.winner_id === match.player2_id
            ? 'bg-green-500/20 text-green-400 ring-1 ring-green-500/40'
            : match.winner_id && match.winner_id !== match.player2_id
              ? 'bg-red-500/10 text-slate-500'
              : 'bg-white/10 text-slate-300'"
        >
          {{ match.player2?.username?.charAt(0)?.toUpperCase() || '?' }}
        </div>
        <span class="text-xs font-bold truncate"
          :class="match.winner_id === match.player2_id ? 'text-green-400' : match.player2 ? 'text-slate-200' : 'text-slate-500'">
          {{ match.player2?.username || 'TBD' }}
        </span>
        <span v-if="match.winner_id === match.player2_id" class="text-[9px] text-green-400 font-black shrink-0">✓ W</span>
      </div>
      <span v-if="match.score_player2 !== null && match.score_player2 !== undefined" class="text-xs font-black text-white shrink-0">
        {{ match.score_player2 }}
      </span>
    </div>

    <!-- Grand Final crown -->
    <div v-if="isFinal" class="mt-2 text-center">
      <span class="text-[9px] font-black text-yellow-400 uppercase tracking-wider">🏆 Grand Final</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  match: {
    type: Object,
    required: true
  },
  currencySymbol: {
    type: String,
    default: 'Rs.'
  },
  isFinal: {
    type: Boolean,
    default: false
  },
  bracketType: {
    type: String,
    default: 'upper'
  }
})
</script>
