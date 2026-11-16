<script>
    import { percent } from '../lib/format.js';

    /** Kartu angka ringkas dengan perubahan vs periode sebelumnya (opsional). */
    let { label, value, hint = '', change = undefined } = $props();

    const changeClass = $derived(change === null || change === undefined ? 'text-stone-400' : change >= 0 ? 'text-emerald-600' : 'text-red-600');
</script>

<div class="card p-4">
    <p class="text-xs font-medium tracking-wide text-stone-500 uppercase">{label}</p>
    <p class="mt-1 text-2xl font-bold text-stone-900 tabular-nums">{value}</p>
    {#if change !== undefined}
        <p class="mt-1 text-xs {changeClass}">{percent(change)} <span class="text-stone-400">vs periode sebelumnya</span></p>
    {:else if hint}
        <p class="mt-1 text-xs text-stone-500">{hint}</p>
    {/if}
</div>
