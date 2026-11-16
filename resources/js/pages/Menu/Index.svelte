<script>
    import { onMount, tick } from 'svelte';
    import MenuHeader from '../../components/menu/MenuHeader.svelte';
    import ProductCard from '../../components/menu/ProductCard.svelte';
    import CartView from '../../components/menu/CartView.svelte';
    import CheckoutForm from '../../components/menu/CheckoutForm.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import { rupiah } from '../../lib/format.js';
    import { cart } from '../../lib/cart.svelte.js';

    let { categories, products, paymentMethods, storeSettings } = $props();

    /** Langkah: menu → keranjang → checkout */
    let step = $state('menu');
    let search = $state('');
    let activeCategory = $state(null);

    const productMap = $derived(new Map(products.map((p) => [p.id, p])));
    const itemCount = $derived(cart.count());
    const subtotal = $derived(cart.subtotal(productMap));

    const blockedReason = $derived(
        storeSettings.accepts_orders ? null : !storeSettings.is_open ? 'Toko sedang tutup' : 'Order online sedang tidak aktif',
    );

    const sections = $derived.by(() => {
        const term = search.trim().toLowerCase();
        return categories
            .map((category) => ({
                ...category,
                products: products.filter((p) => p.category_id === category.id && (!term || p.name.toLowerCase().includes(term))),
            }))
            .filter((section) => section.products.length > 0);
    });

    onMount(() => {
        cart.prune(productMap);
        activeCategory = categories[0]?.id ?? null;
    });

    // Tandai tab kategori yang sedang terlihat saat scroll
    $effect(() => {
        if (step !== 'menu' || sections.length === 0) return;
        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
                if (visible) activeCategory = Number(visible.target.dataset.category);
            },
            { rootMargin: '-120px 0px -60% 0px' },
        );
        document.querySelectorAll('[data-category]').forEach((el) => observer.observe(el));
        return () => observer.disconnect();
    });

    async function goTo(next) {
        step = next;
        await tick();
        window.scrollTo({ top: 0 });
    }

    function scrollToCategory(id) {
        activeCategory = id;
        document.getElementById(`kategori-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>

<svelte:head>
    <title>Menu · {storeSettings.name}</title>
    <meta name="description" content="Pesan online {storeSettings.name}. {storeSettings.delivery_note ?? ''}" />
</svelte:head>

<div class="min-h-screen bg-stone-50">
    {#if step === 'cart'}
        <CartView {productMap} settings={storeSettings} {blockedReason} onback={() => goTo('menu')} oncheckout={() => goTo('checkout')} />
    {:else if step === 'checkout'}
        <CheckoutForm {productMap} settings={storeSettings} {paymentMethods} {blockedReason} onback={() => goTo('cart')} />
    {:else}
        <MenuHeader settings={storeSettings} />

        {#if blockedReason}
            <div class="border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-center text-sm text-amber-800" role="status">
                <strong>{blockedReason}.</strong> Menu tetap bisa dilihat, tapi pesanan belum bisa dikirim.
            </div>
        {/if}

        <div class="sticky top-0 z-20 border-b border-stone-200 bg-white/95 backdrop-blur">
            <div class="mx-auto max-w-2xl px-4 pt-3">
                <input type="search" class="input py-2" placeholder="Cari menu…" bind:value={search} aria-label="Cari menu" />
            </div>
            <nav class="mx-auto flex max-w-2xl gap-2 overflow-x-auto px-4 py-2.5" aria-label="Kategori">
                {#each sections as section (section.id)}
                    <button
                        type="button"
                        class="shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold transition {activeCategory === section.id ? 'bg-brand-600 text-white' : 'bg-stone-100 text-stone-700'}"
                        onclick={() => scrollToCategory(section.id)}
                    >
                        {section.name}
                    </button>
                {/each}
            </nav>
        </div>

        <main class="mx-auto max-w-2xl px-4 pt-4 pb-32">
            {#if sections.length === 0}
                <EmptyState
                    title={search ? 'Menu tidak ditemukan' : 'Menu belum tersedia'}
                    description={search ? `Tidak ada menu yang cocok dengan "${search}".` : 'Silakan cek kembali nanti.'}
                />
            {/if}

            {#each sections as section (section.id)}
                <section id="kategori-{section.id}" data-category={section.id} class="scroll-mt-32 pb-4">
                    <h2 class="mb-2 text-lg font-bold">{section.name}</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        {#each section.products as product (product.id)}
                            <ProductCard {product} />
                        {/each}
                    </div>
                </section>
            {/each}

            {#if storeSettings.min_order > 0}
                <p class="mt-2 text-center text-xs text-stone-500">Minimal order {rupiah(storeSettings.min_order)}</p>
            {/if}
        </main>

        {#if itemCount > 0}
            <div class="fixed inset-x-0 bottom-0 z-30 px-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                <button
                    type="button"
                    class="mx-auto flex w-full max-w-2xl items-center justify-between rounded-2xl bg-brand-600 px-5 py-3.5 text-white shadow-lg active:bg-brand-700"
                    onclick={() => goTo('cart')}
                >
                    <span class="flex items-center gap-2 font-semibold">
                        <span class="rounded-full bg-white/25 px-2 py-0.5 text-sm tabular-nums">{itemCount}</span>
                        item · Lihat keranjang
                    </span>
                    <span class="font-bold tabular-nums">{rupiah(subtotal)}</span>
                </button>
            </div>
        {/if}
    {/if}
</div>
