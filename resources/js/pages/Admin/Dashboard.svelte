<script>
    import { Link } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import PeriodFilter from '../../components/PeriodFilter.svelte';
    import StatCard from '../../components/StatCard.svelte';
    import StatusBadge from '../../components/StatusBadge.svelte';
    import Chart from '../../components/Chart.svelte';
    import ChartCard from '../../components/admin/ChartCard.svelte';
    import RankList from '../../components/admin/RankList.svelte';
    import { rupiah, shortDate, date } from '../../lib/format.js';

    let {
        period, presets, comparison, daily = [], channels = [], paymentMethods = [], categories = [],
        topByQuantity = [], topByRevenue = [], hourly = [], cashiers = [], statusCounts = {}, lowStock = [],
    } = $props();

    const STATUS_ORDER = ['baru', 'dikonfirmasi', 'diproses', 'siap', 'selesai', 'dibatalkan'];

    const current = $derived(comparison.current);
    const change = $derived(comparison.change);
    const periodLabel = $derived(period.from === period.to ? date(period.from) : `${date(period.from)} – ${date(period.to)}`);
    const busyHours = $derived(hourly.filter((h) => h.hour >= 6 || h.transactions > 0));
</script>

<svelte:head><title>Laporan & Tren</title></svelte:head>

<PageHeader title="Laporan & Tren Penjualan" subtitle="Omzet dihitung dari order Selesai + Lunas · {periodLabel}" />

<div class="mb-5">
    <PeriodFilter {period} {presets} url="/admin" />
</div>

{#if lowStock.length > 0}
    <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        <p class="font-semibold">⚠️ Stok menipis ({lowStock.length} produk)</p>
        <p class="mt-1">
            {#each lowStock as product, index (product.id)}
                {product.name} <span class="font-semibold">({product.stock}/{product.stock_minimum})</span>{index < lowStock.length - 1 ? ', ' : ''}
            {/each}
        </p>
        <Link href="/admin/stok" class="mt-2 inline-block font-semibold underline">Kelola stok →</Link>
    </div>
{/if}

<div class="grid gap-3 sm:grid-cols-3">
    <StatCard label="Omzet" value={rupiah(current.revenue)} change={change.revenue} />
    <StatCard label="Jumlah transaksi" value={current.transactions} change={change.transactions} />
    <StatCard label="Rata-rata nilai order" value={rupiah(current.average)} change={change.average} />
</div>

<div class="mt-4 flex flex-wrap gap-2">
    {#each STATUS_ORDER as status (status)}
        <span class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-1.5 text-sm ring-1 ring-stone-200">
            <StatusBadge {status} />
            <span class="font-semibold tabular-nums">{statusCounts[status] ?? 0}</span>
        </span>
    {/each}
</div>

<div class="mt-5 grid gap-4 lg:grid-cols-3">
    <ChartCard title="Omzet harian" class="lg:col-span-2">
        <Chart type="line" labels={daily.map((d) => shortDate(d.date))} datasets={[{ label: 'Omzet', data: daily.map((d) => d.revenue) }]} />
    </ChartCard>

    <ChartCard title="Omzet per kanal">
        <Chart type="doughnut" labels={channels.map((c) => c.label)} datasets={[{ label: 'Omzet', data: channels.map((c) => c.revenue) }]} />
        <ul class="mt-3 space-y-1 text-sm">
            {#each channels as channel (channel.key)}
                <li class="flex justify-between"><span>{channel.label} · {channel.transactions} trx</span><span class="font-semibold tabular-nums">{rupiah(channel.revenue)}</span></li>
            {/each}
        </ul>
    </ChartCard>

    <ChartCard title="Per metode pembayaran">
        {#if paymentMethods.length === 0}
            <p class="py-6 text-center text-sm text-stone-500">Belum ada data</p>
        {:else}
            <Chart type="doughnut" labels={paymentMethods.map((m) => m.label)} datasets={[{ label: 'Omzet', data: paymentMethods.map((m) => m.revenue) }]} height={220} />
        {/if}
    </ChartCard>

    <ChartCard title="Per kategori" subtitle="Subtotal item sebelum diskon">
        {#if categories.length === 0}
            <p class="py-6 text-center text-sm text-stone-500">Belum ada data</p>
        {:else}
            <Chart type="bar" labels={categories.map((c) => c.label)} datasets={[{ label: 'Omzet', data: categories.map((c) => c.revenue) }]} height={220} />
        {/if}
    </ChartCard>

    <ChartCard title="Jam ramai" subtitle="Omzet per jam">
        <Chart type="bar" labels={busyHours.map((h) => `${String(h.hour).padStart(2, '0')}:00`)} datasets={[{ label: 'Omzet', data: busyHours.map((h) => h.revenue) }]} height={220} />
    </ChartCard>

    <ChartCard title="Produk terlaris (qty)">
        <RankList rows={topByQuantity} valueKey="quantity" />
    </ChartCard>

    <ChartCard title="Produk terlaris (omzet)">
        <RankList rows={topByRevenue} valueKey="revenue" />
    </ChartCard>

    <ChartCard title="Performa per kasir">
        {#if cashiers.length === 0}
            <p class="py-6 text-center text-sm text-stone-500">Belum ada data</p>
        {:else}
            <table class="table">
                <thead><tr><th>Kasir</th><th class="text-right">Trx</th><th class="text-right">Omzet</th></tr></thead>
                <tbody>
                    {#each cashiers as cashier (cashier.name)}
                        <tr>
                            <td>{cashier.name}</td>
                            <td class="text-right tabular-nums">{cashier.transactions}</td>
                            <td class="text-right font-semibold tabular-nums">{rupiah(cashier.revenue)}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        {/if}
    </ChartCard>
</div>

<p class="mt-4 text-xs text-stone-500">
    Periode sebelumnya: omzet {rupiah(comparison.previous.revenue)} · {comparison.previous.transactions} transaksi.
</p>
