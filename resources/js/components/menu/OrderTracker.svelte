<script>
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import ActiveOrderCard from './ActiveOrderCard.svelte';
    import { liveOrderUpdates } from '../../lib/liveOrders.svelte.js';

    /** "Pesanan kamu" di halaman menu: hanya order yang masih berjalan, diperbarui realtime. */
    let { orders = [] } = $props();

    liveOrderUpdates(() => orders, 'trackedOrders');
</script>

{#if orders.length > 0}
    <section class="mx-auto max-w-2xl px-5 pb-4" aria-labelledby="pesanan-kamu">
        <div class="mb-2 flex items-center justify-between gap-3">
            <h2 id="pesanan-kamu" class="text-xs font-semibold tracking-wider text-muted uppercase">Pesanan kamu</h2>
            <Link href="/menu/pesanan" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline">Riwayat<ArrowRight size={13} /></Link>
        </div>
        <ul class="space-y-2" aria-live="polite">
            {#each orders as order (order.order_number)}
                <li><ActiveOrderCard {order} /></li>
            {/each}
        </ul>
    </section>
{/if}
