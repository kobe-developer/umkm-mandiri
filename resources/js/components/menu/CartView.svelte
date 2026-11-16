<script>
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

    let openNotes = $state({});
</script>

<div class="mx-auto max-w-2xl px-4 pt-4 pb-32">
    <div class="mb-4 flex items-center gap-2">
        <button type="button" class="btn-ghost btn-sm text-lg" aria-label="Kembali ke menu" onclick={onback}>←</button>
        <h2 class="text-lg font-bold">Keranjang</h2>
    </div>

    {#if lines.length === 0}
        <EmptyState title="Keranjang masih kosong" description="Yuk pilih menu dulu.">
            <button type="button" class="btn-primary" onclick={onback}>Lihat menu</button>
        </EmptyState>
    {:else}
        <ul class="card divide-y divide-stone-100">
            {#each lines as line (line.product_id)}
                <li class="p-4 {line.product.is_sold_out ? 'bg-red-50' : ''}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{line.product.name}</p>
                            <p class="text-sm text-stone-500 tabular-nums">{rupiah(line.product.price)} × {line.quantity}</p>
                            {#if line.product.is_sold_out}
                                <p class="mt-1 text-xs font-semibold text-red-600">Habis — hapus item ini untuk melanjutkan.</p>
                            {/if}
                        </div>
                        <p class="font-semibold tabular-nums">{rupiah(line.product.price * line.quantity)}</p>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            {#if !line.product.is_sold_out}
                                <QtyStepper value={line.quantity} min={1} max={CART_MAX_QUANTITY} size="sm" onchange={(q) => cart.setQuantity(line.product_id, q)} />
                            {/if}
                            <button type="button" class="btn-ghost btn-sm text-red-600" onclick={() => cart.remove(line.product_id)}>Hapus</button>
                        </div>
                        {#if !openNotes[line.product_id] && !line.notes}
                            <button type="button" class="btn-ghost btn-sm text-stone-600" onclick={() => (openNotes = { ...openNotes, [line.product_id]: true })}>
                                + Catatan
                            </button>
                        {/if}
                    </div>
                    {#if openNotes[line.product_id] || line.notes}
                        <input
                            class="input mt-2 py-2"
                            maxlength="100"
                            placeholder="Misal: sambal dipisah, level pedas 3"
                            aria-label="Catatan untuk {line.product.name}"
                            value={line.notes}
                            oninput={(e) => cart.setNotes(line.product_id, e.currentTarget.value)}
                        />
                    {/if}
                </li>
            {/each}
        </ul>

        <div class="card mt-4 space-y-1 p-4 text-sm">
            <div class="flex justify-between text-base font-bold">
                <span>Total</span>
                <span class="tabular-nums">{rupiah(subtotal)}</span>
            </div>
            <p class="text-xs text-stone-500">Harga final dihitung ulang oleh sistem saat pesanan dikirim.</p>
            {#if belowMinimum}
                <p class="text-xs font-semibold text-amber-700">Minimal order {rupiah(settings.min_order)}. Tambah {rupiah(settings.min_order - subtotal)} lagi.</p>
            {/if}
        </div>
    {/if}
</div>

{#if lines.length > 0}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200 bg-white px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
        <div class="mx-auto max-w-2xl">
            {#if blockedReason}
                <p class="mb-2 text-center text-sm font-semibold text-red-600">{blockedReason}</p>
            {/if}
            <button type="button" class="btn-primary w-full py-3 text-base" disabled={!canCheckout} onclick={oncheckout}>
                Lanjut checkout · {rupiah(subtotal)}
            </button>
        </div>
    </div>
{/if}
