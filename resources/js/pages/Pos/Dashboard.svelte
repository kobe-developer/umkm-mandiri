<script>
    import { Link, page } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import StatCard from '../../components/StatCard.svelte';
    import StatusBadge from '../../components/StatusBadge.svelte';
    import Chart from '../../components/Chart.svelte';
    import Bike from '@lucide/svelte/icons/bike';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import TriangleAlert from '@lucide/svelte/icons/triangle-alert';
    import { rupiah, shortDate } from '../../lib/format.js';

    let { period, summary, channels, topProducts, daily, statusCounts, lowStock } = $props();

    const showRevenue = $derived(summary.revenue !== undefined);
    const STATUS_ORDER = ['baru', 'dikonfirmasi', 'diproses', 'siap', 'selesai', 'dibatalkan'];
    const pending = $derived(page.props.pos?.pending_online_count ?? 0);
</script>

<svelte:head><title>Dashboard Kasir</title></svelte:head>

<PageHeader title="Ringkasan" eyebrow={period.preset === '7d' ? '7 hari terakhir' : 'Hari ini'}>
    {#snippet actions()}
        <div class="segmented">
            <Link href="/pos" class="segment {period.preset !== '7d' ? 'segment-active' : ''}">Hari ini</Link>
            <Link href="/pos?period=7d" class="segment {period.preset === '7d' ? 'segment-active' : ''}">7 hari</Link>
        </div>
    {/snippet}
</PageHeader>

{#if pending > 0}
    <Link href="/pos/online" class="mb-5 flex items-center gap-3 rounded-card bg-brand-600 p-4 text-white shadow-float transition hover:bg-brand-700">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/15"><Bike size={22} /></span>
        <span class="min-w-0 flex-1">
            <span class="block font-semibold"><span class="num">{pending}</span> order online menunggu</span>
            <span class="block text-sm text-white/75">Konfirmasi dalam 5 menit</span>
        </span>
        <ArrowRight size={20} />
    </Link>
{/if}

<div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
    {#if showRevenue}<div class="col-span-2 lg:col-span-1"><StatCard featured label="Omzet" value={rupiah(summary.revenue)} hint="Order selesai & lunas" /></div>{/if}
    <StatCard label="Transaksi" value={summary.transactions} hint="Selesai & lunas" />
    {#if summary.average !== undefined}<StatCard label="Rata-rata order" value={rupiah(summary.average)} />{/if}
</div>

{#if lowStock.length > 0}
    <section class="mt-3 rounded-card bg-amber-soft p-4 sm:p-5">
        <h2 class="flex items-center gap-2 text-sm font-semibold text-amber-ink"><TriangleAlert size={16} />Stok menipis</h2>
        <ul class="mt-2 grid gap-x-6 text-sm sm:grid-cols-2">
            {#each lowStock as product (product.id)}
                <li class="flex justify-between gap-3 border-t border-amber-ink/10 py-2 first:border-t-0 sm:[&:nth-child(2)]:border-t-0">
                    <span class="text-ink">{product.name}</span>
                    <span class="num font-semibold {product.stock <= 0 ? 'text-brand-600' : 'text-amber-ink'}">{product.stock} <span class="font-normal opacity-70">/ min {product.stock_minimum}</span></span>
                </li>
            {/each}
        </ul>
    </section>
{/if}

<div class="mt-3 grid gap-3 lg:grid-cols-2">
    <section class="card p-4 sm:p-5">
        <h2 class="font-semibold text-ink">Order per kanal</h2>
        <ul class="mt-3 divide-y divide-line">
            {#each channels as channel (channel.key)}
                <li class="flex items-center justify-between py-2.5 text-sm">
                    <span class="text-ink-soft">{channel.label}</span>
                    <span class="num text-right">
                        <span class="font-semibold text-ink">{channel.transactions}</span>
                        {#if channel.revenue !== undefined}<span class="ml-3 inline-block min-w-24 text-muted">{rupiah(channel.revenue)}</span>{/if}
                    </span>
                </li>
            {/each}
        </ul>
        <h3 class="mt-5 mb-2.5 text-[13px] font-medium text-muted">Status order · semua kanal</h3>
        <div class="flex flex-wrap gap-x-3 gap-y-2">
            {#each STATUS_ORDER as status (status)}
                {#if statusCounts[status]}
                    <span class="inline-flex items-center gap-1.5"><StatusBadge {status} /><span class="num text-sm font-semibold text-ink">{statusCounts[status]}</span></span>
                {/if}
            {/each}
            {#if Object.keys(statusCounts).length === 0}<p class="text-sm text-muted">Belum ada order.</p>{/if}
        </div>
    </section>

    <section class="card p-4 sm:p-5">
        <h2 class="font-semibold text-ink">Produk terlaris</h2>
        {#if topProducts.length === 0}
            <p class="py-8 text-center text-sm text-muted">Belum ada penjualan. Order yang selesai & lunas akan muncul di sini.</p>
        {:else}
            <ol class="mt-3 divide-y divide-line">
                {#each topProducts as product, index (product.name)}
                    <li class="flex items-center gap-3 py-2.5 text-sm">
                        <span class="display num w-5 shrink-0 text-base {index === 0 ? 'text-brand-600' : 'text-faint'}">{index + 1}</span>
                        <span class="flex-1 truncate text-ink">{product.name}</span>
                        <span class="num font-semibold text-ink">{product.quantity}×</span>
                        {#if product.revenue !== undefined}<span class="num w-24 text-right text-muted">{rupiah(product.revenue)}</span>{/if}
                    </li>
                {/each}
            </ol>
        {/if}
    </section>
</div>

{#if daily.length > 0}
    <section class="card mt-3 p-4 sm:p-5">
        <h2 class="mb-4 font-semibold text-ink">{showRevenue ? 'Omzet' : 'Transaksi'} 7 hari terakhir</h2>
        <Chart
            type="bar"
            labels={daily.map((d) => shortDate(d.date))}
            money={showRevenue}
            height={220}
            datasets={[{ label: showRevenue ? 'Omzet' : 'Transaksi', data: daily.map((d) => (showRevenue ? d.revenue : d.transactions)) }]}
        />
    </section>
{/if}
