<script>
    /** Badge status order / pembayaran. */
    let { status, label = null, kind = 'order' } = $props();

    const ORDER = {
        baru: 'bg-amber-100 text-amber-800 ring-amber-200',
        dikonfirmasi: 'bg-sky-100 text-sky-800 ring-sky-200',
        diproses: 'bg-indigo-100 text-indigo-800 ring-indigo-200',
        siap: 'bg-violet-100 text-violet-800 ring-violet-200',
        selesai: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        dibatalkan: 'bg-stone-200 text-stone-600 ring-stone-300',
    };
    const PAYMENT = {
        belum_bayar: 'bg-orange-100 text-orange-800 ring-orange-200',
        lunas: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        void: 'bg-red-100 text-red-700 ring-red-200',
    };
    const LABELS = {
        baru: 'Baru', dikonfirmasi: 'Dikonfirmasi', diproses: 'Diproses', siap: 'Siap/Diantar',
        selesai: 'Selesai', dibatalkan: 'Dibatalkan', belum_bayar: 'Belum Bayar', lunas: 'Lunas', void: 'Void',
    };

    const classes = $derived((kind === 'payment' ? PAYMENT : ORDER)[status] ?? 'bg-stone-100 text-stone-700 ring-stone-200');
</script>

<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset whitespace-nowrap {classes}">
    {label ?? LABELS[status] ?? status}
</span>
