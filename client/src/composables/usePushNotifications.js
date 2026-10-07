// composables/usePushNotifications.js
import { ref } from 'vue'
import axios from 'axios'

export function usePushNotifications() {
  const isSupported = ref('serviceWorker' in navigator && 'PushManager' in window)
  const isSubscribed = ref(false)
  const isLoading = ref(false)
  const error = ref(null)

  async function registerServiceWorker() {
    if (!isSupported.value) return null
    return await navigator.serviceWorker.register('/sw.js')
  }

  async function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4)
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
    const rawData = window.atob(base64)
    const outputArray = new Uint8Array(rawData.length)
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i)
    }
    return outputArray
  }

  async function subscribe() {
    if (!isSupported.value) {
      error.value = 'Push notifications are not supported by your browser.'
      return
    }

    try {
      isLoading.value = true
      error.value = null

      const permission = await Notification.requestPermission()
      if (permission !== 'granted') {
        error.value = 'Notification permission denied.'
        return
      }

      const sw = await registerServiceWorker()
      const registration = await navigator.serviceWorker.ready

      // Fetch VAPID public key from API
      const { data } = await axios.get('/api/push/vapid-key')
      const convertedKey = await urlBase64ToUint8Array(data.vapid_public_key)

      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: convertedKey,
      })

      await axios.post('/api/push/subscribe', {
        endpoint: subscription.endpoint,
        keys: {
          auth: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('auth')))),
          p256dh: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('p256dh')))),
        },
      })

      isSubscribed.value = true
      localStorage.setItem('push_subscribed', '1')
    } catch (e) {
      error.value = e.message || 'Failed to subscribe to push notifications.'
      console.error('[PushNotifications] subscribe error:', e)
    } finally {
      isLoading.value = false
    }
  }

  async function unsubscribe() {
    try {
      isLoading.value = true
      const registration = await navigator.serviceWorker.ready
      const subscription = await registration.pushManager.getSubscription()
      if (subscription) {
        await axios.post('/api/push/unsubscribe', { endpoint: subscription.endpoint })
        await subscription.unsubscribe()
      }
      isSubscribed.value = false
      localStorage.removeItem('push_subscribed')
    } catch (e) {
      error.value = e.message
    } finally {
      isLoading.value = false
    }
  }

  async function checkSubscriptionStatus() {
    if (!isSupported.value) return
    try {
      const registration = await navigator.serviceWorker.ready
      const subscription = await registration.pushManager.getSubscription()
      isSubscribed.value = !!subscription
    } catch (e) {
      isSubscribed.value = false
    }
  }

  return {
    isSupported,
    isSubscribed,
    isLoading,
    error,
    subscribe,
    unsubscribe,
    checkSubscriptionStatus,
  }
}
