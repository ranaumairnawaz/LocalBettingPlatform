import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const isHttps = window.location.protocol === 'https:'
const defaultPort = isHttps ? 443 : 8080

export const initEcho = (token) => {
  return new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'cn16du97atbxgj3dvddw',
    wsHost: window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || defaultPort,
    wssPort: import.meta.env.VITE_REVERB_PORT || defaultPort,
    forceTLS: isHttps,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${token}`
      }
    }
  })
}
