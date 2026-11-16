/**
 * Jaga data order pelanggan tetap segar: WebSocket (Reverb) untuk update seketika,
 * polling sebagai cadangan, dan muat ulang saat tab kembali dibuka.
 * Mengembalikan fungsi untuk berhenti (dipakai sebagai cleanup $effect).
 */
export function keepOrdersFresh({ channels = [], realtime = false, intervalMs, reload }) {
    const refresh = () => {
        if (document.visibilityState === 'visible') reload();
    };

    const timer = setInterval(refresh, intervalMs);
    document.addEventListener('visibilitychange', refresh);
    const stopListening = realtime && channels.length > 0 ? listenToOrderChannels(channels, refresh) : () => {};

    return () => {
        clearInterval(timer);
        document.removeEventListener('visibilitychange', refresh);
        stopListening();
    };
}

/** Echo dimuat terpisah agar bundle menu publik tetap ringan. */
function listenToOrderChannels(channels, onUpdate) {
    let stopped = false;
    let echo = null;

    import('../echo.js')
        .then(({ getEcho }) => {
            if (stopped) return;
            echo = getEcho();
            channels.forEach((name) => echo?.channel(name).listen('.order.updated', onUpdate));
        })
        .catch((error) => console.error('Gagal memuat WebSocket, memakai polling.', error));

    return () => {
        stopped = true;
        channels.forEach((name) => echo?.leave(name));
    };
}
