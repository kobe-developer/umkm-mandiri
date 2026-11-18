/**
 * Jaga data tetap segar dengan WebSocket (Reverb) sebagai jalur utama:
 * - selama WebSocket tersambung → TANPA polling, event memicu `reload`;
 * - WebSocket belum tersambung, terputus, atau tidak dikonfigurasi → polling cadangan;
 * - tersambung kembali setelah terputus → muat ulang sekali untuk mengejar event yang terlewat;
 * - tab kembali dibuka → muat ulang sekali.
 *
 * `subscribe(echo, onEvent)` memasang listener channel dan mengembalikan fungsi untuk melepasnya.
 * Mengembalikan fungsi untuk berhenti (dipakai sebagai cleanup $effect/onMount).
 */
export function keepFresh({ realtime = false, subscribe, intervalMs, reload }) {
    const refresh = () => {
        if (document.visibilityState === 'visible') reload();
    };

    let timer = null;
    const startPolling = () => {
        timer ??= setInterval(refresh, intervalMs);
    };
    const stopPolling = () => {
        clearInterval(timer);
        timer = null;
    };

    let isConnected = false;
    let hasConnectedBefore = false;
    const handleStatus = (status) => {
        const connected = status === 'connected';
        if (connected === isConnected) return; // status yang sama bisa dilaporkan berkali-kali
        isConnected = connected;
        if (!connected) {
            startPolling();
            return;
        }
        stopPolling();
        if (hasConnectedBefore) refresh();
        hasConnectedBefore = true;
    };

    startPolling(); // sampai WebSocket terbukti tersambung
    document.addEventListener('visibilitychange', refresh);
    const stopRealtime = realtime ? connectRealtime({ subscribe, onEvent: refresh, onStatus: handleStatus }) : () => {};

    return () => {
        stopPolling();
        document.removeEventListener('visibilitychange', refresh);
        stopRealtime();
    };
}

/** Status pesanan pelanggan: dengarkan `.order.updated` di channel publik tiap order. */
export function keepOrdersFresh({ channels = [], realtime = false, intervalMs, reload }) {
    return keepFresh({
        realtime: realtime && channels.length > 0,
        intervalMs,
        reload,
        subscribe: (echo, onEvent) => {
            channels.forEach((name) => echo.channel(name).listen('.order.updated', onEvent));
            return () => channels.forEach((name) => echo.leave(name));
        },
    });
}

/** Echo dimuat terpisah agar bundle menu publik tetap ringan. */
function connectRealtime({ subscribe, onEvent, onStatus }) {
    let stopped = false;
    let cleanups = [];

    import('../echo.js')
        .then(({ getEcho }) => {
            if (stopped) return;
            const echo = getEcho();
            if (!echo) return; // belum dikonfigurasi → tetap polling
            cleanups = [subscribe(echo, onEvent), echo.connector.onConnectionChange(onStatus)];
            onStatus(echo.connectionStatus());
        })
        .catch((error) => console.error('Gagal memuat WebSocket, memakai polling.', error));

    return () => {
        stopped = true;
        cleanups.forEach((cleanup) => cleanup());
    };
}
