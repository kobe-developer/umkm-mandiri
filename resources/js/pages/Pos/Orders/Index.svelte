<script>
    import { Link, router } from '@inertiajs/svelte';
    import PageHeader from '../../../components/PageHeader.svelte';
    import StatusBadge from '../../../components/StatusBadge.svelte';
    import Pagination from '../../../components/Pagination.svelte';
    import EmptyState from '../../../components/EmptyState.svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Search from '@lucide/svelte/icons/search';
    import ScrollText from '@lucide/svelte/icons/scroll-text';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
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
        <Link href="/pos/kasir" class="btn-primary"><Plus size={17} />Order baru</Link>
    {/snippet}
</PageHeader>

<div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center">
    <div class="scroll-x -mx-4 gap-2 px-4 sm:mx-0 sm:px-0">
        {#each SCOPES as scope (scope.value)}
            <Link href={`/pos/orders?scope=${scope.value}`} class="chip {filters.scope === scope.value ? 'chip-active' : ''}">{scope.label}</Link>
        {/each}
    </div>
    <form class="relative md:ml-auto md:w-72" onsubmit={submitSearch} role="search">
        <label for="cari-order" class="sr-only">Cari order</label>
        <Search size={18} class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-faint" />
        <input id="cari-order" type="search" class="input rounded-full pl-11" placeholder="No. order / nama / meja" bind:value={search} enterkeyhint="search" />
    </form>
</div>

{#if orders.data.length === 0}
    <EmptyState icon={ScrollText} title="Belum ada transaksi" description="Order yang sesuai filter akan muncul di sini." />
{:else}
    <ul class="card divide-y divide-line overflow-hidden">
        {#each orders.data as order (order.id)}
            <li>
                <Link href={`/pos/orders/${order.order_number}`} class="group flex items-center gap-3 px-4 py-3.5 transition hover:bg-paper/70 sm:px-5">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <p class="num font-semibold text-ink">{order.order_number}</p>
                            <span class="text-xs text-muted">{order.channel_label}{order.table_number ? ` · Meja ${order.table_number}` : ''}</span>
                        </div>
                        <p class="mt-0.5 truncate text-xs text-muted">
                            {dateTime(order.created_at)} · {order.items_count} item{order.customer_name ? ` · ${order.customer_name}` : ''}{order.payment_method ? ` · ${order.payment_method.name}` : ''}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <StatusBadge status={order.status} label={order.status_label} />
                            <StatusBadge kind="payment" status={order.payment_status} label={order.payment_status_label} />
                        </div>
                    </div>
                    <p class="num shrink-0 text-right font-semibold text-ink">{rupiah(order.total)}</p>
                    <ChevronRight size={18} class="shrink-0 text-faint transition group-hover:translate-x-0.5 group-hover:text-ink-soft" />
                </Link>
            </li>
        {/each}
    </ul>
    <Pagination paginator={orders} />
{/if}
