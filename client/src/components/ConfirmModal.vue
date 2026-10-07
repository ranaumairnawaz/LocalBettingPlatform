<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="visible"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-6"
        @click.self="onCancel"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/75 backdrop-blur-sm"></div>

        <!-- Panel -->
        <div class="relative w-full max-w-sm bg-[#13151f] border border-white/10 rounded-[2rem] shadow-2xl overflow-hidden animate-modal-in">
          <!-- Top accent line -->
          <div class="h-1 w-full" :style="{ background: `linear-gradient(90deg, ${accentColor}, ${accentColorEnd})` }"></div>

          <div class="p-7 flex flex-col gap-5">
            <!-- Icon + Title -->
            <div class="flex flex-col items-center gap-3 text-center">
              <div
                class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner"
                :style="{ backgroundColor: `${accentColor}18`, border: `1px solid ${accentColor}30` }"
              >
                <svg v-if="type === 'danger'" class="w-7 h-7" :style="{ color: accentColor }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <svg v-else class="w-7 h-7" :style="{ color: accentColor }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
              </div>
              <h2 class="text-lg font-black uppercase tracking-wider text-white">{{ title }}</h2>
              <p class="text-sm text-zinc-400 font-medium leading-relaxed">{{ message }}</p>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-2 gap-3 mt-1">
              <button
                @click="onCancel"
                class="py-3 rounded-xl font-black text-xs uppercase tracking-wider border border-white/10 bg-white/5 text-zinc-400 hover:bg-white/10 hover:text-white active:scale-95 transition-all"
              >
                {{ cancelLabel }}
              </button>
              <button
                @click="onConfirm"
                class="py-3 rounded-xl font-black text-xs uppercase tracking-wider text-white active:scale-95 transition-all shadow-lg"
                :style="{
                  background: `linear-gradient(135deg, ${accentColor}, ${accentColorEnd})`,
                  boxShadow: `0 0 20px ${accentColor}40`
                }"
              >
                {{ confirmLabel }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  visible:      { type: Boolean, default: false },
  title:        { type: String,  default: 'Are you sure?' },
  message:      { type: String,  default: 'This action cannot be undone.' },
  confirmLabel: { type: String,  default: 'Confirm' },
  cancelLabel:  { type: String,  default: 'Cancel' },
  type:         { type: String,  default: 'danger' },   // 'danger' | 'info'
  accentColor:  { type: String,  default: '#ef4444' },  // rose-500
  accentColorEnd: { type: String, default: '#f97316' }, // orange-500
})

const emit = defineEmits(['confirm', 'cancel'])

const onConfirm = () => emit('confirm')
const onCancel  = () => emit('cancel')
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

@keyframes modal-in {
  from { opacity: 0; transform: scale(0.93) translateY(12px); }
  to   { opacity: 1; transform: scale(1)    translateY(0);    }
}
.animate-modal-in {
  animation: modal-in 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
