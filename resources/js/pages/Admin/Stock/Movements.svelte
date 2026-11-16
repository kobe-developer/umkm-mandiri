<script>
    import { Link, router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import PageHeader from '../../../components/PageHeader.svelte';
    import Pagination from '../../../components/Pagination.svelte';
    import EmptyState from '../../../components/EmptyState.svelte';
    import { dateTime } from '../../../lib/format.js';

    let { movements, products = [], types = [], filters = {} } = $props();

    let productId = $state(untrack(() => (filters?.product_id ? String(filters.product_id) : '')));
    let type = $state(untrack(() => filters?.type ?? ''));

    const TYPE_CLASSES = {
        masuk: 'bg-emerald-100 text-emerald-700',
        keluar: 'bg-sky-100 text-sky-700',
        penyesuaian: 'bg-amber-100 text-amber-800',
        retur: 'bg-violet-100 text-violet-700',
    };

    function apply() {
        const params = {};
        if (productId) params.product_id = productId;
        if (type) params.type = type;
        router.get('/admin/stok/riwayat', params, { preserveState: true, preserveScroll: true });
    }
</script>

<svelte:head><title>Riwayat Stok</title></svelte:head>

<PageHeader title="Riwayat Pergerakan Stok">
    {#snippet actions()}
        <Link href="/admin/stok" class="btn-ghost">← Stok</Link>
    {/snippet}
</PageHeader>

<div class="mb-4 flex flex-wrap gap-2">
    <select class="input w-auto" bind:value={productId} onchange={apply} aria-label="Produk">
        <option value="">Semua produk</option>
        {#each products as product (product.id)}<option value={String(product.id)}>{product.name}</option>{/each}
    </select>
    <select class="input w-auto" bind:value={type} onchange={apply} aria-label="Jenis">
        <option value="">Semua jenis</option>
        {#each types as option (option.value)}<option value={option.value}>{option.label}</option>{/each}
    </select>
</div>

{#if movements.data.length === 0}
    <EmptyState title="Belum ada pergerakan stok" />
{:else}
    <div class="card overflow-x-auto">
        <table class="table min-w-[820px]">
            <thead>
                <tr><th>Tanggal</th><th>Produk</th><th>Jenis</th><th class="text-right">Qty</th><th class="text-right">Stok akhir</th><th>Alasan</th><th>User</th><th>Order</th></tr>
            </thead>
            <tbody>
                {#each movements.data as movement (movement.id)}
                    <tr>
                        <td class="whitespace-nowrap">{dateTime(movement.created_at)}</td>
                        <td class="font-medium">{movement.product ?? '-'}</td>
                        <td><span class="rounded-full px-2 py-0.5 text-xs font-semibold {TYPE_CLASSES[movement.type] ?? 'bg-stone-100'}">{movement.type_label}</span></td>
                        <td class="text-right font-semibold tabular-nums {movement.quantity < 0 ? 'text-red-600' : 'text-emerald-600'}">{movement.quantity > 0 ? '+' : ''}{movement.quantity}</td>
                        <td class="text-right tabular-nums">{movement.stock_after}</td>
                        <td class="text-stone-600">{movement.reason ?? '-'}</td>
                        <td>{movement.user ?? 'Sistem'}</td>
                        <td>
                            {#if movement.order_number}<Link href="/pos/orders/{movement.order_number}" class="text-brand-700 hover:underline">{movement.order_number}</Link>{:else}-{/if}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
    <Pagination paginator={movements} />
{/if}
