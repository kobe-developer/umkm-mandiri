<script>
    import { rupiah } from '../../lib/format.js';

    /** Grid produk per kategori dengan pencarian. Produk habis abu-abu & tidak bisa dipilih. */
    let { categories, products, quantities = {}, onadd } = $props();

    let activeCategory = $state('all');
    let search = $state('');

    const visible = $derived.by(() => {
        const term = search.trim().toLowerCase();
        return products.filter((p) => {
            if (term) return p.name.toLowerCase().includes(term);
            return activeCategory === 'all' || p.category_id === activeCategory;
        });
    });
</script>

<div class="space-y-3">
    <input type="search" class="input" placeholder="Cari produk…" bind:value={search} aria-label="Cari produk" />

    <div class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1">
        <button type="button" class="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium {activeCategory === 'all' ? 'bg-stone-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'}" onclick={() => (activeCategory = 'all')}>Semua</button>
        {#each categories as category (category.id)}
            <button type="button" class="shrink-0 rounded-full px-4 py-1.5 text-sm font-medium {activeCategory === category.id ? 'bg-stone-900 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200'}" onclick={() => (activeCategory = category.id)}>{category.name}</button>
        {/each}
    </div>

    {#if visible.length === 0}
        <p class="py-8 text-center text-sm text-stone-500">Produk tidak ditemukan.</p>
    {:else}
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
            {#each visible as product (product.id)}
                {@const qty = quantities[product.id] ?? 0}
                <button
                    type="button"
                    disabled={product.is_sold_out}
                    onclick={() => onadd(product)}
                    class="relative flex min-h-24 flex-col justify-between rounded-2xl border bg-white p-3 text-left shadow-sm transition active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-stone-100 disabled:opacity-60 {qty > 0 ? 'border-brand-500 ring-2 ring-brand-100' : 'border-stone-200 hover:border-stone-300'}"
                >
                    <span class="text-sm leading-snug font-semibold text-stone-800">{product.name}</span>
                    <span class="mt-2 flex items-end justify-between gap-1">
                        <span class="text-sm font-bold text-brand-700 tabular-nums">{rupiah(product.price)}</span>
                        {#if product.is_sold_out}
                            <span class="rounded-full bg-stone-300 px-2 py-0.5 text-[10px] font-bold text-stone-700 uppercase">Habis</span>
                        {:else if product.track_stock}
                            <span class="text-[11px] text-stone-500">Stok {product.stock}</span>
                        {/if}
                    </span>
                    {#if qty > 0}
                        <span class="absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white shadow">{qty}</span>
                    {/if}
                </button>
            {/each}
        </div>
    {/if}
</div>
