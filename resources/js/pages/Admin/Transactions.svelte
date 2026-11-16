<script>
    import { Link, router } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import PeriodFilter from '../../components/PeriodFilter.svelte';
    import StatusBadge from '../../components/StatusBadge.svelte';
    import Pagination from '../../components/Pagination.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import { rupiah, dateTime } from '../../lib/format.js';

    let { orders, period, filters = {}, options } = $props();

    const FILTER_KEYS = ['channel', 'status', 'payment_status', 'payment_method_id', 'cashier_id', 'search'];

    let values = $state(Object.fromEntries(FILTER_KEYS.map((key) => [key, filters?.[key] ?? ''])));

    const periodParams = $derived(
        period.preset === 'custom' ? { period: 'custom', from: period.from, to: period.to } : { period: period.preset },
    );
    const activeFilters = $derived(Object.fromEntries(Object.entries(values).filter(([, v]) => v !== '' && v !== null)));
    const exportQuery = $derived(new URLSearchParams({ ...periodParams, ...activeFilters }).toString());

    function apply(event) {
        event?.preventDefault();
        router.get('/admin/transaksi', { ...periodParams, ...activeFilters }, { preserveState: true, preserveScroll: true });
    }

    function reset() {
        values = Object.fromEntries(FILTER_KEYS.map((key) => [key, '']));
        router.get('/admin/transaksi', periodParams, { preserveState: true });
    }
</script>

<svelte:head><title>Semua Transaksi</title></svelte:head>

<PageHeader title="Semua Transaksi" subtitle="Dine in, take away & online dalam satu daftar">
    {#snippet actions()}
        <a class="btn-secondary btn-sm" href="/admin/transaksi/export/xlsx?{exportQuery}">⬇️ Excel</a>
        <a class="btn-secondary btn-sm" href="/admin/transaksi/export/pdf?{exportQuery}">⬇️ PDF</a>
    {/snippet}
</PageHeader>

<div class="mb-3"><PeriodFilter {period} url="/admin/transaksi" extra={activeFilters} /></div>

<form class="card mb-4 grid gap-2 p-3 sm:grid-cols-3 lg:grid-cols-7" onsubmit={apply}>
    <input class="input lg:col-span-2" placeholder="Cari no. order / nama / WA" bind:value={values.search} aria-label="Cari" />
    <select class="input" bind:value={values.channel} aria-label="Kanal">
        <option value="">Semua kanal</option>
        {#each options.channels as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
    </select>
    <select class="input" bind:value={values.status} aria-label="Status">
        <option value="">Semua status</option>
        {#each options.statuses as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
    </select>
    <select class="input" bind:value={values.payment_status} aria-label="Status bayar">
        <option value="">Semua pembayaran</option>
        {#each options.paymentStatuses as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
    </select>
    <select class="input" bind:value={values.payment_method_id} aria-label="Metode bayar">
        <option value="">Semua metode</option>
        {#each options.paymentMethods as option (option.id)}<option value={String(option.id)}>{option.name}</option>{/each}
    </select>
    <select class="input" bind:value={values.cashier_id} aria-label="Kasir">
        <option value="">Semua kasir</option>
        {#each options.cashiers as option (option.id)}<option value={String(option.id)}>{option.name}</option>{/each}
    </select>
    <div class="flex gap-2 sm:col-span-3 lg:col-span-7 lg:justify-end">
        <button type="button" class="btn-ghost btn-sm" onclick={reset}>Reset</button>
        <button type="submit" class="btn-primary btn-sm">Terapkan filter</button>
    </div>
</form>

{#if orders.data.length === 0}
    <EmptyState title="Tidak ada transaksi" description="Coba ubah periode atau filter." />
{:else}
    <div class="card overflow-x-auto">
        <table class="table min-w-[860px]">
            <thead>
                <tr>
                    <th>No. Order</th><th>Waktu</th><th>Kanal</th><th>Pelanggan</th><th>Status</th>
                    <th>Bayar</th><th>Metode</th><th>Kasir</th><th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                {#each orders.data as order (order.id)}
                    <tr class="hover:bg-stone-50">
                        <td><Link href="/pos/orders/{order.order_number}" class="font-semibold text-brand-700 hover:underline">{order.order_number}</Link></td>
                        <td class="whitespace-nowrap">{dateTime(order.created_at)}</td>
                        <td>{order.channel_label}{#if order.table_number}<span class="text-stone-500"> · Meja {order.table_number}</span>{/if}</td>
                        <td>{order.customer_name ?? '-'}</td>
                        <td><StatusBadge status={order.status} label={order.status_label} /></td>
                        <td><StatusBadge kind="payment" status={order.payment_status} label={order.payment_status_label} /></td>
                        <td>{order.payment_method?.name ?? '-'}</td>
                        <td>{order.cashier?.name ?? '-'}</td>
                        <td class="text-right font-semibold tabular-nums">
                            {rupiah(order.total)}
                            {#if order.discount > 0}<span class="block text-xs font-normal text-stone-500">diskon {rupiah(order.discount)}</span>{/if}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
    <Pagination paginator={orders} />
{/if}
