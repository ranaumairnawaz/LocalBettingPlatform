<template>
  <Transition
    enter-active-class="transform ease-out duration-300 transition"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="toast.visible" class="fixed top-6 left-1/2 -translate-x-1/2 z-[100] w-full max-w-xs px-4">
      <div :class="bgClass" class="rounded-2xl shadow-2xl p-4 flex items-center gap-3 border border-white/10 backdrop-blur-xl">
        <div :class="iconBgClass" class="p-2 rounded-xl">
          <CheckCircle v-if="toast.type === 'success'" size="18" />
          <XCircle v-else-if="toast.type === 'error'" size="18" />
          <Info v-else size="18" />
        </div>
        <p class="text-sm font-bold text-white">{{ toast.message }}</p>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'
import { CheckCircle, XCircle, Info } from 'lucide-vue-next'
import { useToastStore } from '../store/toast'

const toast = useToastStore()

const bgClass = computed(() => {
  if (toast.type === 'success') return 'bg-green-600/90'
  if (toast.type === 'error') return 'bg-red-600/90'
  return 'bg-zinc-800/90'
})

const iconBgClass = computed(() => {
  if (toast.type === 'success') return 'bg-white/20 text-white'
  if (toast.type === 'error') return 'bg-white/20 text-white'
  return 'bg-primary/20 text-primary'
})
</script>
