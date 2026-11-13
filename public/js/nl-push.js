/* ================================================================
   NextLog — WEB PUSH + BADGE IKON APLIKASI
   - Selalu aktif: perangkat otomatis didaftarkan saat user login (tanpa tombol on/off).
     Browser tetap mewajibkan SATU kali tap untuk izin notifikasi (aturan iPhone/Firefox),
     jadi kalau izin belum pernah diberikan muncul kartu "Aktifkan notifikasi".
   - Badge angka di ikon PWA = jumlah notifikasi belum dibaca.
   - Tidak ada panel lonceng: notifikasi dianggap dibaca saat halaman tujuannya dibuka,
     saat notif di HP diklik, atau saat tugasnya sudah diproses.
   ================================================================ */
(function () {
    if (window.__nlPushInit) return;
    window.__nlPushInit = true;

    const cfg = window.nlPushConfig || {};
    const meta = (n) => document.querySelector(`meta[name="${n}"]`)?.content || '';
    const userId = () => meta('nl-user-id');
    const pushSupported = () => 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    /* ── Service worker (network-only; tidak meng-cache halaman) ── */
    if ('serviceWorker' in navigator && cfg.sw) {
        navigator.serviceWorker.register(cfg.sw).catch((err) => console.warn('[NextLog push] SW gagal didaftarkan', err));
    }

    const syncAppBadge = (n) => {
        try {
            if (!('setAppBadge' in navigator)) return;
            n > 0 ? navigator.setAppBadge(n).catch(() => {}) : navigator.clearAppBadge().catch(() => {});
        } catch (_) {}
    };

    /* ── Sumber tunggal angka unread: meta + badge ikon PWA ── */
    const setUnread = (n) => {
        n = Math.max(0, parseInt(n, 10) || 0);
        const m = document.querySelector('meta[name="nl-unread-count"]');
        if (m) m.content = String(n);
        syncAppBadge(n);
        document.dispatchEvent(new CustomEvent('nl:unread', { detail: { count: n } }));
    };
    window.nlSetUnread = setUnread;

    const urlB64ToUint8 = (b64) => {
        const pad = '='.repeat((4 - (b64.length % 4)) % 4);
        const raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
        return Uint8Array.from(raw, (ch) => ch.charCodeAt(0));
    };

    const post = (url, body) =>
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': meta('csrf-token'),
            },
            body: JSON.stringify(body),
        });

    // Batasi waktu tunggu: jangan sampai UI menggantung bila SW belum siap / prompt tertahan
    const withTimeout = (promise, ms, msg) =>
        Promise.race([promise, new Promise((_, rej) => setTimeout(() => rej(new Error(msg || 'timeout')), ms))]);

    const swReady = () => withTimeout(navigator.serviceWorker.ready, 8000, 'Service worker belum siap');

    // Daftarkan perangkat ini ke akun yang login (izin harus sudah 'granted', tanpa popup)
    const registerDevice = async () => {
        const reg = await swReady();
        let sub = await reg.pushManager.getSubscription();
        if (!sub) {
            sub = await withTimeout(reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlB64ToUint8(meta('nl-push-key')),
            }), 15000, 'Gagal mendaftar push');
        }
        const json = sub.toJSON();
        const enc = (PushManager.supportedContentEncodings || ['aes128gcm'])[0];
        const res = await post(cfg.subscribe, { endpoint: json.endpoint, keys: json.keys, contentEncoding: enc });
        return res.ok;
    };

    // Dipanggil langsung dari klik (wajib di iPhone supaya popup izin boleh muncul)
    const askPermissionAndRegister = async () => {
        const perm = await withTimeout(Notification.requestPermission(), 60000, 'Izin tidak dijawab');
        if (perm !== 'granted') return perm;
        await registerDevice();
        return perm;
    };

    /* ── Kartu ajakan (hanya bila izin belum diberikan / diblokir) ── */
    const promptKey = 'nl-push-prompt-dismissed';
    const promptDismissed = () => { try { return sessionStorage.getItem(promptKey) === userId(); } catch (_) { return false; } };
    const dismissPrompt = () => { try { sessionStorage.setItem(promptKey, userId()); } catch (_) {} };

    const showPrompt = (blocked) => {
        if (document.getElementById('nl-push-prompt') || promptDismissed()) return;
        const card = document.createElement('div');
        card.id = 'nl-push-prompt';
        card.className = 'nl-push-prompt' + (blocked ? ' is-blocked' : '');
        card.setAttribute('role', 'dialog');
        card.setAttribute('aria-live', 'polite');
        card.innerHTML = `
            <div class="nl-push-prompt-icon"><i class="bi ${blocked ? 'bi-bell-slash-fill' : 'bi-bell-fill'}" aria-hidden="true"></i></div>
            <div class="nl-push-prompt-body">
                <strong>${blocked ? 'Notifikasi diblokir' : 'Aktifkan notifikasi'}</strong>
                <p>${blocked
                    ? 'Izinkan notifikasi untuk NextLog lewat pengaturan HP / browser agar tidak ketinggalan info approval dan stok.'
                    : 'Terima pemberitahuan approval, permintaan barang, dan stok rendah langsung di perangkat ini.'}</p>
                <div class="nl-push-prompt-actions">
                    ${blocked ? '' : '<button type="button" class="nl-push-prompt-ok">Aktifkan</button>'}
                    <button type="button" class="nl-push-prompt-later">${blocked ? 'Mengerti' : 'Nanti'}</button>
                </div>
            </div>`;
        const close = () => { dismissPrompt(); card.classList.remove('is-show'); setTimeout(() => card.remove(), 250); };
        card.querySelector('.nl-push-prompt-later').addEventListener('click', close);
        const ok = card.querySelector('.nl-push-prompt-ok');
        if (ok) ok.addEventListener('click', () => {
            ok.disabled = true;
            ok.textContent = 'Memproses…';
            askPermissionAndRegister()
                .then((perm) => {
                    if (perm === 'denied') {
                        card.remove();
                        try { sessionStorage.removeItem(promptKey); } catch (_) {}
                        showPrompt(true);
                    } else {
                        close();
                    }
                })
                .catch((err) => { console.warn('[NextLog push]', err); close(); });
        });
        document.body.appendChild(card);
        requestAnimationFrame(() => card.classList.add('is-show'));
    };

    /* ── Saat halaman dimuat: pastikan perangkat terdaftar untuk akun ini ── */
    const init = async () => {
        const unread = parseInt(meta('nl-unread-count'), 10);
        if (!Number.isNaN(unread)) setUnread(unread);

        if (!pushSupported() || !meta('nl-push-key') || !cfg.subscribe) return;

        if (Notification.permission === 'denied') return showPrompt(true);
        if (Notification.permission !== 'granted') return showPrompt(false);

        try {
            const reg = await swReady();
            const sub = await reg.pushManager.getSubscription();
            let registered = false;
            if (sub) {
                // Endpoint milik akun lain di perangkat ini → server menghapusnya, lalu didaftarkan untuk akun ini
                const res = await post(cfg.check, { endpoint: sub.endpoint });
                registered = res.ok && (await res.json()).subscribed;
            }
            // Izin sudah ada tapi perangkat belum terdaftar untuk akun ini → daftarkan tanpa popup
            if (!registered) await registerDevice();
        } catch (err) {
            console.warn('[NextLog push]', err);
        }
    };

    if (document.readyState !== 'loading') init();
    else document.addEventListener('DOMContentLoaded', init);

    /* ── Tutup notifikasi HP yang masih di tray untuk notif yang sudah dibaca ── */
    const closeTray = async (ids) => {
        try {
            if (!('serviceWorker' in navigator) || !ids || !ids.length) return;
            const reg = await swReady();
            const list = await reg.getNotifications();
            list.forEach((nt) => {
                if (ids.includes(String(nt.tag || '').replace(/^nl-/, ''))) nt.close();
            });
        } catch (_) {}
    };

    /* ── Halaman tujuan dibuka → notifikasi untuk halaman itu dianggap dibaca ── */
    let lastVisited = '';
    const markVisited = async (href) => {
        if (!cfg.readPath || !userId() || !href) return;
        let path;
        try { path = new URL(href, window.location.href).pathname; } catch (_) { return; }
        if (path === lastVisited) return;
        lastVisited = path;
        try {
            const res = await post(cfg.readPath, { href: path });
            if (!res.ok) return;
            const j = await res.json();
            if (typeof j.count === 'number') setUnread(j.count);
            closeTray(j.ids || []);
        } catch (_) {}
    };
    window.nlMarkVisited = markVisited;

    markVisited(window.location.href);
    // Navigasi sidebar NextLog memakai switchSection (tanpa reload) → dengarkan kliknya
    document.addEventListener('click', (e) => {
        const a = e.target.closest && e.target.closest('a.sidebar-link[href]');
        if (a) setTimeout(() => markVisited(a.href), 0);
    }, true);
    window.addEventListener('popstate', () => markVisited(window.location.href));

    /* ── Ambil angka terbaru dari server (mis. tugas diproses di perangkat lain) ── */
    let lastSync = 0;
    const refreshUnread = async () => {
        if (!cfg.badge || !userId() || Date.now() - lastSync < 5000) return;
        lastSync = Date.now();
        try {
            const res = await fetch(cfg.badge, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const j = await res.json();
            if (typeof j.count === 'number') setUnread(j.count);
        } catch (_) {}
    };
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refreshUnread();
    });
    window.addEventListener('pageshow', (e) => { if (e.persisted) refreshUnread(); });

    // Push masuk saat halaman terbuka → service worker mengirim angka terbaru
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (e) => {
            if (e.data && e.data.type === 'nl-unread') {
                setUnread(e.data.count);
                lastVisited = ''; // klik menu yang sama sesudah ini ikut menandai notif baru sebagai dibaca
            }
        });
    }
})();
