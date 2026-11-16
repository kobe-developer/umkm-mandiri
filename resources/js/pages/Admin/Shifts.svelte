<script>
    import PageHeader from '../../components/PageHeader.svelte';
    import PeriodFilter from '../../components/PeriodFilter.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import { rupiah, dateTime } from '../../lib/format.js';

    let { period, shifts = [] } = $props();

    function money(value) {
        return value === null || value === undefined ? '–' : rupiah(value);
    }
</script>

<svelte:head><title>Laporan Shift</title></svelte:head>

<PageHeader title="Laporan Shift" subtitle="Kas awal, penjualan tunai & non-tunai, dan selisih kas saat tutup shift" />

<div class="mb-4"><PeriodFilter {period} url="/admin/shift" /></div>

{#if shifts.length === 0}
    <EmptyState title="Belum ada shift" description="Tidak ada shift yang dibuka pada periode ini." />
{:else}
    <div class="card overflow-x-auto">
        <table class="table min-w-[900px]">
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
                        <td class="font-medium">{shift.cashier_name}</td>
                        <td class="whitespace-nowrap">{dateTime(shift.opened_at)}</td>
                        <td class="whitespace-nowrap">
                            {#if shift.closed_at}{dateTime(shift.closed_at)}{:else}<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Masih buka</span>{/if}
                        </td>
                        <td class="text-right tabular-nums">{money(shift.opening_cash)}</td>
                        <td class="text-right tabular-nums">{money(shift.cash_sales)}</td>
                        <td class="text-right tabular-nums">{money(shift.non_cash_sales)}</td>
                        <td class="text-right tabular-nums">{money(shift.closing_cash_system)}</td>
                        <td class="text-right tabular-nums">{money(shift.closing_cash_actual)}</td>
                        <td class="text-right font-semibold tabular-nums {shift.cash_difference < 0 ? 'text-red-600' : shift.cash_difference > 0 ? 'text-amber-600' : 'text-emerald-600'}">
                            {money(shift.cash_difference)}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/if}
