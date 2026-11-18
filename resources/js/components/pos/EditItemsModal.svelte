<script>
    import { useForm } from '@inertiajs/svelte';
    import Modal from '../Modal.svelte';
    import QtyStepper from '../QtyStepper.svelte';
    import ErrorSummary from './ErrorSummary.svelte';
    import SearchSelect from '../SearchSelect.svelte';
    import { rupiah } from '../../lib/format.js';

    /** Ubah item order online sebelum dikonfirmasi (misal ada yang habis). */
    let { open = $bindable(false), order, products } = $props();

    const productById = $derived(Object.fromEntries(products.map((p) => [p.id, p])));
    let items = $state([]);
    let addId = $state('');

    const form = useForm({});

    $effect(() => {
        if (open) {
            items = order.items.map((i) => ({ product_id: i.product_id, name: i.product_name, price: i.unit_price, quantity: i.quantity, notes: i.notes ?? '' }));
        }
    });

    const total = $derived(items.reduce((sum, i) => sum + (productById[i.product_id]?.price ?? i.price) * i.quantity, 0));
    const addable = $derived(products.filter((p) => !p.is_sold_out && !items.some((i) => i.product_id === p.id)));
    const addOptions = $derived(addable.map((p) => ({ value: p.id, label: p.name, hint: rupiah(p.price) })));

    function setQty(productId, quantity) {
        items = quantity <= 0 ? items.filter((i) => i.product_id !== productId) : items.map((i) => (i.product_id === productId ? { ...i, quantity } : i));
    }

    function addProduct() {
        const product = productById[Number(addId)];
        if (!product) return;
        items = [...items, { product_id: product.id, name: product.name, price: product.price, quantity: 1, notes: '' }];
        addId = '';
    }

    function submit() {
        form
            .transform(() => ({ items: items.map((i) => ({ product_id: i.product_id, quantity: i.quantity, notes: i.notes || null })) }))
            .put(`/pos/orders/${order.order_number}/items`, { preserveScroll: true, onSuccess: () => (open = false) });
    }
</script>

<Modal bind:open title="Ubah item order" size="lg">
    <div class="space-y-3">
        <ErrorSummary errors={form.errors} />
        <ul class="divide-y divide-line">
            {#each items as item (item.product_id)}
                {@const product = productById[item.product_id]}
                <li class="py-3">
                    <div class="flex items-center gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink">{item.name}</p>
                            <p class="text-xs {!product || product.is_sold_out ? 'font-semibold text-brand-600' : 'num text-muted'}">
                                {!product ? 'Tidak tersedia online' : product.is_sold_out ? 'Habis — hapus atau ganti' : rupiah(product.price)}
                            </p>
                        </div>
                        <QtyStepper size="sm" value={item.quantity} onchange={(q) => setQty(item.product_id, q)} />
                    </div>
                    <input class="input mt-2" placeholder="Catatan" maxlength="100" bind:value={item.notes} aria-label="Catatan {item.name}" />
                </li>
            {/each}
        </ul>
        <div class="flex gap-2">
            <SearchSelect
                class="min-w-0 flex-1"
                options={addOptions}
                bind:value={addId}
                ariaLabel="Tambah produk"
                placeholder="+ Tambah produk…"
                searchPlaceholder="Cari nama produk…"
                emptyText="Produk tidak ditemukan"
                searchable={true}
            />
            <button type="button" class="btn-secondary" disabled={!addId} onclick={addProduct}>Tambah</button>
        </div>
        <div class="flex items-baseline justify-between rounded-2xl bg-paper px-4 py-3">
            <span class="text-sm text-muted">Total baru</span>
            <span class="num text-xl font-semibold text-ink">{rupiah(total)}</span>
        </div>
    </div>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (open = false)}>Batal</button>
        <button type="button" class="btn-primary" disabled={form.processing || items.length === 0} onclick={submit}>Simpan perubahan</button>
    {/snippet}
</Modal>
