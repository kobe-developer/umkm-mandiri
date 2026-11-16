<script>
    import { useForm, page } from '@inertiajs/svelte';
    import ProductGrid from '../../components/pos/ProductGrid.svelte';
    import TicketItems from '../../components/pos/TicketItems.svelte';
    import TicketCheckout from '../../components/pos/TicketCheckout.svelte';
    import ErrorSummary from '../../components/pos/ErrorSummary.svelte';
    import { rupiah } from '../../lib/format.js';

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

    const canDiscount = $derived(page.props.pos?.can_discount ?? false);
    const methods = $derived(paymentMethods.filter((m) => (m.channels ?? []).includes(form.channel)));
    const quantities = $derived(Object.fromEntries(cart.map((i) => [i.product_id, i.quantity])));
    const itemCount = $derived(cart.reduce((sum, i) => sum + i.quantity, 0));
    const subtotal = $derived(cart.reduce((sum, i) => sum + i.price * i.quantity, 0));
    const discount = $derived.by(() => {
        const value = Number(form.discount_value) || 0;
        if (!canDiscount || !form.discount_type) return 0;
        if (form.discount_type === 'percent') return Math.floor((subtotal * Math.min(value, 100)) / 100);
        return Math.min(value, subtotal);
    });
    const total = $derived(subtotal - discount);
    const productById = $derived(Object.fromEntries(products.map((p) => [p.id, p])));

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
        cart = [...cart, { product_id: product.id, name: product.name, price: product.price, quantity: 1, notes: '' }];
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

{#snippet ticket()}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="font-bold">Pesanan</h2>
            {#if cart.length > 0}<button type="button" class="text-xs font-medium text-red-600" onclick={clearTicket}>Kosongkan</button>{/if}
        </div>
        <ErrorSummary errors={form.errors} />
        <TicketItems {cart} {maxFor} onqty={setQty} onnotes={setNotes} />
        <TicketCheckout {form} {methods} {subtotal} {discount} {total} {canDiscount} {itemCount} onsubmit={submit} />
    </div>
{/snippet}

<div class="lg:grid lg:grid-cols-[1fr_380px] lg:gap-5">
    <section class="space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex rounded-xl bg-stone-100 p-1">
                {#each channels as channel (channel.value)}
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-semibold {form.channel === channel.value ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-600'}"
                        onclick={() => (form.channel = channel.value)}
                    >{channel.label}</button>
                {/each}
            </div>
            {#if form.channel === 'dine_in'}
                <input class="input w-28" placeholder="No. meja" maxlength="10" bind:value={form.table_number} aria-label="Nomor meja" />
            {/if}
        </div>

        <ProductGrid {categories} {products} {quantities} onadd={add} />
    </section>

    <!-- Tiket desktop/tablet besar -->
    <aside class="hidden lg:block">
        <div class="card sticky top-5 max-h-[calc(100vh-2.5rem)] overflow-y-auto p-4">
            {@render ticket()}
        </div>
    </aside>
</div>

<!-- Tiket mobile: bar melayang + bottom sheet -->
<div class="lg:hidden">
    {#if cart.length > 0 && !ticketOpen}
        <button
            type="button"
            class="fixed inset-x-3 bottom-20 z-30 flex items-center justify-between rounded-2xl bg-brand-600 px-4 py-3 text-white shadow-lg"
            onclick={() => (ticketOpen = true)}
        >
            <span class="text-sm font-semibold">{itemCount} item · Lihat pesanan</span>
            <span class="font-bold tabular-nums">{rupiah(total)}</span>
        </button>
    {/if}
    {#if ticketOpen}
        <div class="fixed inset-0 z-40 bg-black/40" role="presentation" onclick={() => (ticketOpen = false)}></div>
        <div class="fixed inset-x-0 bottom-0 z-50 max-h-[90vh] overflow-y-auto rounded-t-3xl bg-white p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-2xl">
            <div class="mb-2 flex justify-center">
                <button type="button" class="h-1.5 w-12 rounded-full bg-stone-300" aria-label="Tutup pesanan" onclick={() => (ticketOpen = false)}></button>
            </div>
            {@render ticket()}
        </div>
    {/if}
</div>
