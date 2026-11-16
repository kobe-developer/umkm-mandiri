<script>
    import { Link, router } from '@inertiajs/svelte';
    import StatusBadge from '../StatusBadge.svelte';
    import { rupiah, time, dateTime } from '../../lib/format.js';

    /** Kartu order online dengan aksi cepat. */
    let { order } = $props();

    let busy = $state(false);

    const isNew = $derived(order.status === 'baru');
    const isFinal = $derived(order.status === 'selesai' || order.status === 'dibatalkan');
    const isPaid = $derived(order.payment_status === 'lunas');
    const canAdvance = $derived(!isFinal && !isNew && order.next_status && (order.next_status !== 'selesai' || isPaid));
    const minutesAgo = $derived(Math.max(0, Math.round((Date.now() - new Date(order.created_at).getTime()) / 60000)));

    function advance() {
        busy = true;
        router.post(`/pos/orders/${order.order_number}/advance`, {}, { preserveScroll: true, onFinish: () => (busy = false) });
    }
</script>

<article class="card flex flex-col p-4 {isNew ? 'border-amber-300 bg-amber-50/60 ring-2 ring-amber-200' : ''}">
    <header class="flex items-start justify-between gap-2">
        <div>
            <p class="font-bold">{order.customer_name}</p>
            <a href={`https://wa.me/${order.whatsapp_number}`} target="_blank" rel="noopener noreferrer" class="text-xs text-emerald-700 underline">+{order.whatsapp_number}</a>
        </div>
        <div class="text-right">
            <StatusBadge status={order.status} label={order.status_label} />
            <p class="mt-1 text-xs text-stone-500" title={dateTime(order.created_at)}>{time(order.created_at)}{isNew ? ` · ${minutesAgo} mnt lalu` : ''}</p>
        </div>
    </header>

    <p class="mt-2 text-xs font-semibold text-stone-500">{order.order_number}</p>
    <p class="text-sm">
        <span class="font-medium">{order.fulfillment_label}</span>
        {#if order.address}<span class="text-stone-600"> · {order.address}</span>{/if}
    </p>

    <ul class="mt-2 space-y-0.5 text-sm">
        {#each order.items as item (item.id)}
            <li class="flex justify-between gap-2">
                <span>{item.quantity}× {item.product_name}{#if item.notes}<span class="text-xs text-amber-700 italic"> ({item.notes})</span>{/if}</span>
                <span class="text-stone-500 tabular-nums">{rupiah(item.subtotal)}</span>
            </li>
        {/each}
    </ul>
    {#if order.notes}<p class="mt-2 rounded-lg bg-white px-2 py-1 text-xs text-amber-900">📝 {order.notes}</p>{/if}

    <div class="mt-3 flex items-center justify-between border-t border-stone-200 pt-2 text-sm">
        <span class="text-stone-500">{order.planned_payment_method?.name ?? '-'} · <StatusBadge kind="payment" status={order.payment_status} label={order.payment_status_label} /></span>
        <span class="text-base font-bold tabular-nums">{rupiah(order.total)}</span>
    </div>

    <div class="mt-3 flex gap-2">
        {#if isNew}
            <button type="button" class="btn-primary flex-1" disabled={busy} onclick={advance}>✓ Konfirmasi</button>
        {:else if canAdvance}
            <button type="button" class="btn-primary flex-1" disabled={busy} onclick={advance}>{order.next_status_label}</button>
        {/if}
        <Link href={`/pos/orders/${order.order_number}`} class="btn-secondary {isNew || canAdvance ? '' : 'flex-1'}">Detail</Link>
    </div>
</article>
