/*
 * Service worker UMKM Mandiri: hanya untuk web push (tanpa cache offline).
 * Payload dari server: { title, body, icon, badge, tag, renotify, lang, requireInteraction, data: { url } }
 */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch {
        payload = { body: event.data ? event.data.text() : '' };
    }
    const { title = 'UMKM Mandiri', ...options } = payload;
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url ?? '/', self.location.origin);
    // Hanya buka halaman di situs ini sendiri
    if (target.origin !== self.location.origin) return;

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const exact = windows.find((client) => client.url === target.href);
        if (exact) return exact.focus();
        const sameSite = windows.find((client) => new URL(client.url).origin === target.origin && 'navigate' in client);
        if (sameSite) {
            const client = await sameSite.navigate(target.href);
            return client?.focus();
        }
        return self.clients.openWindow(target.href);
    })());
});
