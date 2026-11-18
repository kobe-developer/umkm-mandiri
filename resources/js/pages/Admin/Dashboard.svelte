<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import { Link } from '@inertiajs/svelte';
    import TriangleAlert from '@lucide/svelte/icons/triangle-exclamation-point';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
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

<PageTitle title="Laporan & Tren" />

<PageHeader eyebrow={periodLabel} title="Laporan & Tren Penjualan" subtitle="Omzet dihitung dari order Selesai + Lunas" />

<div class="mb-6">
    <PeriodFilter {period} {presets} url="/admin" />
</div>

{#if lowStock.length > 0}
    <div class="mb-6 flex gap-3 rounded-card bg-amber-soft p-4 text-sm text-amber-ink">
        <TriangleAlert size={18} class="mt-0.5 shrink-0" aria-hidden="true" />
        <div class="min-w-0">
            <p class="font-semibold">Stok menipis · {lowStock.length} produk</p>
            <p class="mt-1 leading-relaxed">
                {#each lowStock as product, index (product.id)}
                    {product.name} <span class="num font-semibold">({product.stock}/{product.stock_minimum})</span>{index < lowStock.length - 1 ? ', ' : ''}
                {/each}
            </p>
            <Link href="/admin/stok" class="mt-2 inline-flex min-h-9 items-center gap-1 font-semibold underline-offset-4 hover:underline">
                Kelola stok <ArrowRight size={15} />
            </Link>
        </div>
    </div>
{/if}

<div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
    <div class="col-span-2 sm:col-span-1">
        <StatCard featured label="Omzet" value={rupiah(current.revenue)} change={change.revenue} />
    </div>
    <StatCard label="Jumlah transaksi" value={current.transactions} change={change.transactions} />
    <StatCard label="Rata-rata nilai order" value={rupiah(current.average)} change={change.average} />
</div>

<section class="mt-8">
    <h2 class="display mb-3 text-lg text-ink">Status order</h2>
    <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-card border border-line bg-line sm:grid-cols-3 lg:grid-cols-6">
        {#each STATUS_ORDER as status (status)}
            <div class="flex flex-col gap-2 bg-surface p-3.5 sm:p-4">
                <dt><StatusBadge {status} /></dt>
                <dd class="num text-2xl leading-none font-semibold text-ink">{statusCounts[status] ?? 0}</dd>
            </div>
        {/each}
    </dl>
</section>

<section class="mt-8">
    <h2 class="display mb-3 text-lg text-ink">Tren omzet</h2>
    <div class="grid gap-4 lg:grid-cols-3">
        <ChartCard title="Omzet harian" class="lg:col-span-2">
            <Chart type="line" labels={daily.map((d) => shortDate(d.date))} datasets={[{ label: 'Omzet', data: daily.map((d) => d.revenue) }]} />
        </ChartCard>

        <ChartCard title="Omzet per kanal">
            <Chart type="doughnut" labels={channels.map((c) => c.label)} datasets={[{ label: 'Omzet', data: channels.map((c) => c.revenue) }]} />
            <ul class="mt-4 divide-y divide-line text-sm">
                {#each channels as channel (channel.key)}
                    <li class="flex items-baseline justify-between gap-3 py-2 first:pt-0 last:pb-0">
                        <span class="min-w-0 text-ink-soft">{channel.label} <span class="num text-muted">· {channel.transactions} trx</span></span>
                        <span class="num shrink-0 font-semibold text-ink">{rupiah(channel.revenue)}</span>
                    </li>
                {/each}
            </ul>
        </ChartCard>
    </div>
</section>

<section class="mt-8">
    <h2 class="display mb-3 text-lg text-ink">Rincian penjualan</h2>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <ChartCard title="Per metode pembayaran">
            {#if paymentMethods.length === 0}
                <p class="py-6 text-center text-sm text-muted">Belum ada data</p>
            {:else}
                <Chart type="doughnut" labels={paymentMethods.map((m) => m.label)} datasets={[{ label: 'Omzet', data: paymentMethods.map((m) => m.revenue) }]} height={220} />
            {/if}
        </ChartCard>

        <ChartCard title="Per kategori" subtitle="Subtotal item sebelum diskon">
            {#if categories.length === 0}
                <p class="py-6 text-center text-sm text-muted">Belum ada data</p>
            {:else}
                <Chart type="bar" labels={categories.map((c) => c.label)} datasets={[{ label: 'Omzet', data: categories.map((c) => c.revenue) }]} height={220} />
            {/if}
        </ChartCard>

        <ChartCard title="Jam ramai" subtitle="Omzet per jam" class="md:col-span-2 lg:col-span-1">
            <Chart type="bar" labels={busyHours.map((h) => `${String(h.hour).padStart(2, '0')}:00`)} datasets={[{ label: 'Omzet', data: busyHours.map((h) => h.revenue) }]} height={220} />
        </ChartCard>
    </div>
</section>

<section class="mt-8">
    <h2 class="display mb-3 text-lg text-ink">Produk & kasir</h2>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <ChartCard title="Terlaris menurut porsi">
            <RankList rows={topByQuantity} valueKey="quantity" />
        </ChartCard>

        <ChartCard title="Terlaris menurut omzet">
            <RankList rows={topByRevenue} valueKey="revenue" />
        </ChartCard>

        <ChartCard title="Performa per kasir" class="md:col-span-2 lg:col-span-1">
            {#if cashiers.length === 0}
                <p class="py-6 text-center text-sm text-muted">Belum ada data</p>
            {:else}
                <ul class="divide-y divide-line text-sm">
                    {#each cashiers as cashier (cashier.name)}
                        <li class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-ink">{cashier.name}</p>
                                <p class="num text-xs text-muted">{cashier.transactions} transaksi</p>
                            </div>
                            <span class="num shrink-0 font-semibold text-ink">{rupiah(cashier.revenue)}</span>
                        </li>
                    {/each}
                </ul>
            {/if}
        </ChartCard>
    </div>
</section>

<p class="mt-8 border-t border-line pt-4 text-xs text-muted">
    Periode sebelumnya: omzet <span class="num">{rupiah(comparison.previous.revenue)}</span> · <span class="num">{comparison.previous.transactions}</span> transaksi.
</p>
