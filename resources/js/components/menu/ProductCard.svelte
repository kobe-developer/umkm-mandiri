<script>
    import Plus from '@lucide/svelte/icons/plus';
    import QtyStepper from '../QtyStepper.svelte';
    import { rupiah } from '../../lib/format.js';
    import { cart, CART_MAX_QUANTITY } from '../../lib/cart.svelte.js';

    let { product } = $props();

    const quantity = $derived(cart.quantityOf(product.id));
    const soldOut = $derived(product.is_sold_out);
</script>

{#snippet action(overlay)}
    {#if soldOut}
        <span class="rounded-full border border-line bg-surface px-3 py-1.5 text-xs font-semibold text-muted {overlay ? 'shadow-soft' : ''}">Habis</span>
    {:else if quantity > 0}
        <div class="rounded-full {overlay ? 'shadow-soft' : ''}"><QtyStepper value={quantity} max={CART_MAX_QUANTITY} size="sm" onchange={(q) => cart.setQuantity(product.id, q)} /></div>
    {:else}
        <button
            type="button"
            class="inline-flex min-h-9 items-center gap-1 rounded-full border border-line bg-surface pr-4 pl-3 text-sm font-semibold text-brand-700 transition hover:border-brand-200 active:scale-95 {overlay ? 'shadow-soft' : ''}"
            aria-label="Tambah {product.name}"
            onclick={() => cart.add(product.id)}
        ><Plus size={16} strokeWidth={2.5} />Tambah</button>
    {/if}
{/snippet}

<article class="flex gap-4 {product.photo_url ? 'py-5' : 'items-center py-4'} {soldOut ? 'opacity-55' : ''}">
    <div class="flex min-w-0 flex-1 flex-col self-stretch">
        <h3 class="text-[15px] leading-snug font-semibold text-ink">{product.name}</h3>
        {#if product.description}
            <p class="mt-1 line-clamp-2 text-[13px] leading-relaxed text-muted">{product.description}</p>
        {/if}
        <p class="num mt-auto pt-1.5 text-[15px] font-semibold text-ink">
            {rupiah(product.price)}
            {#if quantity > 0}<span class="ml-1.5 text-xs font-medium text-brand-600">· {quantity} di keranjang</span>{/if}
        </p>
    </div>

    {#if product.photo_url}
        <div class="relative shrink-0 pb-4">
            <div class="size-28 overflow-hidden rounded-2xl bg-paper-deep {soldOut ? 'grayscale' : ''}">
                <img src={product.photo_url} alt={product.name} width="112" height="112" class="size-full object-cover" loading="lazy" decoding="async" />
            </div>
            <div class="absolute inset-x-0 bottom-0 flex justify-center">{@render action(true)}</div>
        </div>
    {:else}
        <div class="shrink-0">{@render action(false)}</div>
    {/if}
</article>
