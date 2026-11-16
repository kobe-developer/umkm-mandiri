<script>
    import { onMount, tick } from 'svelte';
    import MenuHeader from '../../components/menu/MenuHeader.svelte';
    import ProductCard from '../../components/menu/ProductCard.svelte';
    import CartView from '../../components/menu/CartView.svelte';
    import CheckoutForm from '../../components/menu/CheckoutForm.svelte';
    import OrderTracker from '../../components/menu/OrderTracker.svelte';
    import Search from '@lucide/svelte/icons/search';
    import SearchX from '@lucide/svelte/icons/search-x';
    import ShoppingBag from '@lucide/svelte/icons/shopping-bag';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import Info from '@lucide/svelte/icons/info';
    import EmptyState from '../../components/EmptyState.svelte';
    import { rupiah } from '../../lib/format.js';
    import { cart } from '../../lib/cart.svelte.js';

    let { categories, products, paymentMethods, storeSettings, trackedOrders = [], hasOrderHistory = false } = $props();

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

    /** Selama scroll hasil klik tab, observer tidak boleh mengganti tab aktif. */
    const PROGRAMMATIC_SCROLL_MS = 1000;
    let programmaticScrollUntil = 0;
    let stickyBar = $state();

    const isProgrammaticScroll = () => Date.now() < programmaticScrollUntil;
    const scrollBehavior = () => (window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth');

    // Tandai tab kategori yang sedang terlihat saat scroll
    $effect(() => {
        if (step !== 'menu' || sections.length === 0) return;
        const observer = new IntersectionObserver(
            (entries) => {
                if (isProgrammaticScroll()) return;
                const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
                if (visible) activeCategory = Number(visible.target.dataset.category);
            },
            { rootMargin: '-140px 0px -60% 0px' },
        );
        document.querySelectorAll('[data-category]').forEach((el) => observer.observe(el));
        return () => observer.disconnect();
    });

    // Geser strip tab agar tab aktif di tengah. Hanya menggulir <nav> (bukan halaman), dan
    // tanpa animasi saat halaman sedang di-scroll oleh klik tab: di iOS Safari dua smooth
    // scroll yang berjalan bersamaan saling membatalkan sehingga halaman tidak sampai.
    $effect(() => {
        if (step !== 'menu' || activeCategory === null) return;
        const tab = document.getElementById(`tab-${activeCategory}`);
        const nav = tab?.parentElement;
        if (!tab || !nav) return;
        const offset = tab.getBoundingClientRect().left - nav.getBoundingClientRect().left;
        const left = nav.scrollLeft + offset - (nav.clientWidth - tab.offsetWidth) / 2;
        nav.scrollTo({ left: Math.max(0, left), behavior: isProgrammaticScroll() ? 'auto' : scrollBehavior() });
    });

    async function goTo(next) {
        step = next;
        await tick();
        window.scrollTo({ top: 0 });
    }

    function scrollToCategory(id) {
        const section = document.getElementById(`kategori-${id}`);
        if (!section) return;
        programmaticScrollUntil = Date.now() + PROGRAMMATIC_SCROLL_MS;
        activeCategory = id;
        // Satu-satunya scroll halaman: posisi section dikurangi tinggi bar sticky
        const top = section.getBoundingClientRect().top + window.scrollY - (stickyBar?.offsetHeight ?? 0);
        window.scrollTo({ top: Math.max(0, top), behavior: scrollBehavior() });
    }
</script>

<svelte:head>
    <title>Menu · {storeSettings.name}</title>
    <meta name="description" content="Pesan online {storeSettings.name}. {storeSettings.delivery_note ?? ''}" />
</svelte:head>

<div class="min-h-dvh bg-paper">
    {#if step === 'cart'}
        <CartView {productMap} settings={storeSettings} {blockedReason} onback={() => goTo('menu')} oncheckout={() => goTo('checkout')} />
    {:else if step === 'checkout'}
        <CheckoutForm {productMap} settings={storeSettings} {paymentMethods} {blockedReason} onback={() => goTo('cart')} />
    {:else}
        <MenuHeader settings={storeSettings} {hasOrderHistory} />

        {#if blockedReason}
            <div class="mx-auto max-w-2xl px-5 pb-4" role="status">
                <p class="flex gap-3 rounded-2xl bg-amber-soft px-4 py-3 text-sm text-amber-ink">
                    <Info size={18} class="mt-px shrink-0" />
                    <span><strong class="font-semibold">{blockedReason}.</strong> Menu tetap bisa dilihat, tapi pesanan belum bisa dikirim.</span>
                </p>
            </div>
        {/if}

        <OrderTracker orders={trackedOrders} />

        <div bind:this={stickyBar} class="sticky top-0 z-20 border-b border-line bg-paper/90 backdrop-blur-xl">
            <div class="mx-auto max-w-2xl px-5 pt-3">
                <label class="relative block">
                    <span class="sr-only">Cari menu</span>
                    <Search size={18} class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-faint" />
                    <input type="search" class="input rounded-full pl-11" placeholder="Cari ayam, sambal, minuman…" bind:value={search} />
                </label>
            </div>
            <nav class="scroll-x mx-auto max-w-2xl gap-6 px-5" aria-label="Kategori">
                {#each sections as section (section.id)}
                    {@const active = activeCategory === section.id}
                    <button
                        id="tab-{section.id}"
                        type="button"
                        aria-current={active ? 'true' : undefined}
                        class="relative shrink-0 py-3.5 text-sm font-semibold whitespace-nowrap transition {active ? 'text-ink' : 'text-muted hover:text-ink-soft'}"
                        onclick={() => scrollToCategory(section.id)}
                    >
                        {section.name}
                        <span class="absolute inset-x-0 bottom-0 h-0.5 rounded-full transition {active ? 'bg-brand-600' : 'bg-transparent'}"></span>
                    </button>
                {/each}
            </nav>
        </div>

        <main class="mx-auto max-w-2xl px-5 pt-2 pb-36">
            {#if sections.length === 0}
                <div class="pt-6">
                    <EmptyState
                        icon={search ? SearchX : undefined}
                        title={search ? 'Menu tidak ditemukan' : 'Menu belum tersedia'}
                        description={search ? `Tidak ada menu yang cocok dengan "${search}".` : 'Silakan cek kembali nanti.'}
                    />
                </div>
            {/if}

            {#each sections as section (section.id)}
                <section id="kategori-{section.id}" data-category={section.id} class="pt-6" aria-labelledby="judul-{section.id}">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 id="judul-{section.id}" class="display text-2xl text-ink">{section.name}</h2>
                        <span class="num text-xs text-faint">{section.products.length} menu</span>
                    </div>
                    <div class="divide-y divide-line">
                        {#each section.products as product (product.id)}
                            <ProductCard {product} />
                        {/each}
                    </div>
                </section>
            {/each}

            {#if storeSettings.min_order > 0}
                <p class="mt-6 text-center text-xs text-muted">Minimal order {rupiah(storeSettings.min_order)}</p>
            {/if}
        </main>

        {#if itemCount > 0}
            <div class="fixed inset-x-0 bottom-0 z-30 px-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                <button
                    type="button"
                    class="mx-auto flex w-full max-w-2xl animate-sheet-up items-center gap-3 rounded-full bg-ink py-2 pr-5 pl-2 text-paper shadow-float transition active:scale-[0.99]"
                    onclick={() => goTo('cart')}
                >
                    <span class="relative flex size-11 items-center justify-center rounded-full bg-white/10">
                        <ShoppingBag size={20} />
                        {#key itemCount}
                            <span class="num absolute -top-0.5 -right-0.5 min-w-5 animate-pop rounded-full bg-brand-500 px-1 text-center text-[11px] leading-5 font-bold text-white">{itemCount}</span>
                        {/key}
                    </span>
                    <span class="flex-1 text-left text-[15px] font-semibold">Lihat keranjang</span>
                    <span class="num text-[15px] font-semibold">{rupiah(subtotal)}</span>
                    <ArrowRight size={18} class="opacity-60" />
                </button>
            </div>
        {/if}
    {/if}
</div>
