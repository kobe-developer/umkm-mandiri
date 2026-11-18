<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import { onMount } from 'svelte';
    import { page, router } from '@inertiajs/svelte';
    import StatusTimeline from '../../components/menu/StatusTimeline.svelte';
    import PaymentInfo from '../../components/menu/PaymentInfo.svelte';
    import StatusBadge from '../../components/StatusBadge.svelte';
    import Check from '@lucide/svelte/icons/check';
    import MessageCircle from '@lucide/svelte/icons/message-circle';
    import RefreshCw from '@lucide/svelte/icons/refresh-cw';
    import Copy from '@lucide/svelte/icons/copy';
    import { rupiah, dateTime } from '../../lib/format.js';
    import { cart } from '../../lib/cart.svelte.js';
    import { isFinalStatus } from '../../lib/orderStatus.js';
    import { keepOrdersFresh } from '../../lib/realtime.js';

    let { order, isNew = false, storeWhatsapp = null, paymentMethod = null, realtimeChannel = null } = $props();

    /** Polling cadangan bila WebSocket tidak tersedia. */
    const FALLBACK_REFRESH_MS = 15000;

    const isFinal = $derived(isFinalStatus(order.status));
    const realtime = $derived(Boolean(page.props.config?.realtime && realtimeChannel));
    const storeChatUrl = $derived(
        storeWhatsapp ? `https://wa.me/${storeWhatsapp}?text=${encodeURIComponent(`Halo, saya mau tanya pesanan ${order.order_number} atas nama ${order.customer_name}.`)}` : null,
    );
    const showPaymentInfo = $derived(order.payment_status === 'belum_bayar' && order.status !== 'dibatalkan');

    let copied = $state(false);

    async function copyOrderNumber() {
        try {
            await navigator.clipboard.writeText(order.order_number);
            copied = true;
            setTimeout(() => (copied = false), 1800);
        } catch {
            copied = false;
        }
    }

    onMount(() => {
        if (isNew) cart.clear();
    });

    // Status ikut berubah seketika saat kasir memprosesnya, selama pesanan belum final
    $effect(() => {
        if (isFinal) return;
        return keepOrdersFresh({
            channels: [realtimeChannel],
            realtime,
            intervalMs: FALLBACK_REFRESH_MS,
            reload: () => router.reload({ only: ['order'], preserveScroll: true, showProgress: false }),
        });
    });
</script>

<PageTitle title={`Pesanan ${order.order_number}`} />

{#snippet row(label, value)}
    <div class="flex justify-between gap-4 py-2.5">
        <dt class="shrink-0 text-muted">{label}</dt>
        <dd class="text-right text-ink">{value}</dd>
    </div>
{/snippet}

<div class="min-h-dvh bg-paper pb-12">
    <header class="mx-auto max-w-lg px-5 pt-[max(2.5rem,env(safe-area-inset-top))] pb-8 text-center">
        {#if isNew}
            <div class="mx-auto mb-5 flex size-16 animate-pop items-center justify-center rounded-full bg-leaf-600 text-white shadow-float" aria-hidden="true">
                <Check size={30} strokeWidth={2.5} />
            </div>
            <h1 class="display text-[2rem] leading-tight text-ink">Pesanan terkirim</h1>
            <p class="mx-auto mt-2 max-w-xs text-sm text-muted">Kasir akan mengonfirmasi pesananmu lewat WhatsApp sebentar lagi.</p>
        {:else}
            <h1 class="display text-[2rem] leading-tight text-ink">Status pesanan</h1>
        {/if}
        <button type="button" class="group mx-auto mt-5 inline-flex items-center gap-2 rounded-full border border-dashed border-line-strong bg-surface px-4 py-2" onclick={copyOrderNumber} aria-label="Salin nomor order {order.order_number}">
            <span class="num font-mono text-base font-semibold tracking-wider text-ink">{order.order_number}</span>
            <span class="text-faint group-hover:text-ink-soft">{#if copied}<Check size={15} />{:else}<Copy size={15} />{/if}</span>
        </button>
    </header>

    <main class="mx-auto max-w-lg space-y-4 px-4">
        <section class="card p-5">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                <StatusBadge status={order.status} label={order.status_label} />
                <StatusBadge status={order.payment_status} label={order.payment_status_label} kind="payment" />
            </div>
            <StatusTimeline {order} />
            {#if !isFinal}
                <p class="flex items-center justify-center gap-1.5 border-t border-line pt-4 text-xs text-muted">
                    <RefreshCw size={12} class="animate-spin [animation-duration:3s]" />Diperbarui otomatis · simpan link ini untuk cek status
                </p>
            {/if}
        </section>

        <section class="card p-5 text-sm">
            <h2 class="display mb-2 text-lg text-ink">Ringkasan</h2>
            <ul class="divide-y divide-line">
                {#each order.items ?? [] as item (item.id)}
                    <li class="flex justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-ink"><span class="num mr-1.5 text-muted">{item.quantity}×</span>{item.product_name}</p>
                            {#if item.notes}<p class="mt-0.5 text-xs text-muted italic">“{item.notes}”</p>{/if}
                        </div>
                        <p class="num text-ink">{rupiah(item.subtotal)}</p>
                    </li>
                {/each}
            </ul>
            {#if order.discount > 0}
                <div class="flex justify-between border-t border-line py-2.5 text-leaf-600">
                    <span>Diskon</span><span class="num">−{rupiah(order.discount)}</span>
                </div>
            {/if}
            <div class="flex items-baseline justify-between border-t border-ink/80 pt-3">
                <span class="font-semibold text-ink">Total</span><span class="num text-xl font-semibold text-ink">{rupiah(order.total)}</span>
            </div>

            <dl class="mt-5 divide-y divide-line border-t border-line">
                {@render row('Nama', order.customer_name)}
                {@render row('WhatsApp', order.whatsapp_number)}
                {@render row('Pengambilan', order.fulfillment_label ?? '-')}
                {#if order.address}{@render row('Alamat', order.address)}{/if}
                {#if order.notes}{@render row('Catatan', order.notes)}{/if}
                {@render row('Waktu', dateTime(order.created_at))}
            </dl>
        </section>

        {#if paymentMethod || order.payment_method}
            <section class="card space-y-3 p-5 text-sm">
                <h2 class="display text-lg text-ink">Pembayaran</h2>
                {#if order.payment_status === 'lunas'}
                    <p class="flex items-center gap-2 text-leaf-700"><Check size={16} />Lunas{order.payment_method ? ` via ${order.payment_method.name}` : ''}. Terima kasih!</p>
                {:else if paymentMethod}
                    <p class="text-muted">Rencana bayar: <strong class="font-semibold text-ink">{paymentMethod.name}</strong></p>
                    {#if showPaymentInfo}<PaymentInfo method={paymentMethod} />{/if}
                {/if}
            </section>
        {/if}

        <div class="flex flex-col gap-2 pt-2 sm:flex-row">
            {#if storeChatUrl}
                <a href={storeChatUrl} target="_blank" rel="noopener noreferrer" class="btn-success min-h-12 flex-1">
                    <MessageCircle size={18} />Chat toko via WhatsApp
                </a>
            {/if}
            <a href="/menu" class="btn-secondary min-h-12 flex-1">Pesan lagi</a>
        </div>
    </main>
</div>
