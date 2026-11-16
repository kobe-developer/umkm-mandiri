<script>
    import { Link, router } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import Bike from '@lucide/svelte/icons/bike';
    import ShoppingBag from '@lucide/svelte/icons/shopping-bag';
    import MessageCircle from '@lucide/svelte/icons/message-circle';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
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

<article class="flex flex-col rounded-card bg-surface p-4 sm:p-5 {isNew ? 'shadow-soft ring-2 ring-brand-500' : 'border border-line'}">
    <header class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-base font-semibold text-ink">{order.customer_name}</p>
            <a href={`https://wa.me/${order.whatsapp_number}`} target="_blank" rel="noopener noreferrer" class="num mt-0.5 inline-flex items-center gap-1 text-xs font-medium text-leaf-600 hover:underline">
                <MessageCircle size={12} />+{order.whatsapp_number}
            </a>
        </div>
        <div class="shrink-0 text-right">
            <StatusBadge status={order.status} label={order.status_label} />
            <p class="num mt-1.5 text-xs {isNew && minutesAgo >= 5 ? 'font-semibold text-brand-600' : 'text-muted'}" title={dateTime(order.created_at)}>{time(order.created_at)}{isNew ? ` · ${minutesAgo} mnt lalu` : ''}</p>
        </div>
    </header>

    <div class="mt-3 flex items-start gap-2 rounded-xl bg-paper px-3 py-2 text-[13px]">
        {#if order.fulfillment === 'delivery'}<Bike size={15} class="mt-px shrink-0 text-muted" />{:else}<ShoppingBag size={15} class="mt-px shrink-0 text-muted" />{/if}
        <p class="min-w-0"><span class="font-semibold text-ink">{order.fulfillment_label}</span>{#if order.address}<span class="text-muted"> · {order.address}</span>{/if}</p>
    </div>

    <ul class="mt-3 space-y-1 text-sm">
        {#each order.items as item (item.id)}
            <li class="flex justify-between gap-3">
                <span class="min-w-0 text-ink-soft"><span class="num mr-1 font-semibold text-ink">{item.quantity}×</span>{item.product_name}{#if item.notes}<span class="block pl-6 text-xs text-amber-ink italic">“{item.notes}”</span>{/if}</span>
                <span class="num shrink-0 text-muted">{rupiah(item.subtotal)}</span>
            </li>
        {/each}
    </ul>
    {#if order.notes}<p class="mt-3 rounded-xl bg-amber-soft px-3 py-2 text-xs text-amber-ink">Catatan: {order.notes}</p>{/if}

    <div class="mt-auto pt-4">
        <div class="flex items-center justify-between gap-2 border-t border-dashed border-line-strong pt-3">
            <span class="flex min-w-0 items-center gap-2 text-xs text-muted">
                <span class="truncate">{order.planned_payment_method?.name ?? '-'}</span>
                <StatusBadge kind="payment" status={order.payment_status} label={order.payment_status_label} />
            </span>
            <span class="num text-lg font-semibold text-ink">{rupiah(order.total)}</span>
        </div>
        <p class="mt-1 text-[11px] text-faint">{order.order_number}</p>

        <div class="mt-3 flex gap-2">
            {#if isNew}
                <button type="button" class="btn-accent flex-1" disabled={busy} onclick={advance}><Check size={17} strokeWidth={2.5} />Konfirmasi</button>
            {:else if canAdvance}
                <button type="button" class="btn-primary flex-1" disabled={busy} onclick={advance}>{order.next_status_label}<ArrowRight size={16} /></button>
            {/if}
            <Link href={`/pos/orders/${order.order_number}`} class="btn-secondary {isNew || canAdvance ? '' : 'flex-1'}">Detail</Link>
        </div>
    </div>
</article>
