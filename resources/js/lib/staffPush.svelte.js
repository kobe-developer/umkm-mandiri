/**
 * Status web push perangkat staf (owner/kasir), dipakai bersama oleh banner di layout
 * dan kartu pengaturan di halaman Akun.
 */
import { pushSupport, currentSubscription, subscribePush, unsubscribePush, syncStaffSubscription } from './push.js';

const DISMISS_KEY = 'umkm-push-prompt-dismissed-v1';

function readDismissed() {
    try {
        return localStorage.getItem(DISMISS_KEY) === '1';
    } catch {
        return false;
    }
}

let state = $state({ support: 'unsupported', subscribed: false, busy: false, error: null, dismissed: readDismissed() });

function update(changes) {
    state = { ...state, ...changes };
}

export const staffPush = {
    get state() {
        return state;
    },

    /**
     * Cek dukungan; bila izin sudah diberikan, sinkronkan langganan perangkat ini ke akun
     * yang sedang login (mis. tablet kasir dipakai bergantian).
     */
    async init(vapidKey) {
        update({ support: pushSupport(vapidKey) });
        if (state.support !== 'granted') return;
        try {
            const subscription = await currentSubscription();
            if (subscription) await syncStaffSubscription('POST', subscription);
            update({ subscribed: subscription !== null, error: null });
        } catch (error) {
            update({ error: error.message });
        }
    },

    /** Dipanggil dari klik tombol (izin browser butuh gestur pengguna). */
    async enable(vapidKey) {
        update({ busy: true, error: null });
        try {
            const subscription = await subscribePush(vapidKey);
            if (!subscription) {
                update({ support: pushSupport(vapidKey), error: 'Izin notifikasi tidak diberikan.' });
                return false;
            }
            await syncStaffSubscription('POST', subscription);
            update({ support: 'granted', subscribed: true });
            return true;
        } catch (error) {
            update({ error: error.message || 'Gagal mengaktifkan notifikasi.' });
            return false;
        } finally {
            update({ busy: false });
        }
    },

    async disable() {
        update({ busy: true, error: null });
        try {
            const endpoint = await unsubscribePush();
            if (endpoint) await syncStaffSubscription('DELETE', { endpoint });
            update({ subscribed: false });
        } catch (error) {
            update({ error: error.message || 'Gagal menonaktifkan notifikasi.' });
        } finally {
            update({ busy: false });
        }
    },

    dismissPrompt() {
        try {
            localStorage.setItem(DISMISS_KEY, '1');
        } catch {
            // penyimpanan diblokir: banner tetap tertutup untuk sesi ini saja
        }
        update({ dismissed: true });
    },
};
