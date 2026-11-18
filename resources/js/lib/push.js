/**
 * Web push di browser: registrasi service worker, izin notifikasi, dan langganan.
 * Kunci publik VAPID datang dari props Inertia `config.vapidPublicKey` (null = fitur nonaktif).
 */
const SW_URL = '/sw.js';

export function isIos() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

export function isStandalone() {
    return window.matchMedia?.('(display-mode: standalone)').matches || navigator.standalone === true;
}

/**
 * Status dukungan perangkat:
 * - 'ready'          bisa diaktifkan (izin belum ditolak)
 * - 'granted'        izin sudah diberikan
 * - 'denied'         izin ditolak; harus diubah dari pengaturan browser
 * - 'needs-install'  iPhone/iPad: pasang ke Layar Utama dulu (iOS 16.4+)
 * - 'unsupported'    browser tidak mendukung / server belum dikonfigurasi
 */
export function pushSupport(vapidKey) {
    if (typeof window === 'undefined' || !vapidKey) return 'unsupported';
    const hasApis = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    if (!hasApis) return isIos() && !isStandalone() ? 'needs-install' : 'unsupported';
    if (Notification.permission === 'denied') return 'denied';
    return Notification.permission === 'granted' ? 'granted' : 'ready';
}

export function registerServiceWorker() {
    if (typeof navigator === 'undefined' || !('serviceWorker' in navigator)) return Promise.resolve(null);
    return navigator.serviceWorker.register(SW_URL).catch(() => null);
}

function base64UrlToBytes(base64) {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(padded), (char) => char.charCodeAt(0));
}

function sameKey(buffer, bytes) {
    if (!buffer) return false;
    const current = new Uint8Array(buffer);
    return current.length === bytes.length && current.every((value, index) => value === bytes[index]);
}

function serialize(subscription) {
    const json = subscription.toJSON();
    const encodings = window.PushManager?.supportedContentEncodings ?? ['aesgcm'];
    return {
        endpoint: json.endpoint,
        keys: { p256dh: json.keys?.p256dh, auth: json.keys?.auth },
        content_encoding: encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm',
    };
}

async function readyRegistration() {
    await registerServiceWorker();
    return navigator.serviceWorker.ready;
}

/** Langganan yang sudah ada di perangkat ini (tanpa meminta izin), atau null. */
export async function currentSubscription() {
    if (!('serviceWorker' in navigator) || Notification.permission !== 'granted') return null;
    const registration = await readyRegistration();
    const subscription = await registration.pushManager.getSubscription();
    return subscription ? serialize(subscription) : null;
}

/**
 * Minta izin lalu berlangganan. WAJIB dipanggil langsung dari handler klik/submit
 * (Safari menolak permintaan izin di luar gestur pengguna).
 * @returns {Promise<object|null>} objek langganan siap dikirim ke server, atau null bila izin tidak diberikan
 */
export async function subscribePush(vapidKey) {
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return null;

    const registration = await readyRegistration();
    const applicationServerKey = base64UrlToBytes(vapidKey);
    let subscription = await registration.pushManager.getSubscription();

    // Kunci VAPID server berganti → langganan lama tidak bisa dipakai lagi
    if (subscription && !sameKey(subscription.options?.applicationServerKey, applicationServerKey)) {
        await subscription.unsubscribe();
        subscription = null;
    }

    subscription ??= await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey });
    return serialize(subscription);
}

/** Berhenti berlangganan di perangkat ini; mengembalikan endpoint yang dilepas (untuk dihapus di server). */
export async function unsubscribePush() {
    if (!('serviceWorker' in navigator)) return null;
    const registration = await navigator.serviceWorker.getRegistration();
    const subscription = await registration?.pushManager.getSubscription();
    if (!subscription) return null;
    const { endpoint } = subscription;
    await subscription.unsubscribe();
    return endpoint;
}

function xsrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

/** Kirim/hapus langganan perangkat staf di server. */
export async function syncStaffSubscription(method, body) {
    const response = await fetch('/push-subscriptions', {
        method,
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
        body: JSON.stringify(body),
    });
    if (!response.ok) throw new Error(`Gagal menyimpan langganan notifikasi (${response.status})`);
    return response.json();
}
