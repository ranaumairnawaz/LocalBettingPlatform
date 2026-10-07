<template>
  <div class="mb-4">
    <h5 class="text-[10px] font-black uppercase tracking-wider mb-2 flex items-center gap-2"
      :class="bracketType === 'lower' ? 'text-purple-400' : 'text-slate-400'"
    >
      <span class="w-5 h-px" :class="bracketType === 'lower' ? 'bg-purple-400' : 'bg-slate-600'"></span>
      Round {{ roundNumber }}
      <span class="flex-1 h-px" :class="bracketType === 'lower' ? 'bg-purple-400/20' : 'bg-slate-700'"></span>
    </h5>
    <div class="grid gap-2" :class="round.length > 2 ? 'grid-cols-2' : 'grid-cols-1'">
      <MatchCard
        v-for="match in round"
        :key="match.id"
        :match="match"
        :currency-symbol="currencySymbol"
        :bracket-type="bracketType"
      />
    </div>
  </div>
</template>

<script setup>
import MatchCard from './MatchCard.vue'

defineProps({
  round: {
    type: Array,
    required: true
  },
  roundNumber: {
    type: Number,
    required: true
  },
  currencySymbol: {
    type: String,
    default: 'Rs.'
  },
  bracketType: {
    type: String,
    default: 'upper'
  }
})
</script>
