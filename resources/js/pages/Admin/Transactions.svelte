<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import { Link, router } from '@inertiajs/svelte';
    import Download from '@lucide/svelte/icons/download';
    import Search from '@lucide/svelte/icons/search';
    import ReceiptText from '@lucide/svelte/icons/receipt-text';
    import PageHeader from '../../components/PageHeader.svelte';
    import PeriodFilter from '../../components/PeriodFilter.svelte';
    import StatusBadge from '../../components/StatusBadge.svelte';
    import Pagination from '../../components/Pagination.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import SearchSelect from '../../components/SearchSelect.svelte';
    import { rupiah, dateTime } from '../../lib/format.js';

    let { orders, period, filters = {}, options } = $props();

    const FILTER_KEYS = ['channel', 'status', 'payment_status', 'payment_method_id', 'cashier_id', 'search'];

    let values = $state(Object.fromEntries(FILTER_KEYS.map((key) => [key, filters?.[key] ?? ''])));

    const periodParams = $derived(
        period.preset === 'custom' ? { period: 'custom', from: period.from, to: period.to } : { period: period.preset },
    );
    /** Opsi filter dengan pilihan "Semua …" (nilai kosong) di paling atas. */
    const withAll = (label, list) => [{ value: '', label }, ...list];
    const selectOptions = $derived({
        channels: withAll('Semua kanal', options.channels),
        statuses: withAll('Semua status', options.statuses),
        paymentStatuses: withAll('Semua pembayaran', options.paymentStatuses),
        paymentMethods: withAll('Semua metode', options.paymentMethods.map((m) => ({ value: String(m.id), label: m.name }))),
        cashiers: withAll('Semua kasir', options.cashiers.map((c) => ({ value: String(c.id), label: c.name }))),
    });
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

<PageTitle title="Semua Transaksi" />

<PageHeader title="Semua Transaksi" subtitle="Dine in, take away & online dalam satu daftar">
    {#snippet actions()}
        <a class="btn-secondary btn-sm" href="/admin/transaksi/export/xlsx?{exportQuery}"><Download size={15} />Excel</a>
        <a class="btn-secondary btn-sm" href="/admin/transaksi/export/pdf?{exportQuery}"><Download size={15} />PDF</a>
    {/snippet}
</PageHeader>

<div class="mb-4"><PeriodFilter {period} url="/admin/transaksi" extra={activeFilters} /></div>

<form class="card mb-6 grid grid-cols-2 gap-2 p-3 sm:grid-cols-3 sm:p-4 lg:grid-cols-5" onsubmit={apply}>
    <div class="relative col-span-2 sm:col-span-3 lg:col-span-5">
        <Search size={17} class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-faint" aria-hidden="true" />
        <input class="input pl-10" type="search" placeholder="Cari no. order / nama / WA" bind:value={values.search} aria-label="Cari" />
    </div>
    <SearchSelect options={selectOptions.channels} bind:value={values.channel} ariaLabel="Kanal" />
    <SearchSelect options={selectOptions.statuses} bind:value={values.status} ariaLabel="Status" />
    <SearchSelect options={selectOptions.paymentStatuses} bind:value={values.payment_status} ariaLabel="Status bayar" />
    <SearchSelect options={selectOptions.paymentMethods} bind:value={values.payment_method_id} ariaLabel="Metode bayar" searchPlaceholder="Cari metode…" />
    <SearchSelect class="col-span-2 sm:col-span-1" options={selectOptions.cashiers} bind:value={values.cashier_id} ariaLabel="Kasir" searchPlaceholder="Cari kasir…" />
    <div class="col-span-2 flex gap-2 pt-1 sm:col-span-3 sm:justify-end lg:col-span-5 [&>*]:flex-1 sm:[&>*]:flex-none">
        <button type="button" class="btn-ghost" onclick={reset}>Reset</button>
        <button type="submit" class="btn-primary">Terapkan</button>
    </div>
</form>

{#if orders.data.length === 0}
    <EmptyState icon={ReceiptText} title="Tidak ada transaksi" description="Coba ubah periode atau filter." />
{:else}
    <!-- Mobile & tablet: daftar ringkas -->
    <ul class="card divide-y divide-line overflow-hidden xl:hidden">
        {#each orders.data as order (order.id)}
            <li>
                <Link href="/pos/orders/{order.order_number}" class="block px-4 py-3.5 transition-colors hover:bg-paper/60 active:bg-paper">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="num font-semibold text-ink">{order.order_number}</p>
                            <p class="mt-0.5 truncate text-xs text-muted">
                                {dateTime(order.created_at)} · {order.channel_label}{#if order.table_number} · Meja {order.table_number}{/if}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="num font-semibold text-ink">{rupiah(order.total)}</p>
                            {#if order.discount > 0}<p class="num text-xs text-muted">diskon {rupiah(order.discount)}</p>{/if}
                        </div>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <StatusBadge status={order.status} label={order.status_label} />
                        <StatusBadge kind="payment" status={order.payment_status} label={order.payment_status_label} />
                        <span class="min-w-0 truncate text-xs text-muted">
                            {order.payment_method?.name ?? '-'}{#if order.customer_name} · {order.customer_name}{/if}{#if order.cashier?.name} · {order.cashier.name}{/if}
                        </span>
                    </div>
                </Link>
            </li>
        {/each}
    </ul>

    <!-- Layar lebar: tabel lengkap -->
    <div class="card hidden overflow-x-auto xl:block">
        <table class="table">
            <thead>
                <tr>
                    <th>No. Order</th><th>Waktu</th><th>Kanal</th><th>Pelanggan</th><th>Status</th>
                    <th>Bayar</th><th>Metode</th><th>Kasir</th><th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                {#each orders.data as order (order.id)}
                    <tr>
                        <td><Link href="/pos/orders/{order.order_number}" class="num font-semibold text-ink underline-offset-4 hover:text-brand-700 hover:underline">{order.order_number}</Link></td>
                        <td class="whitespace-nowrap text-ink-soft">{dateTime(order.created_at)}</td>
                        <td class="whitespace-nowrap">{order.channel_label}{#if order.table_number}<span class="text-muted"> · Meja {order.table_number}</span>{/if}</td>
                        <td>{order.customer_name ?? '-'}</td>
                        <td><StatusBadge status={order.status} label={order.status_label} /></td>
                        <td><StatusBadge kind="payment" status={order.payment_status} label={order.payment_status_label} /></td>
                        <td class="text-ink-soft">{order.payment_method?.name ?? '-'}</td>
                        <td class="text-ink-soft">{order.cashier?.name ?? '-'}</td>
                        <td class="num text-right font-semibold whitespace-nowrap">
                            {rupiah(order.total)}
                            {#if order.discount > 0}<span class="block text-xs font-normal text-muted">diskon {rupiah(order.discount)}</span>{/if}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
    <Pagination paginator={orders} />
{/if}
