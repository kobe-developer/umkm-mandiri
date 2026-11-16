<script>
    import QtyStepper from '../QtyStepper.svelte';
    import { rupiah } from '../../lib/format.js';
    import { cart, CART_MAX_QUANTITY } from '../../lib/cart.svelte.js';

    let { product } = $props();

    const quantity = $derived(cart.quantityOf(product.id));
    const soldOut = $derived(product.is_sold_out);
</script>

<article class="card flex gap-3 overflow-hidden p-3 {soldOut ? 'opacity-60 grayscale' : ''}">
    <div class="relative size-24 shrink-0 overflow-hidden rounded-xl bg-orange-50">
        {#if product.photo_url}
            <img src={product.photo_url} alt={product.name} class="size-full object-cover" loading="lazy" decoding="async" />
        {:else}
            <div class="flex size-full items-center justify-center text-4xl" aria-hidden="true">🍗</div>
        {/if}
        {#if soldOut}
            <span class="absolute inset-x-0 bottom-0 bg-stone-800/80 py-0.5 text-center text-xs font-bold text-white">Habis</span>
        {/if}
    </div>

    <div class="flex min-w-0 flex-1 flex-col">
        <h3 class="leading-snug font-semibold text-stone-900">{product.name}</h3>
        {#if product.description}
            <p class="mt-0.5 line-clamp-2 text-xs text-stone-500">{product.description}</p>
        {/if}
        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            <p class="font-bold text-brand-700 tabular-nums">{rupiah(product.price)}</p>
            {#if soldOut}
                <span class="rounded-full bg-stone-200 px-3 py-1 text-xs font-semibold text-stone-600">Habis</span>
            {:else if quantity > 0}
                <QtyStepper value={quantity} max={CART_MAX_QUANTITY} size="sm" onchange={(q) => cart.setQuantity(product.id, q)} />
            {:else}
                <button
                    type="button"
                    class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-xl font-bold text-white shadow-sm active:scale-95"
                    aria-label="Tambah {product.name}"
                    onclick={() => cart.add(product.id)}
                >+</button>
            {/if}
        </div>
    </div>
</article>
