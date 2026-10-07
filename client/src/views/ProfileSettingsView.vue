<template>
  <div class="min-h-screen text-slate-100 pb-10">
    <div class="flex items-center gap-4 mb-8">
      <button @click="$router.back()" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl transition-all">
        <ChevronLeft size="20" />
      </button>
      <div>
        <h2 class="text-xs uppercase tracking-widest text-slate-400 font-bold">Profile</h2>
        <h1 class="text-2xl font-black text-white italic tracking-wide">APP SETTINGS</h1>
      </div>
    </div>

    <div class="bg-[#121623]/60 border border-white/5 p-6 md:p-8 rounded-[24px] backdrop-blur-md max-w-2xl">
      <div class="space-y-8">
        <!-- All Sound Toggle -->
        <div class="flex items-center justify-between border-b border-white/5 pb-8">
          <div>
            <h3 class="text-lg font-black text-white italic tracking-wider uppercase">Enable All Sounds</h3>
            <p class="text-xs text-slate-400 font-bold uppercase mt-1">Master switch for all application audio</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="audio.allSoundEnabled" class="sr-only peer">
            <div class="w-14 h-7 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[3px] after:left-[3px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5.5 after:w-5.5 after:transition-all peer-checked:bg-neon-cyan shadow-[0_0_10px_rgba(0,242,254,0.1)] peer-checked:shadow-[0_0_20px_rgba(0,242,254,0.4)]"></div>
          </label>
        </div>

        <!-- BGM Toggle -->
        <div class="flex items-center justify-between border-b border-white/5 pb-8">
          <div :class="{'opacity-50 transition-opacity': !audio.allSoundEnabled}">
            <h3 class="text-lg font-black text-white italic tracking-wider uppercase">Background Music</h3>
            <p class="text-xs text-slate-400 font-bold uppercase mt-1">Play BGM while navigating the app</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer" :class="{'opacity-50 pointer-events-none transition-opacity': !audio.allSoundEnabled}">
            <input type="checkbox" v-model="audio.bgmEnabled" :disabled="!audio.allSoundEnabled" class="sr-only peer">
            <div class="w-14 h-7 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[3px] after:left-[3px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5.5 after:w-5.5 after:transition-all peer-checked:bg-neon-pink shadow-[0_0_10px_rgba(255,0,127,0.1)] peer-checked:shadow-[0_0_20px_rgba(255,0,127,0.4)]"></div>
          </label>
        </div>

        <!-- Global Volume Slider -->
        <div class="pt-2">
          <div class="flex justify-between items-center mb-6" :class="{'opacity-50 transition-opacity': !audio.allSoundEnabled}">
            <div>
              <h3 class="text-lg font-black text-white italic tracking-wider uppercase">Master Volume</h3>
              <p class="text-xs text-slate-400 font-bold uppercase mt-1">Adjust sound intensity</p>
            </div>
            <span class="text-xl text-neon-cyan font-black italic">{{ Math.round(audio.globalVolume * 100) }}%</span>
          </div>
          <input 
            type="range" 
            v-model.number="audio.globalVolume" 
            min="0" 
            max="1" 
            step="0.01"
            class="w-full h-3 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-neon-cyan shadow-[0_0_15px_rgba(0,242,254,0.2)] hover:shadow-[0_0_25px_rgba(0,242,254,0.4)] transition-shadow"
            :disabled="!audio.allSoundEnabled"
            :class="{'opacity-50 pointer-events-none': !audio.allSoundEnabled}"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ChevronLeft } from 'lucide-vue-next'
import { useAudioStore } from '../store/audio'

const audio = useAudioStore()
</script>

<style scoped>
input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 24px;
  height: 24px;
  background: #00f2fe;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 0 15px rgba(0, 242, 254, 0.8);
}
</style>
