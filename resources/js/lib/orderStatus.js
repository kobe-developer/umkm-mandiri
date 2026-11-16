/** Langkah status order online yang dilihat pelanggan: Baru → Dikonfirmasi → Diproses → Siap/Diantar → Selesai. */
export const ORDER_STEPS = [
    { value: 'baru', label: 'Pesanan diterima', hint: 'Menunggu konfirmasi kasir' },
    { value: 'dikonfirmasi', label: 'Dikonfirmasi', hint: 'Pesanan sudah dicek kasir' },
    { value: 'diproses', label: 'Diproses', hint: 'Sedang disiapkan di dapur' },
    { value: 'siap', label: 'Siap / Diantar', hint: 'Siap diambil atau sedang diantar' },
    { value: 'selesai', label: 'Selesai', hint: 'Selamat menikmati!' },
];

export const FINAL_STATUSES = ['selesai', 'dibatalkan'];

export const isFinalStatus = (status) => FINAL_STATUSES.includes(status);

export const stepIndex = (status) => ORDER_STEPS.findIndex((step) => step.value === status);

/** Kalimat status saat ini untuk pelanggan, menyesuaikan ambil sendiri / diantar. */
export function statusHint(status, fulfillment) {
    if (status === 'dibatalkan') return 'Pesanan dibatalkan';
    if (status === 'siap') return fulfillment === 'delivery' ? 'Pesanan sedang diantar' : 'Pesanan siap diambil';
    return ORDER_STEPS[stepIndex(status)]?.hint ?? '';
}
