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
    <p class="py-6 text-center text-sm text-muted">{emptyText}</p>
{:else}
    <ol class="space-y-3.5">
        {#each rows as row, index (row[labelKey])}
            <li class="flex gap-3">
                <span class="num w-5 shrink-0 pt-px text-right text-sm text-muted">{index + 1}</span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <span class="truncate text-ink">{row[labelKey]}</span>
                        <span class="num shrink-0 font-semibold text-ink">{format(row[valueKey])}</span>
                    </div>
                    <div class="mt-1.5 h-1 rounded-full bg-paper-deep">
                        <div
                            class="h-1 rounded-full {index === 0 ? 'bg-brand-500' : 'bg-ink/25'}"
                            style="width: {Math.max(2, (row[valueKey] / max) * 100)}%"
                        ></div>
                    </div>
                </div>
            </li>
        {/each}
    </ol>
{/if}
