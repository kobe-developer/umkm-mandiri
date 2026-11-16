<script>
    import { Link, router } from '@inertiajs/svelte';
    import PageHeader from '../../../components/PageHeader.svelte';
    import StatusBadge from '../../../components/StatusBadge.svelte';
    import Pagination from '../../../components/Pagination.svelte';
    import EmptyState from '../../../components/EmptyState.svelte';
    import { rupiah, dateTime } from '../../../lib/format.js';

    let { orders, filters } = $props();

    const SCOPES = [
        { value: 'shift', label: 'Shift berjalan' },
        { value: 'today', label: 'Hari ini' },
        { value: 'unpaid', label: 'Belum dibayar' },
        { value: 'active', label: 'Aktif' },
    ];

    // Nilai awal saja; selanjutnya dikendalikan input pencarian
    // svelte-ignore state_referenced_locally
    let search = $state(filters.search ?? '');

    function submitSearch(event) {
        event.preventDefault();
        router.get('/pos/orders', { scope: filters.scope, search: search || undefined }, { preserveState: true });
    }
</script>

<svelte:head><title>Transaksi</title></svelte:head>

<PageHeader title="Transaksi" subtitle="Riwayat order & daftar belum dibayar.">
    {#snippet actions()}
        <Link href="/pos/kasir" class="btn-primary">+ Order baru</Link>
    {/snippet}
</PageHeader>

<div class="mb-4 flex flex-wrap items-center gap-2">
    <div class="-mx-1 flex gap-1 overflow-x-auto px-1">
        {#each SCOPES as scope (scope.value)}
            <Link
                href={`/pos/orders?scope=${scope.value}`}
                class="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium {filters.scope === scope.value ? 'bg-stone-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'}"
            >{scope.label}</Link>
        {/each}
    </div>
    <form class="ml-auto flex w-full gap-2 sm:w-auto" onsubmit={submitSearch}>
        <input type="search" class="input sm:w-56" placeholder="No. order / nama / meja" bind:value={search} aria-label="Cari order" />
        <button class="btn-secondary" type="submit">Cari</button>
    </form>
</div>

{#if orders.data.length === 0}
    <EmptyState title="Belum ada transaksi" description="Order yang sesuai filter akan muncul di sini." />
{:else}
    <ul class="space-y-2">
        {#each orders.data as order (order.id)}
            <li>
                <Link href={`/pos/orders/${order.order_number}`} class="card flex flex-wrap items-center gap-x-4 gap-y-1 p-3 hover:border-stone-300">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">
                            {order.order_number}
                            <span class="ml-1 text-xs font-medium text-stone-500">{order.channel_label}{order.table_number ? ` · Meja ${order.table_number}` : ''}</span>
                        </p>
                        <p class="truncate text-xs text-stone-500">
                            {dateTime(order.created_at)} · {order.items_count} item{order.customer_name ? ` · ${order.customer_name}` : ''}{order.payment_method ? ` · ${order.payment_method.name}` : ''}
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <StatusBadge status={order.status} label={order.status_label} />
                        <StatusBadge kind="payment" status={order.payment_status} label={order.payment_status_label} />
                    </div>
                    <p class="w-24 text-right font-bold tabular-nums">{rupiah(order.total)}</p>
                </Link>
            </li>
        {/each}
    </ul>
    <Pagination paginator={orders} />
{/if}
