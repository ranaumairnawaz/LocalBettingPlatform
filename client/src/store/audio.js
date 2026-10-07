import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useAudioStore = defineStore('audio', () => {
  let bgm = null
  let winSound = null
  let lostSound = null
  let clickSound = null
  let depositSound = null

  // State
  const bgmEnabled = ref(localStorage.getItem('bgmEnabled') !== 'false')
  const allSoundEnabled = ref(localStorage.getItem('allSoundEnabled') !== 'false')
  const globalVolume = ref(parseFloat(localStorage.getItem('globalVolume') || '1.0'))

  watch(bgmEnabled, (val) => {
    localStorage.setItem('bgmEnabled', val)
    if (!val) stopBGM()
    else playBGM() // will only play if not on game page etc, handled by caller or we just let it be
  })

  watch(allSoundEnabled, (val) => {
    localStorage.setItem('allSoundEnabled', val)
    if (!val) stopBGM()
    else if (bgmEnabled.value) playBGM()
  })

  watch(globalVolume, (val) => {
    localStorage.setItem('globalVolume', val)
    if (bgm) bgm.volume = val * 0.5
  })

  if (typeof window !== 'undefined') {
    bgm = new Audio('/assets/sounds/bgm.mp3')
    bgm.loop = true
    bgm.volume = globalVolume.value * 0.5
    
    winSound = new Audio('/assets/sounds/win.mp3')
    lostSound = new Audio('/assets/sounds/lost.mp3')
    clickSound = new Audio('/assets/sounds/click.mp3')
    depositSound = new Audio('/assets/sounds/deposit.mp3')
  }

  let bgmShouldPlay = false

  const playBGM = () => {
    bgmShouldPlay = true
    if (bgm && bgmEnabled.value && allSoundEnabled.value) {
      bgm.volume = globalVolume.value * 0.5
      bgm.play().catch(e => console.log('BGM play failed:', e))
    }
  }

  const stopBGM = () => {
    bgmShouldPlay = false
    if (bgm) {
      bgm.pause()
      // Do not reset currentTime so it pauses gracefully
    }
  }

  // Auto-resume BGM if settings change while it should be playing
  watch([bgmEnabled, allSoundEnabled], ([bgmVal, allVal]) => {
    if (bgmShouldPlay && bgmVal && allVal) {
      bgm.volume = globalVolume.value * 0.5
      bgm.play().catch(e => console.log('BGM play failed:', e))
    }
  })

  const playClick = () => {
    if (clickSound && allSoundEnabled.value) {
      const clone = clickSound.cloneNode()
      clone.volume = globalVolume.value
      clone.play().catch(e => console.log('Click play failed:', e))
    }
  }

  const playWin = () => {
    if (winSound && allSoundEnabled.value) {
      winSound.volume = globalVolume.value
      winSound.currentTime = 0
      winSound.play().catch(e => console.log('Win play failed:', e))
    }
  }

  const playLost = () => {
    if (lostSound && allSoundEnabled.value) {
      lostSound.volume = globalVolume.value
      lostSound.currentTime = 0
      lostSound.play().catch(e => console.log('Lost play failed:', e))
    }
  }

  const playDeposit = () => {
    if (depositSound && allSoundEnabled.value) {
      depositSound.volume = globalVolume.value
      depositSound.currentTime = 0
      depositSound.play().catch(e => console.log('Deposit play failed:', e))
    }
  }

  return {
    bgmEnabled,
    allSoundEnabled,
    globalVolume,
    playBGM,
    stopBGM,
    playClick,
    playWin,
    playLost,
    playDeposit
  }
})
