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
        // Default: origin halaman ini (nginx mem-proxy /app ke Reverb), sehingga jalan
        // lewat http://localhost maupun https dari tunnel/proxy (ngrok, Cloudflare).
        const { hostname, port, protocol } = window.location;
        const scheme = import.meta.env.VITE_REVERB_SCHEME || protocol.replace(':', '');
        const envPort = import.meta.env.VITE_REVERB_PORT || port;
        instance = new Echo({
            broadcaster: 'reverb',
            key,
            wsHost: import.meta.env.VITE_REVERB_HOST || hostname,
            wsPort: Number(envPort || 80),
            wssPort: Number(envPort || 443),
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } catch (error) {
        console.error('Gagal menghubungkan WebSocket, memakai polling.', error);
        instance = null;
    }
    return instance;
}
