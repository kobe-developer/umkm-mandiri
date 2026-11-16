<script>
    import { Link, router } from '@inertiajs/svelte';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import ReceiptText from '@lucide/svelte/icons/receipt-text';
    import FlowHeader from '../../components/menu/FlowHeader.svelte';
    import ActiveOrderCard from '../../components/menu/ActiveOrderCard.svelte';
    import StatusBadge from '../../components/StatusBadge.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import { liveOrderUpdates } from '../../lib/liveOrders.svelte.js';
    import { rupiah, time, date } from '../../lib/format.js';

    /** "Pesanan saya": order yang sedang berjalan (realtime) + riwayat pembelian di perangkat ini. */
    let { orders = [], storeName } = $props();

    const ITEMS_PREVIEW = 3;
    const TZ = 'Asia/Jakarta';

    const active = $derived(orders.filter((o) => !o.is_final));
    const past = $derived(orders.filter((o) => o.is_final));

    /** Riwayat dikelompokkan per tanggal (WIB), urutan dari server sudah terbaru dulu. */
    const pastByDay = $derived.by(() => {
        const groups = new Map();
        for (const order of past) {
            const key = dayKey(order.created_at);
            groups.set(key, [...(groups.get(key) ?? []), order]);
        }
        return [...groups].map(([key, items]) => ({ key, label: dayLabel(key, items[0].created_at), orders: items }));
    });

    liveOrderUpdates(() => orders, 'orders');

    function dayKey(value) {
        return new Intl.DateTimeFormat('en-CA', { timeZone: TZ }).format(new Date(value));
    }

    function dayLabel(key, value) {
        const today = dayKey(Date.now());
        const yesterday = dayKey(Date.now() - 86400000);
        if (key === today) return 'Hari ini';
        if (key === yesterday) return 'Kemarin';
        return date(value);
    }

    function itemsSummary(items) {
        const shown = items.slice(0, ITEMS_PREVIEW).map((i) => `${i.quantity}× ${i.name}`).join(', ');
        const rest = items.length - ITEMS_PREVIEW;
        return rest > 0 ? `${shown} +${rest} lainnya` : shown;
    }
</script>

<svelte:head><title>Pesanan saya · {storeName}</title></svelte:head>

<div class="min-h-dvh bg-paper pb-12">
    <FlowHeader title="Pesanan saya" backLabel="Kembali ke menu" onback={() => router.visit('/menu')} />

    <main class="mx-auto max-w-2xl px-4 pt-5">
        {#if orders.length === 0}
            <EmptyState icon={ReceiptText} title="Belum ada pesanan" description="Pesanan yang kamu buat dari perangkat ini akan muncul di sini.">
                <Link href="/menu" class="btn-primary">Lihat menu</Link>
            </EmptyState>
        {/if}

        {#if active.length > 0}
            <section aria-labelledby="sedang-diproses" class="mb-8">
                <h2 id="sedang-diproses" class="mb-2 px-1 text-xs font-semibold tracking-wider text-muted uppercase">Sedang diproses</h2>
                <ul class="space-y-2" aria-live="polite">
                    {#each active as order (order.order_number)}
                        <li><ActiveOrderCard {order} /></li>
                    {/each}
                </ul>
            </section>
        {/if}

        {#if past.length > 0}
            <section aria-labelledby="riwayat">
                <h2 id="riwayat" class="display mb-1 px-1 text-2xl text-ink">Riwayat</h2>
                {#each pastByDay as group (group.key)}
                    <h3 class="mt-4 px-1 text-xs font-semibold text-muted">{group.label}</h3>
                    <ul class="card mt-2 divide-y divide-line px-4">
                        {#each group.orders as order (order.order_number)}
                            <li>
                                <Link href={order.status_url} class="group flex items-center gap-3 py-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline justify-between gap-3">
                                            <p class="line-clamp-2 text-sm font-medium text-ink {order.status === 'dibatalkan' ? 'text-muted line-through decoration-faint' : ''}">{itemsSummary(order.items)}</p>
                                            <p class="num shrink-0 text-sm font-semibold text-ink">{rupiah(order.total)}</p>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between gap-3">
                                            <p class="num truncate text-xs text-muted"><span class="font-mono">{order.order_number}</span> · {time(order.created_at)}</p>
                                            <StatusBadge status={order.status} label={order.status_label} />
                                        </div>
                                    </div>
                                    <ChevronRight size={18} class="shrink-0 text-faint transition group-hover:translate-x-0.5" />
                                </Link>
                            </li>
                        {/each}
                    </ul>
                {/each}
            </section>
        {/if}

        {#if orders.length > 0}
            <p class="mt-8 px-4 text-center text-xs text-faint">Riwayat tersimpan di perangkat ini. Pesanan dari HP atau browser lain tidak ikut tampil.</p>
        {/if}
    </main>
</div>
