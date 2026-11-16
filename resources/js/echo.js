import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Koneksi WebSocket ke Laravel Reverb. Mengembalikan null bila belum dikonfigurasi;
 * aplikasi tetap jalan memakai polling sebagai cadangan.
 */
let instance;

export function getEcho() {
    if (instance !== undefined) return instance;

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    if (!key) {
        instance = null;
        return instance;
    }

    try {
        window.Pusher = Pusher;
        const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
        instance = new Echo({
            broadcaster: 'reverb',
            key,
            wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } catch (error) {
        console.error('Gagal menghubungkan WebSocket, memakai polling.', error);
        instance = null;
    }
    return instance;
}
