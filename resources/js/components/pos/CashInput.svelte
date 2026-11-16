<script>
    import MoneyInput from '../MoneyInput.svelte';
    import { rupiah } from '../../lib/format.js';

    /** Input uang diterima + tombol cepat + kembalian. */
    let { value = $bindable(null), total, error = '' } = $props();

    const QUICK = [20000, 50000, 100000];
    const change = $derived(value !== null && value >= total ? value - total : null);
</script>

<div class="space-y-2">
    <label class="label" for="amount_received">Uang diterima</label>
    <MoneyInput id="amount_received" bind:value />
    <div class="flex flex-wrap gap-1.5">
        <button type="button" class="btn-secondary btn-sm" onclick={() => (value = total)}>Uang pas</button>
        {#each QUICK as amount (amount)}
            {#if amount >= total}
                <button type="button" class="btn-secondary btn-sm" onclick={() => (value = amount)}>{amount / 1000}rb</button>
            {/if}
        {/each}
    </div>
    {#if error}<p class="error">{error}</p>{/if}
    <div class="flex justify-between rounded-xl bg-stone-50 px-3 py-2 text-sm">
        <span class="text-stone-600">Kembalian</span>
        {#if change !== null}
            <span class="font-bold text-emerald-700 tabular-nums">{rupiah(change)}</span>
        {:else if value !== null}
            <span class="font-semibold text-red-600">Kurang {rupiah(total - value)}</span>
        {:else}
            <span class="text-stone-400">–</span>
        {/if}
    </div>
</div>
