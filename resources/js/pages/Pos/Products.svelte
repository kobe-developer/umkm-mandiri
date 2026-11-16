<script>
    import { router } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import Toggle from '../../components/Toggle.svelte';
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

<PageHeader title="Ketersediaan Produk" subtitle="Tandai Habis/Tersedia — langsung berlaku di menu online." />

<input type="search" class="input mb-4 max-w-sm" placeholder="Cari produk…" bind:value={search} aria-label="Cari produk" />

{#each groups as group (group.id)}
    <section class="card mb-4 overflow-hidden">
        <h2 class="bg-stone-50 px-4 py-2 text-sm font-semibold text-stone-600">{group.name}</h2>
        <ul class="divide-y divide-stone-100">
            {#each group.items as product (product.id)}
                <li class="flex items-center gap-3 px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium {product.is_sold_out ? 'text-stone-400' : ''}">{product.name}</p>
                        <p class="flex flex-wrap items-center gap-2 text-xs text-stone-500">
                            <span>{rupiah(product.price)}</span>
                            {#if product.track_stock}
                                <span class="{product.stock <= 0 ? 'font-semibold text-red-600' : product.is_low_stock ? 'font-semibold text-amber-700' : ''}">Stok {product.stock}</span>
                                {#if product.is_low_stock}<span class="rounded-full bg-amber-100 px-1.5 py-0.5 font-semibold text-amber-800">Menipis</span>{/if}
                            {/if}
                            {#if !product.show_online}<span class="rounded-full bg-stone-100 px-1.5 py-0.5">Hanya di tempat</span>{/if}
                            {#if product.is_available && product.is_sold_out}<span class="rounded-full bg-red-100 px-1.5 py-0.5 font-semibold text-red-700">Habis (stok 0)</span>{/if}
                        </p>
                    </div>
                    <span class="text-xs font-semibold {product.is_available ? 'text-emerald-700' : 'text-stone-500'}">{product.is_available ? 'Tersedia' : 'Habis'}</span>
                    <Toggle checked={product.is_available} disabled={pendingId === product.id} onchange={(value) => setAvailability(product, value)} />
                </li>
            {/each}
        </ul>
    </section>
{:else}
    <p class="py-8 text-center text-sm text-stone-500">Produk tidak ditemukan.</p>
{/each}
