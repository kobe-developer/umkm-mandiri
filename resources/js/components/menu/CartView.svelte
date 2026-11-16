<script>
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import NotebookPen from '@lucide/svelte/icons/notebook-pen';
    import ShoppingBag from '@lucide/svelte/icons/shopping-bag';
    import Info from '@lucide/svelte/icons/info';
    import FlowHeader from './FlowHeader.svelte';
    import QtyStepper from '../QtyStepper.svelte';
    import EmptyState from '../EmptyState.svelte';
    import { rupiah } from '../../lib/format.js';
    import { cart, CART_MAX_QUANTITY } from '../../lib/cart.svelte.js';

    /** Halaman keranjang: ubah qty, catatan per item, hapus, lihat total. */
    let { productMap, settings, blockedReason = null, onback, oncheckout } = $props();

    const lines = $derived(cart.items.map((item) => ({ ...item, product: productMap.get(item.product_id) })).filter((line) => line.product));
    const subtotal = $derived(cart.subtotal(productMap));
    const soldOutLines = $derived(lines.filter((line) => line.product.is_sold_out));
    const belowMinimum = $derived(settings.min_order > 0 && subtotal < settings.min_order);
    const canCheckout = $derived(lines.length > 0 && soldOutLines.length === 0 && !belowMinimum && !blockedReason);
    const minimumProgress = $derived(settings.min_order > 0 ? Math.min(100, Math.round((subtotal / settings.min_order) * 100)) : 100);

    let openNotes = $state({});
</script>

<FlowHeader title="Keranjang" step={1} backLabel="Kembali ke menu" {onback} />

<div class="mx-auto max-w-2xl px-5 pt-2 pb-40">
    {#if lines.length === 0}
        <div class="pt-6">
            <EmptyState icon={ShoppingBag} title="Keranjang masih kosong" description="Yuk pilih menu dulu.">
                <button type="button" class="btn-primary" onclick={onback}>Lihat menu</button>
            </EmptyState>
        </div>
    {:else}
        <ul class="divide-y divide-line">
            {#each lines as line (line.product_id)}
                {@const soldOut = line.product.is_sold_out}
                {@const showNote = openNotes[line.product_id] || line.notes}
                <li class="py-5">
                    <div class="flex gap-4">
                        {#if line.product.photo_url}
                            <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-paper-deep {soldOut ? 'grayscale' : ''}">
                                <img src={line.product.photo_url} alt="" width="64" height="64" class="size-full object-cover" loading="lazy" />
                            </div>
                        {/if}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-[15px] leading-snug font-semibold text-ink">{line.product.name}</p>
                                <p class="num shrink-0 text-[15px] font-semibold text-ink">{rupiah(line.product.price * line.quantity)}</p>
                            </div>
                            <p class="num mt-0.5 text-[13px] text-muted">{rupiah(line.product.price)} / porsi</p>
                            {#if soldOut}
                                <p class="mt-1.5 text-xs font-semibold text-brand-600">Habis — hapus item ini untuk melanjutkan.</p>
                            {/if}
                            <div class="mt-3 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1">
                                    <button type="button" class="btn-icon size-9 text-muted hover:text-brand-600" aria-label="Hapus {line.product.name}" onclick={() => cart.remove(line.product_id)}>
                                        <Trash2 size={17} />
                                    </button>
                                    {#if !showNote}
                                        <button type="button" class="inline-flex min-h-9 items-center gap-1.5 rounded-full px-2.5 text-[13px] font-medium text-muted hover:bg-ink/5 hover:text-ink" onclick={() => (openNotes = { ...openNotes, [line.product_id]: true })}>
                                            <NotebookPen size={15} />Catatan
                                        </button>
                                    {/if}
                                </div>
                                {#if !soldOut}
                                    <QtyStepper value={line.quantity} min={1} max={CART_MAX_QUANTITY} size="sm" onchange={(q) => cart.setQuantity(line.product_id, q)} />
                                {/if}
                            </div>
                            {#if showNote}
                                <input
                                    class="input mt-3"
                                    maxlength="100"
                                    placeholder="Misal: sambal dipisah, level pedas 3"
                                    aria-label="Catatan untuk {line.product.name}"
                                    value={line.notes}
                                    oninput={(e) => cart.setNotes(line.product_id, e.currentTarget.value)}
                                />
                            {/if}
                        </div>
                    </div>
                </li>
            {/each}
        </ul>

        <div class="mt-2 rounded-card bg-surface p-5 ring-1 ring-line">
            <div class="flex items-baseline justify-between">
                <span class="text-sm text-muted">Total</span>
                <span class="num text-2xl font-semibold tracking-tight text-ink">{rupiah(subtotal)}</span>
            </div>
            {#if belowMinimum}
                <div class="mt-4">
                    <div class="h-1.5 overflow-hidden rounded-full bg-paper-deep">
                        <div class="h-full rounded-full bg-brand-500 transition-all duration-500" style="width:{minimumProgress}%"></div>
                    </div>
                    <p class="mt-2 text-xs font-medium text-amber-ink">Tambah {rupiah(settings.min_order - subtotal)} lagi untuk mencapai minimal order {rupiah(settings.min_order)}.</p>
                </div>
            {/if}
            <p class="mt-3 flex items-start gap-1.5 text-xs text-muted"><Info size={13} class="mt-px shrink-0" />Harga final dihitung ulang oleh sistem saat pesanan dikirim.</p>
        </div>
    {/if}
</div>

{#if lines.length > 0}
    <div class="safe-bottom fixed inset-x-0 bottom-0 z-30 border-t border-line bg-paper/95 px-4 pt-3 backdrop-blur-xl">
        <div class="mx-auto max-w-2xl">
            {#if blockedReason}
                <p class="mb-2 text-center text-sm font-semibold text-brand-600">{blockedReason}</p>
            {/if}
            <button type="button" class="btn-accent min-h-13 w-full text-base" disabled={!canCheckout} onclick={oncheckout}>
                Lanjut checkout <span class="opacity-50">·</span> <span class="num">{rupiah(subtotal)}</span>
            </button>
        </div>
    </div>
{/if}
