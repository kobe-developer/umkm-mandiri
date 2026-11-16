<script>
    import { router } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import Toggle from '../../components/Toggle.svelte';
    import Search from '@lucide/svelte/icons/search';
    import { rupiah } from '../../lib/format.js';

    let { categories, products } = $props();

    let search = $state('');
    let pendingId = $state(null);

    const groups = $derived.by(() => {
        const term = search.trim().toLowerCase();
        return categories
            .map((category) => ({
                ...category,
                items: products.filter((p) => p.category_id === category.id && (!term || p.name.toLowerCase().includes(term))),
            }))
            .filter((group) => group.items.length > 0);
    });

    function setAvailability(product, isAvailable) {
        pendingId = product.id;
        router.patch(`/pos/produk/${product.id}/ketersediaan`, { is_available: isAvailable }, {
            preserveScroll: true,
            onFinish: () => (pendingId = null),
        });
    }
</script>

<svelte:head><title>Ketersediaan Produk</title></svelte:head>

<PageHeader title="Ketersediaan" subtitle="Tandai habis atau tersedia — langsung berlaku di menu online." />

<label class="relative mb-5 block sm:max-w-sm">
    <span class="sr-only">Cari produk</span>
    <Search size={18} class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-faint" />
    <input type="search" class="input rounded-full pl-11" placeholder="Cari produk…" bind:value={search} />
</label>

{#each groups as group (group.id)}
    <section class="mb-6">
        <h2 class="display mb-2 px-1 text-lg text-ink">{group.name}</h2>
        <ul class="card divide-y divide-line">
            {#each group.items as product (product.id)}
                <li class="flex items-center gap-3 px-4 py-3.5">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium {product.is_available ? 'text-ink' : 'text-faint line-through decoration-1'}">{product.name}</p>
                        <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                            <span class="num">{rupiah(product.price)}</span>
                            {#if product.track_stock}
                                <span class="num {product.stock <= 0 ? 'font-semibold text-brand-600' : product.is_low_stock ? 'font-semibold text-amber-ink' : ''}">· Stok {product.stock}</span>
                                {#if product.is_low_stock}<span class="rounded-full bg-amber-soft px-2 py-0.5 font-semibold text-amber-ink">Menipis</span>{/if}
                            {/if}
                            {#if !product.show_online}<span class="rounded-full bg-paper-deep px-2 py-0.5">Hanya di tempat</span>{/if}
                            {#if product.is_available && product.is_sold_out}<span class="rounded-full bg-brand-50 px-2 py-0.5 font-semibold text-brand-700">Habis (stok 0)</span>{/if}
                        </p>
                    </div>
                    <span class="hidden text-xs font-medium sm:inline {product.is_available ? 'text-leaf-600' : 'text-muted'}">{product.is_available ? 'Tersedia' : 'Habis'}</span>
                    <Toggle checked={product.is_available} disabled={pendingId === product.id} onchange={(value) => setAvailability(product, value)} />
                </li>
            {/each}
        </ul>
    </section>
{:else}
    <p class="py-12 text-center text-sm text-muted">Produk tidak ditemukan.</p>
{/each}
