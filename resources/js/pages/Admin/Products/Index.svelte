<script>
    import PageTitle from '../../../components/PageTitle.svelte';
    import { Link, router, useForm } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash from '@lucide/svelte/icons/trash';
    import Search from '@lucide/svelte/icons/search';
    import Drumstick from '@lucide/svelte/icons/drumstick';
    import Globe from '@lucide/svelte/icons/globe';
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

    function selectCategory(id) {
        categoryId = id;
        applyFilters();
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

<PageTitle title="Produk" />

<PageHeader title="Produk" subtitle="{products.length} produk">
    {#snippet actions()}
        <Link href="/admin/produk/create" class="btn-primary"><Plus size={18} />Tambah produk</Link>
    {/snippet}
</PageHeader>

<div class="mb-6 space-y-3">
    <form class="flex gap-2 sm:max-w-md" onsubmit={applyFilters}>
        <div class="relative min-w-0 flex-1">
            <Search size={17} class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-faint" aria-hidden="true" />
            <input class="input pl-10" type="search" placeholder="Cari nama produk" bind:value={search} aria-label="Cari produk" />
        </div>
        <button class="btn-secondary" type="submit">Cari</button>
    </form>
    <div class="scroll-x gap-2" role="group" aria-label="Kategori">
        <button type="button" class="chip {categoryId === '' ? 'chip-active' : ''}" aria-pressed={categoryId === ''} onclick={() => selectCategory('')}>Semua kategori</button>
        {#each categories as category (category.id)}
            <button
                type="button"
                class="chip {categoryId === String(category.id) ? 'chip-active' : ''}"
                aria-pressed={categoryId === String(category.id)}
                onclick={() => selectCategory(String(category.id))}
            >{category.name}</button>
        {/each}
    </div>
</div>

{#if products.length === 0}
    <EmptyState icon={Drumstick} title="Belum ada produk" description="Tambahkan produk pertama atau ubah filter.">
        <Link href="/admin/produk/create" class="btn-primary"><Plus size={18} />Tambah produk</Link>
    </EmptyState>
{:else}
    <ul class="card divide-y divide-line overflow-hidden sm:grid sm:grid-cols-2 sm:gap-3 sm:divide-y-0 sm:overflow-visible sm:rounded-none sm:border-0 sm:bg-transparent xl:grid-cols-3">
        {#each products as product (product.id)}
            <li class="flex gap-3 p-3 sm:rounded-card sm:border sm:border-line sm:bg-surface sm:p-4">
                <div class="size-18 shrink-0 overflow-hidden rounded-xl bg-paper-deep {product.is_active ? '' : 'opacity-55 grayscale'}">
                    {#if product.photo_url}
                        <img src={product.photo_url} alt={product.name} class="size-full object-cover" loading="lazy" />
                    {:else}
                        <div class="flex size-full items-center justify-center text-faint" aria-hidden="true"><Drumstick size={24} strokeWidth={1.5} /></div>
                    {/if}
                </div>
                <div class="flex min-w-0 flex-1 flex-col">
                    <div class="flex items-start justify-between gap-2 {product.is_active ? '' : 'opacity-60'}">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink">{product.name}</p>
                            <p class="text-xs text-muted">{product.category?.name ?? '-'}</p>
                        </div>
                        <p class="num shrink-0 font-semibold text-ink">{rupiah(product.price)}</p>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1 text-[11px] font-semibold">
                        {#if !product.is_active}<span class="rounded-full bg-ink px-2 py-0.5 text-paper">Nonaktif</span>{/if}
                        {#if product.show_online}
                            <span class="inline-flex items-center gap-1 rounded-full bg-paper-deep px-2 py-0.5 text-ink-soft"><Globe size={11} />Online</span>
                        {:else}
                            <span class="rounded-full bg-paper-deep px-2 py-0.5 text-muted">Hanya di tempat</span>
                        {/if}
                        {#if product.track_stock}
                            <span class="num rounded-full px-2 py-0.5 {product.is_low_stock ? 'bg-amber-soft text-amber-ink' : 'bg-leaf-50 text-leaf-700'}">Stok {product.stock}</span>
                        {/if}
                        {#if product.is_sold_out}<span class="rounded-full bg-brand-50 px-2 py-0.5 text-brand-700">Habis</span>{/if}
                    </div>
                    <div class="mt-auto flex items-center gap-1 pt-2">
                        <Link href="/admin/produk/{product.id}/edit" class="btn-secondary btn-sm"><Pencil size={14} />Ubah</Link>
                        <button type="button" class="btn-icon size-9 hover:bg-brand-50 hover:text-brand-700" onclick={() => confirmDelete(product)} aria-label="Hapus {product.name}" title="Hapus">
                            <Trash size={16} />
                        </button>
                    </div>
                </div>
            </li>
        {/each}
    </ul>
{/if}

<Modal bind:open={showDelete} title="Hapus produk?" size="sm">
    <p class="text-sm text-ink-soft">
        Produk <strong>{deleting?.name}</strong> akan dihapus dari menu. Riwayat transaksi tetap tersimpan.
        Jika hanya ingin menyembunyikan sementara, nonaktifkan produk atau tandai habis.
    </p>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showDelete = false)}>Batal</button>
        <button type="button" class="btn-accent" disabled={deleteForm.processing} onclick={destroy}>Hapus</button>
    {/snippet}
</Modal>
