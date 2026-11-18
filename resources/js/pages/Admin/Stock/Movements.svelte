<script>
    import PageTitle from '../../../components/PageTitle.svelte';
    import { Link, router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import History from '@lucide/svelte/icons/rotate-ccw-clock';
    import PageHeader from '../../../components/PageHeader.svelte';
    import Pagination from '../../../components/Pagination.svelte';
    import EmptyState from '../../../components/EmptyState.svelte';
    import SearchSelect from '../../../components/SearchSelect.svelte';
    import { dateTime } from '../../../lib/format.js';

    let { movements, products = [], types = [], filters = {} } = $props();

    let productId = $state(untrack(() => (filters?.product_id ? String(filters.product_id) : '')));
    let type = $state(untrack(() => filters?.type ?? ''));

    const TYPE_CLASSES = {
        masuk: 'bg-leaf-50 text-leaf-700',
        keluar: 'bg-paper-deep text-ink-soft',
        penyesuaian: 'bg-amber-soft text-amber-ink',
        retur: 'border border-line-strong text-ink-soft',
    };

    const productOptions = $derived([{ value: '', label: 'Semua produk' }, ...products.map((p) => ({ value: String(p.id), label: p.name }))]);
    const typeOptions = $derived([{ value: '', label: 'Semua jenis' }, ...types]);

    function apply() {
        const params = {};
        if (productId) params.product_id = productId;
        if (type) params.type = type;
        router.get('/admin/stok/riwayat', params, { preserveState: true, preserveScroll: true });
    }
</script>

<PageTitle title="Riwayat Stok" />

<PageHeader title="Riwayat Pergerakan Stok">
    {#snippet actions()}
        <Link href="/admin/stok" class="btn-ghost -ml-2 sm:ml-0"><ArrowLeft size={17} />Stok</Link>
    {/snippet}
</PageHeader>

<div class="mb-6 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
    <SearchSelect class="sm:w-64" options={productOptions} bind:value={productId} onchange={apply} ariaLabel="Produk" searchPlaceholder="Cari nama produk…" emptyText="Produk tidak ditemukan" />
    <SearchSelect class="sm:w-48" options={typeOptions} bind:value={type} onchange={apply} ariaLabel="Jenis" />
</div>

{#snippet typeBadge(movement)}
    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap {TYPE_CLASSES[movement.type] ?? 'bg-paper-deep text-ink-soft'}">{movement.type_label}</span>
{/snippet}

{#snippet quantity(movement)}
    <span class="num font-semibold {movement.quantity < 0 ? 'text-brand-600' : 'text-leaf-600'}">{movement.quantity > 0 ? '+' : ''}{movement.quantity}</span>
{/snippet}

{#snippet orderLink(movement)}
    {#if movement.order_number}
        <Link href="/pos/orders/{movement.order_number}" class="num font-medium text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">{movement.order_number}</Link>
    {:else}-{/if}
{/snippet}

{#if movements.data.length === 0}
    <EmptyState icon={History} title="Belum ada pergerakan stok" />
{:else}
    <!-- Mobile & tablet: daftar -->
    <ul class="card divide-y divide-line xl:hidden">
        {#each movements.data as movement (movement.id)}
            <li class="px-4 py-3.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <p class="font-medium text-ink">{movement.product ?? '-'}</p>
                            {@render typeBadge(movement)}
                        </div>
                        <p class="mt-0.5 text-xs text-muted">{dateTime(movement.created_at)} · {movement.user ?? 'Sistem'}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-lg leading-tight">{@render quantity(movement)}</p>
                        <p class="text-xs text-muted">stok akhir <span class="num">{movement.stock_after}</span></p>
                    </div>
                </div>
                {#if movement.reason || movement.order_number}
                    <p class="mt-1.5 text-sm text-ink-soft">
                        {movement.reason ?? ''}{#if movement.reason && movement.order_number}<span class="text-faint"> · </span>{/if}{#if movement.order_number}{@render orderLink(movement)}{/if}
                    </p>
                {/if}
            </li>
        {/each}
    </ul>

    <!-- Layar lebar: tabel -->
    <div class="card hidden overflow-x-auto xl:block">
        <table class="table">
            <thead>
                <tr><th>Tanggal</th><th>Produk</th><th>Jenis</th><th class="text-right">Qty</th><th class="text-right">Stok akhir</th><th>Alasan</th><th>User</th><th>Order</th></tr>
            </thead>
            <tbody>
                {#each movements.data as movement (movement.id)}
                    <tr>
                        <td class="whitespace-nowrap text-ink-soft">{dateTime(movement.created_at)}</td>
                        <td class="font-medium text-ink">{movement.product ?? '-'}</td>
                        <td>{@render typeBadge(movement)}</td>
                        <td class="text-right">{@render quantity(movement)}</td>
                        <td class="num text-right">{movement.stock_after}</td>
                        <td class="text-ink-soft">{movement.reason ?? '-'}</td>
                        <td>{movement.user ?? 'Sistem'}</td>
                        <td>{@render orderLink(movement)}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
    <Pagination paginator={movements} />
{/if}
