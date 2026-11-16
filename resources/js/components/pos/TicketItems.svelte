<script>
    import QtyStepper from '../QtyStepper.svelte';
    import { rupiah } from '../../lib/format.js';

    /** Daftar item di tiket kasir: qty stepper & catatan per item. */
    let { cart, maxFor, onqty, onnotes } = $props();

    let editingNote = $state(null);
</script>

{#if cart.length === 0}
    <p class="py-6 text-center text-sm text-stone-500">Belum ada item. Tap produk untuk menambah.</p>
{:else}
    <ul class="divide-y divide-stone-100">
        {#each cart as item (item.product_id)}
            <li class="py-2">
                <div class="flex items-center gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{item.name}</p>
                        <p class="text-xs text-stone-500 tabular-nums">{rupiah(item.price)} · {rupiah(item.price * item.quantity)}</p>
                    </div>
                    <QtyStepper size="sm" value={item.quantity} max={maxFor(item)} onchange={(q) => onqty(item.product_id, q)} />
                </div>
                {#if editingNote === item.product_id || item.notes}
                    <input
                        class="input mt-1.5 py-1.5 text-xs"
                        placeholder="Catatan, mis. sambal dipisah / level pedas 3"
                        maxlength="100"
                        value={item.notes}
                        oninput={(e) => onnotes(item.product_id, e.currentTarget.value)}
                        onblur={() => (editingNote = null)}
                        aria-label="Catatan {item.name}"
                    />
                {:else}
                    <button type="button" class="mt-1 text-xs font-medium text-brand-700" onclick={() => (editingNote = item.product_id)}>+ Catatan</button>
                {/if}
            </li>
        {/each}
    </ul>
{/if}
