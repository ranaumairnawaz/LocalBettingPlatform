import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    balance: (state) => state.user?.balance || 0
  },

  actions: {
    async login(credentials) {
      this.loading = true
      try {
        const { data } = await axios.post('/api/login', credentials)
        this.token = data.token
        this.user = data.user
        localStorage.setItem('token', this.token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        return true
      } catch (e) {
        this.error = e.response?.data?.message || 'Login failed'
        return false
      } finally {
        this.loading = false
      }
    },

    async register(userData) {
      this.loading = true
      try {
        const { data } = await axios.post('/api/register', userData)
        this.token = data.token
        this.user = data.user
        localStorage.setItem('token', this.token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        return true
      } catch (e) {
        this.error = e.response?.data?.message || 'Registration failed'
        return false
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await axios.post('/api/logout')
      } catch (e) {
        console.error('Logout error', e)
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('token')
        delete axios.defaults.headers.common['Authorization']
      }
    },

    async fetchUser() {
      if (!this.token) return
      try {
        const { data } = await axios.get('/api/user')
        this.user = data
      } catch (e) {
        this.logout()
      }
    }
  }
})
