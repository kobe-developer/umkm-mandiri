<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import Clock from '@lucide/svelte/icons/clock';
    import PageHeader from '../../components/PageHeader.svelte';
    import PeriodFilter from '../../components/PeriodFilter.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import { rupiah, dateTime } from '../../lib/format.js';

    let { period, shifts = [] } = $props();

    function money(value) {
        return value === null || value === undefined ? '–' : rupiah(value);
    }

    function differenceClass(value) {
        if (value === null || value === undefined) return 'text-muted';
        if (value < 0) return 'text-brand-600';
        if (value > 0) return 'text-amber-ink';
        return 'text-leaf-600';
    }
</script>

<PageTitle title="Laporan Shift" />

<PageHeader title="Laporan Shift" subtitle="Kas awal, penjualan tunai & non-tunai, dan selisih kas saat tutup shift" />

<div class="mb-6"><PeriodFilter {period} url="/admin/shift" /></div>

{#snippet openBadge()}
    <span class="inline-flex items-center gap-1.5 rounded-full bg-leaf-50 px-2.5 py-0.5 text-xs font-semibold text-leaf-700">
        <span class="size-1.5 rounded-full bg-leaf-500"></span>Masih buka
    </span>
{/snippet}

{#if shifts.length === 0}
    <EmptyState icon={Clock} title="Belum ada shift" description="Tidak ada shift yang dibuka pada periode ini." />
{:else}
    <!-- Mobile & tablet: satu blok per shift -->
    <ul class="card divide-y divide-line xl:hidden">
        {#each shifts as shift (shift.id)}
            <li class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink">{shift.cashier_name}</p>
                        <p class="mt-0.5 text-xs text-muted">
                            {dateTime(shift.opened_at)} – {#if shift.closed_at}{dateTime(shift.closed_at)}{:else}sekarang{/if}
                        </p>
                    </div>
                    {#if shift.closed_at}
                        <div class="shrink-0 text-right">
                            <p class="text-xs text-muted">Selisih</p>
                            <p class="num font-semibold {differenceClass(shift.cash_difference)}">{money(shift.cash_difference)}</p>
                        </div>
                    {:else}
                        {@render openBadge()}
                    {/if}
                </div>
                <dl class="mt-3 grid gap-x-4 gap-y-1.5 rounded-xl bg-paper px-3.5 py-3 text-sm sm:grid-cols-3">
                    <div class="flex justify-between gap-2 sm:block"><dt class="text-muted">Kas awal</dt><dd class="num text-ink">{money(shift.opening_cash)}</dd></div>
                    <div class="flex justify-between gap-2 sm:block"><dt class="text-muted">Tunai</dt><dd class="num text-ink">{money(shift.cash_sales)}</dd></div>
                    <div class="flex justify-between gap-2 sm:block"><dt class="text-muted">Non-tunai</dt><dd class="num text-ink">{money(shift.non_cash_sales)}</dd></div>
                    <div class="flex justify-between gap-2 sm:block"><dt class="text-muted">Kas sistem</dt><dd class="num text-ink">{money(shift.closing_cash_system)}</dd></div>
                    <div class="flex justify-between gap-2 sm:block"><dt class="text-muted">Kas aktual</dt><dd class="num text-ink">{money(shift.closing_cash_actual)}</dd></div>
                </dl>
            </li>
        {/each}
    </ul>

    <!-- Layar lebar: tabel lengkap -->
    <div class="card hidden overflow-x-auto xl:block">
        <table class="table">
            <thead>
                <tr>
                    <th>Kasir</th><th>Buka</th><th>Tutup</th>
                    <th class="text-right">Kas awal</th><th class="text-right">Tunai</th><th class="text-right">Non-tunai</th>
                    <th class="text-right">Kas sistem</th><th class="text-right">Kas aktual</th><th class="text-right">Selisih</th>
                </tr>
            </thead>
            <tbody>
                {#each shifts as shift (shift.id)}
                    <tr>
                        <td class="font-medium text-ink">{shift.cashier_name}</td>
                        <td class="whitespace-nowrap text-ink-soft">{dateTime(shift.opened_at)}</td>
                        <td class="whitespace-nowrap text-ink-soft">
                            {#if shift.closed_at}{dateTime(shift.closed_at)}{:else}{@render openBadge()}{/if}
                        </td>
                        <td class="num text-right whitespace-nowrap">{money(shift.opening_cash)}</td>
                        <td class="num text-right whitespace-nowrap">{money(shift.cash_sales)}</td>
                        <td class="num text-right whitespace-nowrap">{money(shift.non_cash_sales)}</td>
                        <td class="num text-right whitespace-nowrap">{money(shift.closing_cash_system)}</td>
                        <td class="num text-right whitespace-nowrap">{money(shift.closing_cash_actual)}</td>
                        <td class="num text-right font-semibold whitespace-nowrap {differenceClass(shift.cash_difference)}">
                            {money(shift.cash_difference)}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/if}
