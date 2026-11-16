<script>
    /** Badge status order / pembayaran: titik warna + label, nada lembut. */
    let { status, label = null, kind = 'order' } = $props();

    const TONES = {
        attention: 'bg-amber-soft text-amber-ink [--dot:#c98a1b]',
        progress: 'bg-paper-deep text-ink-soft [--dot:#3d372e]',
        positive: 'bg-leaf-50 text-leaf-700 [--dot:#4d7a46]',
        negative: 'bg-brand-50 text-brand-700 [--dot:#b93a1a]',
        neutral: 'bg-paper-deep text-muted [--dot:#a69d90]',
    };
    const ORDER = { baru: 'attention', dikonfirmasi: 'progress', diproses: 'progress', siap: 'progress', selesai: 'positive', dibatalkan: 'neutral' };
    const PAYMENT = { belum_bayar: 'attention', lunas: 'positive', void: 'negative' };
    const LABELS = {
        baru: 'Baru', dikonfirmasi: 'Dikonfirmasi', diproses: 'Diproses', siap: 'Siap/Diantar',
        selesai: 'Selesai', dibatalkan: 'Dibatalkan', belum_bayar: 'Belum Bayar', lunas: 'Lunas', void: 'Void',
    };

    const tone = $derived(TONES[(kind === 'payment' ? PAYMENT : ORDER)[status] ?? 'neutral']);
    const isLive = $derived(kind === 'order' && status === 'baru');
</script>

<span class="inline-flex items-center gap-1.5 rounded-full py-0.5 pr-2.5 pl-2 text-xs font-semibold whitespace-nowrap {tone}">
    <span class="relative flex size-1.5">
        {#if isLive}<span class="absolute inline-flex size-full animate-ping rounded-full bg-(--dot) opacity-60"></span>{/if}
        <span class="relative inline-flex size-1.5 rounded-full bg-(--dot)"></span>
    </span>
    {label ?? LABELS[status] ?? status}
</span>
