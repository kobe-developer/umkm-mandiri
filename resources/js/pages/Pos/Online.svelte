<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import { Link, page } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import Pagination from '../../components/Pagination.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import OnlineOrderCard from '../../components/pos/OnlineOrderCard.svelte';
    import ErrorSummary from '../../components/pos/ErrorSummary.svelte';
    import Bike from '@lucide/svelte/icons/bike';

    let { orders, status, statuses, counts } = $props();
</script>

<PageTitle title={`${counts.baru ? `(${counts.baru}) ` : ''}Order Online`} />

<PageHeader title="Order Online" subtitle="Konfirmasi order baru, lalu proses sampai selesai. Diperbarui otomatis." />

<ErrorSummary errors={page.props.errors} />

<div class="scroll-x -mx-4 mb-5 gap-2 px-4 sm:mx-0 sm:px-0">
    {#each statuses as item (item.value)}
        {@const active = status === item.value}
        <Link href={`/pos/online?status=${item.value}`} preserveScroll class="chip {active ? 'chip-active' : ''}">
            {item.label}
            {#if counts[item.value]}
                <span class="num min-w-5 rounded-full px-1.5 text-center text-xs leading-5 font-bold {item.value === 'baru' ? 'bg-brand-600 text-white' : active ? 'bg-white/15' : 'bg-paper-deep text-ink-soft'}">{counts[item.value]}</span>
            {/if}
        </Link>
    {/each}
</div>

{#if orders.data.length === 0}
    <EmptyState icon={Bike} title="Tidak ada order" description={status === 'baru' ? 'Order online baru akan muncul di sini dengan bunyi notifikasi.' : 'Belum ada order dengan status ini.'} />
{:else}
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        {#each orders.data as order (order.id)}
            <OnlineOrderCard {order} />
        {/each}
    </div>
    <Pagination paginator={orders} />
{/if}
