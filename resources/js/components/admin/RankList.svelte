<script>
    import { rupiah } from '../../lib/format.js';

    /** Daftar peringkat dengan bar proporsional. valueKey: 'quantity' | 'revenue'. */
    let { rows = [], valueKey = 'quantity', labelKey = 'name', emptyText = 'Belum ada data' } = $props();

    const max = $derived(Math.max(1, ...rows.map((r) => r[valueKey] ?? 0)));

    function format(value) {
        return valueKey === 'revenue' ? rupiah(value) : `${new Intl.NumberFormat('id-ID').format(value)} porsi`;
    }
</script>

{#if rows.length === 0}
    <p class="py-6 text-center text-sm text-stone-500">{emptyText}</p>
{:else}
    <ol class="space-y-2">
        {#each rows as row, index (row[labelKey])}
            <li>
                <div class="flex items-baseline justify-between gap-2 text-sm">
                    <span class="truncate"><span class="mr-1 text-stone-400 tabular-nums">{index + 1}.</span>{row[labelKey]}</span>
                    <span class="shrink-0 font-semibold tabular-nums">{format(row[valueKey])}</span>
                </div>
                <div class="mt-1 h-1.5 rounded-full bg-stone-100">
                    <div class="h-1.5 rounded-full bg-brand-500" style="width: {Math.max(2, (row[valueKey] / max) * 100)}%"></div>
                </div>
            </li>
        {/each}
    </ol>
{/if}
