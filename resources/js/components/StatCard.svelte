<script>
    import TrendingUp from '@lucide/svelte/icons/trending-up';
    import TrendingDown from '@lucide/svelte/icons/trending-down';
    import { percent } from '../lib/format.js';

    /** Angka ringkas. `featured` = kartu utama (tinta gelap). `change` = % vs periode sebelumnya. */
    let { label, value, hint = '', change = undefined, featured = false } = $props();

    const hasChange = $derived(change !== null && change !== undefined);
</script>

<div class="flex min-h-32 flex-col justify-between rounded-card p-4 sm:p-5 {featured ? 'bg-ink text-paper' : 'border border-line bg-surface'}">
    <p class="text-[13px] font-medium {featured ? 'text-paper/60' : 'text-muted'}">{label}</p>
    <div>
        <p class="num mt-3 text-[1.6rem] leading-none font-semibold tracking-tight sm:text-3xl">{value}</p>
        {#if change !== undefined}
            <p class="mt-2 flex items-center gap-1 text-xs {featured ? 'text-paper/60' : 'text-muted'}">
                {#if hasChange}
                    <span class="inline-flex items-center gap-0.5 font-semibold {change >= 0 ? (featured ? 'text-leaf-100' : 'text-leaf-600') : featured ? 'text-brand-200' : 'text-brand-600'}">
                        {#if change >= 0}<TrendingUp size={13} />{:else}<TrendingDown size={13} />{/if}{percent(change)}
                    </span>
                {:else}–{/if}
                <span>vs sebelumnya</span>
            </p>
        {:else if hint}
            <p class="mt-2 text-xs {featured ? 'text-paper/60' : 'text-muted'}">{hint}</p>
        {/if}
    </div>
</div>
