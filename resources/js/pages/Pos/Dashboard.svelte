<script>
    import { Link, page } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import StatCard from '../../components/StatCard.svelte';
    import StatusBadge from '../../components/StatusBadge.svelte';
    import Chart from '../../components/Chart.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import { rupiah, shortDate } from '../../lib/format.js';

    let { period, summary, channels, topProducts, daily, statusCounts, lowStock } = $props();

    const showRevenue = $derived(summary.revenue !== undefined);
    const STATUS_ORDER = ['baru', 'dikonfirmasi', 'diproses', 'siap', 'selesai', 'dibatalkan'];
    const pending = $derived(page.props.pos?.pending_online_count ?? 0);
</script>

<svelte:head><title>Dashboard Kasir</title></svelte:head>

<PageHeader title="Dashboard" subtitle={period.preset === '7d' ? '7 hari terakhir' : 'Hari ini'}>
    {#snippet actions()}
        <div class="flex rounded-xl bg-stone-100 p-1">
            <Link href="/pos" class="rounded-lg px-3 py-1.5 text-sm font-medium {period.preset !== '7d' ? 'bg-white shadow-sm' : 'text-stone-600'}">Hari ini</Link>
            <Link href="/pos?period=7d" class="rounded-lg px-3 py-1.5 text-sm font-medium {period.preset === '7d' ? 'bg-white shadow-sm' : 'text-stone-600'}">7 hari</Link>
        </div>
    {/snippet}
</PageHeader>

{#if pending > 0}
    <Link href="/pos/online" class="mb-4 flex items-center justify-between rounded-2xl bg-amber-100 px-4 py-3 text-amber-900">
        <span class="font-semibold">🛵 {pending} order online menunggu konfirmasi</span>
        <span class="text-sm font-bold">Lihat →</span>
    </Link>
{/if}

<div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
    {#if showRevenue}<StatCard label="Omzet" value={rupiah(summary.revenue)} hint="Order selesai & lunas" />{/if}
    <StatCard label="Transaksi" value={summary.transactions} hint="Order selesai & lunas" />
    {#if summary.average !== undefined}<StatCard label="Rata-rata order" value={rupiah(summary.average)} />{/if}
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <section class="card p-4">
        <h2 class="mb-3 font-semibold">Order per kanal</h2>
        <ul class="space-y-2">
            {#each channels as channel (channel.key)}
                <li class="flex items-center justify-between text-sm">
                    <span>{channel.label}</span>
                    <span class="font-semibold tabular-nums">
                        {channel.transactions} order{#if channel.revenue !== undefined}<span class="ml-2 font-normal text-stone-500">{rupiah(channel.revenue)}</span>{/if}
                    </span>
                </li>
            {/each}
        </ul>
        <h3 class="mt-4 mb-2 text-sm font-semibold text-stone-600">Status order (semua kanal)</h3>
        <div class="flex flex-wrap gap-2">
            {#each STATUS_ORDER as status (status)}
                {#if statusCounts[status]}
                    <span class="inline-flex items-center gap-1"><StatusBadge {status} /><span class="text-sm font-semibold">{statusCounts[status]}</span></span>
                {/if}
            {/each}
            {#if Object.keys(statusCounts).length === 0}<p class="text-sm text-stone-500">Belum ada order.</p>{/if}
        </div>
    </section>

    <section class="card p-4">
        <h2 class="mb-3 font-semibold">Produk terlaris</h2>
        {#if topProducts.length === 0}
            <EmptyState title="Belum ada penjualan" description="Order yang selesai & lunas akan muncul di sini." />
        {:else}
            <ol class="space-y-2">
                {#each topProducts as product, index (product.name)}
                    <li class="flex items-center gap-3 text-sm">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-stone-100 text-xs font-bold">{index + 1}</span>
                        <span class="flex-1 truncate">{product.name}</span>
                        <span class="font-semibold tabular-nums">{product.quantity}×</span>
                        {#if product.revenue !== undefined}<span class="w-24 text-right text-stone-500 tabular-nums">{rupiah(product.revenue)}</span>{/if}
                    </li>
                {/each}
            </ol>
        {/if}
    </section>
</div>

{#if daily.length > 0}
    <section class="card mt-4 p-4">
        <h2 class="mb-3 font-semibold">{showRevenue ? 'Omzet' : 'Transaksi'} 7 hari terakhir</h2>
        <Chart
            type="bar"
            labels={daily.map((d) => shortDate(d.date))}
            money={showRevenue}
            datasets={[{ label: showRevenue ? 'Omzet' : 'Transaksi', data: daily.map((d) => (showRevenue ? d.revenue : d.transactions)) }]}
        />
    </section>
{/if}

{#if lowStock.length > 0}
    <section class="card mt-4 border-amber-200 bg-amber-50 p-4">
        <h2 class="mb-2 font-semibold text-amber-900">⚠️ Stok menipis</h2>
        <ul class="grid gap-1 text-sm sm:grid-cols-2">
            {#each lowStock as product (product.id)}
                <li class="flex justify-between"><span>{product.name}</span><span class="font-semibold tabular-nums {product.stock <= 0 ? 'text-red-600' : 'text-amber-800'}">{product.stock} (min {product.stock_minimum})</span></li>
            {/each}
        </ul>
    </section>
{/if}
