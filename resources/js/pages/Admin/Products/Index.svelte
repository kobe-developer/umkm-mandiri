<script>
    import { Link, router, useForm } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import PageHeader from '../../../components/PageHeader.svelte';
    import EmptyState from '../../../components/EmptyState.svelte';
    import Modal from '../../../components/Modal.svelte';
    import { rupiah } from '../../../lib/format.js';

    let { products = [], categories = [], filters = {} } = $props();

    let search = $state(untrack(() => filters?.search ?? ''));
    let categoryId = $state(untrack(() => (filters?.category_id ? String(filters.category_id) : '')));
    let deleting = $state(null);
    let showDelete = $state(false);
    const deleteForm = useForm({});

    function applyFilters(event) {
        event?.preventDefault();
        const params = {};
        if (search) params.search = search;
        if (categoryId) params.category_id = categoryId;
        router.get('/admin/produk', params, { preserveState: true, preserveScroll: true });
    }

    function confirmDelete(product) {
        deleting = product;
        showDelete = true;
    }

    function destroy() {
        deleteForm.delete(`/admin/produk/${deleting.id}`, {
            preserveScroll: true,
            onSuccess: () => (showDelete = false),
        });
    }
</script>

<svelte:head><title>Produk</title></svelte:head>

<PageHeader title="Produk" subtitle="{products.length} produk">
    {#snippet actions()}
        <Link href="/admin/produk/create" class="btn-primary">+ Tambah produk</Link>
    {/snippet}
</PageHeader>

<form class="mb-4 flex flex-wrap gap-2" onsubmit={applyFilters}>
    <input class="input max-w-xs" placeholder="Cari nama produk" bind:value={search} aria-label="Cari produk" />
    <select class="input w-auto" bind:value={categoryId} onchange={applyFilters} aria-label="Kategori">
        <option value="">Semua kategori</option>
        {#each categories as category (category.id)}<option value={String(category.id)}>{category.name}</option>{/each}
    </select>
    <button class="btn-secondary" type="submit">Cari</button>
</form>

{#if products.length === 0}
    <EmptyState title="Belum ada produk" description="Tambahkan produk pertama atau ubah filter.">
        <Link href="/admin/produk/create" class="btn-primary">+ Tambah produk</Link>
    </EmptyState>
{:else}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        {#each products as product (product.id)}
            <div class="card flex gap-3 p-3 {product.is_active ? '' : 'opacity-60'}">
                <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-stone-100">
                    {#if product.photo_url}
                        <img src={product.photo_url} alt={product.name} class="size-full object-cover" loading="lazy" />
                    {:else}
                        <div class="flex size-full items-center justify-center text-2xl" aria-hidden="true">🍗</div>
                    {/if}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{product.name}</p>
                            <p class="text-xs text-stone-500">{product.category?.name ?? '-'}</p>
                        </div>
                        <p class="shrink-0 font-bold tabular-nums">{rupiah(product.price)}</p>
                    </div>
                    <div class="mt-1.5 flex flex-wrap gap-1 text-[11px] font-medium">
                        {#if !product.is_active}<span class="rounded-full bg-stone-200 px-2 py-0.5 text-stone-700">Nonaktif</span>{/if}
                        {#if product.show_online}<span class="rounded-full bg-sky-100 px-2 py-0.5 text-sky-700">Online</span>{:else}<span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-600">Hanya di tempat</span>{/if}
                        {#if product.track_stock}
                            <span class="rounded-full px-2 py-0.5 {product.is_low_stock ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700'}">Stok {product.stock}</span>
                        {/if}
                        {#if product.is_sold_out}<span class="rounded-full bg-red-100 px-2 py-0.5 text-red-700">Habis</span>{/if}
                    </div>
                    <div class="mt-2 flex gap-1">
                        <Link href="/admin/produk/{product.id}/edit" class="btn-secondary btn-sm">Ubah</Link>
                        <button type="button" class="btn-danger btn-sm" onclick={() => confirmDelete(product)}>Hapus</button>
                    </div>
                </div>
            </div>
        {/each}
    </div>
{/if}

<Modal bind:open={showDelete} title="Hapus produk?" size="sm">
    <p class="text-sm text-stone-600">
        Produk <strong>{deleting?.name}</strong> akan dihapus dari menu. Riwayat transaksi tetap tersimpan.
        Jika hanya ingin menyembunyikan sementara, nonaktifkan produk atau tandai habis.
    </p>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showDelete = false)}>Batal</button>
        <button type="button" class="btn-primary" disabled={deleteForm.processing} onclick={destroy}>Hapus</button>
    {/snippet}
</Modal>
