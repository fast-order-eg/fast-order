// FastOrder Service Worker - Push Notifications
self.addEventListener('install', function() { self.skipWaiting(); });
self.addEventListener('activate', function(e) { e.waitUntil(clients.claim()); });

self.addEventListener('push', function(event) {
    if (!event.data) return;
    var data;
    try { data = event.data.json(); }
    catch(err) { data = { title: 'FastOrder', body: event.data.text(), url: '/admin/orders' }; }

    var title = data.title || 'FastOrder';
    var options = {
        body: data.body || '',
        icon: data.icon || '/images/notification-icon.png',
        badge: data.badge || '/images/notification-badge.png',
        tag: 'fo-' + (data.data && data.data.order_id ? data.data.order_id : Date.now()),
        renotify: true,
        requireInteraction: false,
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/admin/orders',
            order_id: data.data ? data.data.order_id : null,
            type: data.data ? data.data.type : 'general'
        }
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    var targetUrl = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url : '/admin/orders';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(wins) {
            for (var i = 0; i < wins.length; i++) {
                var w = wins[i];
                if (w.url.indexOf('/admin') !== -1 && 'focus' in w) {
                    w.focus();
                    w.navigate(targetUrl);
                    return;
                }
            }
            if (clients.openWindow) return clients.openWindow(targetUrl);
        })
    );
});