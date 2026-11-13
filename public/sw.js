/* NextLog service worker — PWA + Web Push. Network-only on purpose:
   pages are dynamic (auth, CSRF), so nothing is cached except an offline notice. */
const OFFLINE_HTML = `<!doctype html><html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>NextLog - Offline</title>
<style>html,body{height:100%;margin:0}body{display:flex;align-items:center;justify-content:center;
background:linear-gradient(160deg,#064e3b,#065f46 45%,#022c22);color:#ecfdf5;font-family:system-ui,sans-serif;text-align:center;padding:24px}
h1{font-size:1.25rem;margin:0 0 8px}p{margin:0 0 20px;opacity:.75}
button{background:#10b981;border:0;border-radius:10px;padding:12px 22px;font-weight:700;color:#fff}</style></head>
<body><div><h1>Tidak ada koneksi internet</h1><p>Periksa koneksi kamu, lalu coba lagi.</p>
<button onclick="location.reload()">Muat ulang</button></div></body></html>`;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') return;
    event.respondWith(
        fetch(event.request).catch(() =>
            new Response(OFFLINE_HTML, { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } })
        )
    );
});

/* ── Web Push: notifikasi walau aplikasi tertutup + badge angka di ikon ── */
const setBadge = (n) => {
    try {
        if (!('setAppBadge' in self.navigator)) return Promise.resolve();
        return n > 0 ? self.navigator.setAppBadge(n) : self.navigator.clearAppBadge();
    } catch (e) {
        return Promise.resolve();
    }
};

const scopeUrl = (p) => new URL(p, self.registration.scope).href;

self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { body: event.data && event.data.text() }; }

    const title = data.title || 'NextLog';
    const options = {
        body: data.body || '',
        icon: data.icon || scopeUrl('images/pwa-icon-192.png'),
        badge: data.badgeIcon || scopeUrl('images/pwa-badge-96.png'),
        tag: data.tag || undefined,
        renotify: Boolean(data.tag),
        vibrate: [120, 60, 120],
        data: { url: data.url || self.registration.scope },
    };

    const count = Number(data.badge) || 0;
    // userVisibleOnly: showNotification WAJIB dipanggil di setiap push
    event.waitUntil(Promise.all([
        self.registration.showNotification(title, options),
        setBadge(count),
        // Halaman NextLog yang sedang terbuka ikut menyamakan angka badge
        self.clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((all) => all.forEach((c) => c.postMessage({ type: 'nl-unread', count }))),
    ]));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || self.registration.scope;
    event.waitUntil((async () => {
        const all = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const client of all) {
            if (new URL(client.url).origin === new URL(target, self.location.origin).origin && 'focus' in client) {
                await client.focus();
                if ('navigate' in client) return client.navigate(target);
                return;
            }
        }
        if (self.clients.openWindow) return self.clients.openWindow(target);
    })());
});
