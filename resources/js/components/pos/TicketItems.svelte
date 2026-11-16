<script>
    import NotebookPen from '@lucide/svelte/icons/notebook-pen';
    import Receipt from '@lucide/svelte/icons/receipt';
    import QtyStepper from '../QtyStepper.svelte';
    import { rupiah } from '../../lib/format.js';

    /** Daftar item di tiket kasir: qty stepper & catatan per item. */
    let { cart, maxFor, onqty, onnotes } = $props();

    let editingNote = $state(null);
</script>

{#if cart.length === 0}
    <div class="flex flex-col items-center gap-2 py-10 text-center text-sm text-muted">
        <span class="flex size-12 items-center justify-center rounded-full bg-paper-deep text-faint"><Receipt size={22} strokeWidth={1.5} /></span>
        Belum ada item. Tap produk untuk menambah.
    </div>
{:else}
    <ul class="divide-y divide-line">
        {#each cart as item (item.product_id)}
            <li class="py-3">
                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink">{item.name}</p>
                        <p class="num text-xs text-muted">{rupiah(item.price)} <span class="text-faint">·</span> <span class="text-ink-soft">{rupiah(item.price * item.quantity)}</span></p>
                    </div>
                    <QtyStepper size="sm" value={item.quantity} max={maxFor(item)} onchange={(q) => onqty(item.product_id, q)} />
                </div>
                {#if editingNote === item.product_id || item.notes}
                    <input
                        class="input mt-2"
                        placeholder="Catatan, mis. sambal dipisah / level pedas 3"
                        maxlength="100"
                        value={item.notes}
                        oninput={(e) => onnotes(item.product_id, e.currentTarget.value)}
                        onblur={() => (editingNote = null)}
                        aria-label="Catatan {item.name}"
                    />
                {:else}
                    <button type="button" class="mt-1 -ml-1 inline-flex min-h-8 items-center gap-1 rounded-full px-1.5 text-xs font-medium text-muted hover:text-ink" onclick={() => (editingNote = item.product_id)}>
                        <NotebookPen size={13} />Catatan
                    </button>
                {/if}
            </li>
        {/each}
    </ul>
{/if}
