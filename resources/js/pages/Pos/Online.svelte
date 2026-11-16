<script>
    import { Link, page } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import Pagination from '../../components/Pagination.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import OnlineOrderCard from '../../components/pos/OnlineOrderCard.svelte';
    import ErrorSummary from '../../components/pos/ErrorSummary.svelte';

    let { orders, status, statuses, counts } = $props();
</script>

<svelte:head><title>{counts.baru ? `(${counts.baru}) ` : ''}Order Online</title></svelte:head>

<PageHeader title="Order Online" subtitle="Konfirmasi order baru, lalu proses sampai selesai. Halaman ini diperbarui otomatis." />

<ErrorSummary errors={page.props.errors} />

<div class="-mx-1 mb-4 flex gap-1.5 overflow-x-auto px-1 pb-1">
    {#each statuses as item (item.value)}
        <Link
            href={`/pos/online?status=${item.value}`}
            preserveScroll
            class="flex shrink-0 items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-medium {status === item.value ? 'bg-stone-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'}"
        >
            {item.label}
            {#if counts[item.value]}
                <span class="rounded-full px-1.5 text-xs font-bold {item.value === 'baru' ? 'bg-brand-600 text-white' : status === item.value ? 'bg-white/20' : 'bg-stone-100'}">{counts[item.value]}</span>
            {/if}
        </Link>
    {/each}
</div>

{#if orders.data.length === 0}
    <EmptyState title="Tidak ada order" description={status === 'baru' ? 'Order online baru akan muncul di sini dengan bunyi notifikasi.' : 'Belum ada order dengan status ini.'} />
{:else}
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        {#each orders.data as order (order.id)}
            <OnlineOrderCard {order} />
        {/each}
    </div>
    <Pagination paginator={orders} />
{/if}
