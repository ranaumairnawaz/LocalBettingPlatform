import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', {
  state: () => ({
    message: null,
    type: 'success', // success, error, info
    visible: false
  }),

  actions: {
    show(message, type = 'success') {
      this.message = message
      this.type = type
      this.visible = true

      setTimeout(() => {
        this.visible = false
      }, 3000)
    }
  }
})
