// 666Win Service Worker — handles WebPush notifications
self.addEventListener('push', function (event) {
  const data = event.data ? event.data.json() : {};
  const title = data.title || '🎮 666Win Notification';
  const options = {
    body: data.body || 'You have a new notification.',
    icon: data.icon || '/logo.png',
    badge: '/badge.png',
    vibrate: data.vibrate || [100],
    data: { url: (data.data && data.data.url) || '/' },
    actions: data.actions || [],
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  const url = event.notification.data && event.notification.data.url
    ? event.notification.data.url
    : '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (let i = 0; i < clientList.length; i++) {
        const client = clientList[i];
        if ('focus' in client) {
          client.focus();
          client.navigate(url);
          return;
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});
