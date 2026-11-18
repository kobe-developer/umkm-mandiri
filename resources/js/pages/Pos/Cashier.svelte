<script>
    import { useForm, page } from '@inertiajs/svelte';
    import ProductGrid from '../../components/pos/ProductGrid.svelte';
    import TicketItems from '../../components/pos/TicketItems.svelte';
    import TicketCheckout from '../../components/pos/TicketCheckout.svelte';
    import ErrorSummary from '../../components/pos/ErrorSummary.svelte';
    import Receipt from '@lucide/svelte/icons/receipt';
    import ChevronUp from '@lucide/svelte/icons/chevron-up';
    import X from '@lucide/svelte/icons/x';
    import { rupiah } from '../../lib/format.js';
    import { effectivePrice } from '../../lib/promo.js';

    let { categories, products, paymentMethods, channels } = $props();

    const MAX_QTY = 50;

    let cart = $state([]);
    let ticketOpen = $state(false);

    const form = useForm({
        channel: 'dine_in',
        table_number: '',
        customer_name: '',
        whatsapp_number: '',
        notes: '',
        discount_type: '',
        discount_value: null,
        pay_later: false,
        payment_method_id: null,
        amount_received: null,
    });

    const productById = $derived(Object.fromEntries(products.map((p) => [p.id, p])));
    const canDiscount = $derived(page.props.pos?.can_discount ?? false);
    const methods = $derived(paymentMethods.filter((m) => (m.channels ?? []).includes(form.channel)));
    const quantities = $derived(Object.fromEntries(cart.map((i) => [i.product_id, i.quantity])));
    const itemCount = $derived(cart.reduce((sum, i) => sum + i.quantity, 0));
    // Harga mengikuti kanal terpilih (promo bisa hanya berlaku untuk dine in / take away)
    const ticketItems = $derived(cart.map((i) => {
        const product = productById[i.product_id];
        return { ...i, price: effectivePrice(product, form.channel), normal_price: product?.price ?? i.price };
    }));
    const subtotal = $derived(ticketItems.reduce((sum, i) => sum + i.price * i.quantity, 0));
    const discount = $derived.by(() => {
        const value = Number(form.discount_value) || 0;
        if (!canDiscount || !form.discount_type) return 0;
        if (form.discount_type === 'percent') return Math.floor((subtotal * Math.min(value, 100)) / 100);
        return Math.min(value, subtotal);
    });
    const total = $derived(subtotal - discount);

    // Metode yang tidak berlaku untuk kanal terpilih di-reset; default ke metode pertama (biasanya Tunai)
    $effect(() => {
        if (!methods.some((m) => m.id === form.payment_method_id)) {
            form.payment_method_id = methods[0]?.id ?? null;
        }
    });

    $effect(() => {
        if (form.channel !== 'dine_in' && form.pay_later) form.pay_later = false;
    });

    function maxFor(item) {
        const product = productById[item.product_id];
        return product?.track_stock ? Math.min(product.stock, MAX_QTY) : MAX_QTY;
    }

    function add(product) {
        const existing = cart.find((i) => i.product_id === product.id);
        if (existing) {
            setQty(product.id, Math.min(existing.quantity + 1, maxFor(existing)));
            return;
        }
        cart = [...cart, { product_id: product.id, name: product.name, quantity: 1, notes: '' }];
    }

    function setQty(productId, quantity) {
        cart = quantity <= 0
            ? cart.filter((i) => i.product_id !== productId)
            : cart.map((i) => (i.product_id === productId ? { ...i, quantity } : i));
    }

    function setNotes(productId, notes) {
        cart = cart.map((i) => (i.product_id === productId ? { ...i, notes } : i));
    }

    function clearTicket() {
        cart = [];
        form.reset();
        form.clearErrors();
    }

    function submit() {
        form
            .transform((data) => ({
                ...data,
                table_number: data.channel === 'dine_in' ? data.table_number : null,
                discount_type: data.discount_type || null,
                discount_value: data.discount_type ? Number(data.discount_value) || 0 : 0,
                amount_received: data.pay_later ? null : data.amount_received,
                items: cart.map((i) => ({ product_id: i.product_id, quantity: i.quantity, notes: i.notes || null })),
            }))
            .post('/pos/orders', { preserveScroll: true });
    }
</script>

<svelte:head><title>Kasir</title></svelte:head>

{#snippet ticket(closeable = false)}
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <h2 class="display flex-1 text-xl text-ink">Pesanan</h2>
            {#if cart.length > 0}<button type="button" class="btn-ghost btn-sm text-muted hover:text-brand-600" onclick={clearTicket}>Kosongkan</button>{/if}
            {#if closeable}<button type="button" class="btn-icon -mr-2 size-10" aria-label="Tutup pesanan" onclick={() => (ticketOpen = false)}><X size={20} /></button>{/if}
        </div>
        <ErrorSummary errors={form.errors} />
        <TicketItems cart={ticketItems} {maxFor} onqty={setQty} onnotes={setNotes} />
        <TicketCheckout {form} {methods} {subtotal} {discount} {total} {canDiscount} {itemCount} onsubmit={submit} />
    </div>
{/snippet}

<div class="lg:grid lg:grid-cols-[1fr_380px] lg:gap-6">
    <section class="space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <div class="segmented" role="tablist" aria-label="Kanal">
                {#each channels as channel (channel.value)}
                    <button
                        type="button"
                        role="tab"
                        aria-selected={form.channel === channel.value}
                        class="segment {form.channel === channel.value ? 'segment-active' : ''}"
                        onclick={() => (form.channel = channel.value)}
                    >{channel.label}</button>
                {/each}
            </div>
            {#if form.channel === 'dine_in'}
                <input class="input num min-h-11 w-28 rounded-full text-center" placeholder="No. meja" maxlength="10" bind:value={form.table_number} aria-label="Nomor meja" />
            {/if}
        </div>

        <ProductGrid {categories} {products} {quantities} channel={form.channel} onadd={add} />
    </section>

    <!-- Tiket desktop/tablet besar -->
    <aside class="hidden lg:block">
        <div class="card sticky top-8 max-h-[calc(100dvh-4rem)] overflow-y-auto p-5 shadow-soft">
            {@render ticket()}
        </div>
    </aside>
</div>

<!-- Tiket mobile: bar melayang + bottom sheet -->
<div class="lg:hidden">
    {#if cart.length > 0 && !ticketOpen}
        <button
            type="button"
            class="fixed inset-x-3 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-30 flex animate-sheet-up items-center gap-3 rounded-full bg-ink py-2 pr-5 pl-2 text-paper shadow-float"
            onclick={() => (ticketOpen = true)}
        >
            <span class="relative flex size-10 items-center justify-center rounded-full bg-white/10">
                <Receipt size={18} />
                <span class="num absolute -top-0.5 -right-0.5 min-w-5 rounded-full bg-brand-500 px-1 text-center text-[11px] leading-5 font-bold text-white">{itemCount}</span>
            </span>
            <span class="flex-1 text-left text-sm font-semibold">Lihat pesanan</span>
            <span class="num font-semibold">{rupiah(total)}</span>
            <ChevronUp size={18} class="opacity-60" />
        </button>
    {/if}
    {#if ticketOpen}
        <div class="fixed inset-0 z-40 animate-fade-in bg-ink/40 backdrop-blur-[2px]" role="presentation" onclick={() => (ticketOpen = false)}></div>
        <div class="fixed inset-x-0 bottom-0 z-50 max-h-[92dvh] animate-sheet-up overflow-y-auto rounded-t-[1.75rem] bg-surface px-5 pt-2.5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-float" role="dialog" aria-modal="true" aria-label="Pesanan">
            <div class="mb-1 flex items-center justify-center">
                <span class="h-1 w-10 rounded-full bg-line-strong" aria-hidden="true"></span>
            </div>
            {@render ticket(true)}
        </div>
    {/if}
</div>
