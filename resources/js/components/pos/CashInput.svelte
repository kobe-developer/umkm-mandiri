<script>
    import MoneyInput from '../MoneyInput.svelte';
    import { rupiah } from '../../lib/format.js';

    /** Input uang diterima + tombol cepat + kembalian. */
    let { value = $bindable(null), total, error = '' } = $props();

    const QUICK = [20000, 50000, 100000];
    const change = $derived(value !== null && value >= total ? value - total : null);
</script>

<div class="space-y-2.5">
    <label class="label mb-0" for="amount_received">Uang diterima</label>
    <MoneyInput id="amount_received" bind:value />
    <div class="flex flex-wrap gap-1.5">
        <button type="button" class="chip {value === total ? 'chip-active' : ''}" onclick={() => (value = total)}>Uang pas</button>
        {#each QUICK as amount (amount)}
            {#if amount >= total}
                <button type="button" class="chip num {value === amount ? 'chip-active' : ''}" onclick={() => (value = amount)}>{amount / 1000}rb</button>
            {/if}
        {/each}
    </div>
    {#if error}<p class="error">{error}</p>{/if}
    <div class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-sm {change !== null ? 'bg-leaf-50' : value !== null ? 'bg-brand-50' : 'bg-paper'}">
        <span class="text-muted">Kembalian</span>
        {#if change !== null}
            <span class="num text-base font-semibold text-leaf-700">{rupiah(change)}</span>
        {:else if value !== null}
            <span class="num font-semibold text-brand-700">Kurang {rupiah(total - value)}</span>
        {:else}
            <span class="text-faint">–</span>
        {/if}
    </div>
</div>
