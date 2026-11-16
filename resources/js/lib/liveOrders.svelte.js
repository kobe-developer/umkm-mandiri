import { page, router } from '@inertiajs/svelte';
import { keepOrdersFresh } from './realtime.js';

/** Polling cadangan bila WebSocket tidak tersedia. */
const FALLBACK_REFRESH_MS = 15000;

/**
 * Perbarui prop daftar order secara realtime selama masih ada order yang berjalan.
 * Panggil saat inisialisasi komponen. `getOrders` mengembalikan ringkasan order
 * (punya `is_final` & `realtime_channel`), `prop` = nama prop Inertia yang dimuat ulang.
 */
export function liveOrderUpdates(getOrders, prop) {
    // String (bukan array) agar langganan WebSocket tidak diulang di setiap reload bila isinya sama
    const channels = $derived(
        getOrders()
            .filter((order) => !order.is_final && order.realtime_channel)
            .map((order) => order.realtime_channel)
            .join(','),
    );
    const realtime = $derived(Boolean(page.props.config?.realtime));

    $effect(() => {
        if (!channels) return;
        return keepOrdersFresh({
            channels: channels.split(','),
            realtime,
            intervalMs: FALLBACK_REFRESH_MS,
            reload: () => router.reload({ only: [prop], preserveScroll: true, preserveState: true, showProgress: false }),
        });
    });
}
