import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import './index.css'

axios.defaults.baseURL = import.meta.env.VITE_API_URL || (import.meta.env.DEV ? 'http://localhost:8000' : window.location.origin)
const token = localStorage.getItem('token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
