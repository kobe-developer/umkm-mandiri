<script>
    import Search from '@lucide/svelte/icons/search';
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
    <label class="relative block">
        <span class="sr-only">Cari produk</span>
        <Search size={18} class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-faint" />
        <input type="search" class="input rounded-full pl-11" placeholder="Cari produk…" bind:value={search} />
    </label>

    <div class="scroll-x -mx-4 gap-2 px-4 sm:mx-0 sm:px-0">
        <button type="button" class="chip {activeCategory === 'all' ? 'chip-active' : ''}" onclick={() => (activeCategory = 'all')}>Semua</button>
        {#each categories as category (category.id)}
            <button type="button" class="chip {activeCategory === category.id ? 'chip-active' : ''}" onclick={() => (activeCategory = category.id)}>{category.name}</button>
        {/each}
    </div>

    {#if visible.length === 0}
        <p class="py-12 text-center text-sm text-muted">Produk tidak ditemukan.</p>
    {:else}
        <div class="grid grid-cols-2 gap-2.5 pt-1 sm:grid-cols-3 xl:grid-cols-4">
            {#each visible as product (product.id)}
                {@const qty = quantities[product.id] ?? 0}
                <button
                    type="button"
                    disabled={product.is_sold_out}
                    onclick={() => onadd(product)}
                    class="relative flex min-h-28 flex-col justify-between rounded-2xl border bg-surface p-3.5 text-left transition duration-150 active:scale-[0.97] disabled:cursor-not-allowed disabled:bg-paper disabled:opacity-55
                        {qty > 0 ? 'border-ink ring-1 ring-ink' : 'border-line hover:border-line-strong hover:shadow-soft'}"
                >
                    <span class="line-clamp-2 pr-6 text-[14px] leading-snug font-semibold text-ink">{product.name}</span>
                    <span class="mt-3 flex items-end justify-between gap-1">
                        <span class="num text-[15px] font-semibold text-ink">{rupiah(product.price)}</span>
                        {#if product.is_sold_out}
                            <span class="rounded-full bg-paper-deep px-2 py-0.5 text-[11px] font-semibold text-muted">Habis</span>
                        {:else if product.track_stock}
                            <span class="num text-[11px] {product.stock <= 5 ? 'font-semibold text-amber-ink' : 'text-faint'}">Stok {product.stock}</span>
                        {/if}
                    </span>
                    {#if qty > 0}
                        {#key qty}
                            <span class="num absolute top-2.5 right-2.5 flex size-6 animate-pop items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">{qty}</span>
                        {/key}
                    {/if}
                </button>
            {/each}
        </div>
    {/if}
</div>
