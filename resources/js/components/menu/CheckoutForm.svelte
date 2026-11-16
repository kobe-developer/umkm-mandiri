<script>
    import { useForm } from '@inertiajs/svelte';
    import PaymentInfo from './PaymentInfo.svelte';
    import { rupiah, normalizePhone } from '../../lib/format.js';
    import { cart } from '../../lib/cart.svelte.js';

    /** Form checkout tanpa login: nama, WA, pengambilan, alamat, rencana bayar, catatan. */
    let { productMap, settings, paymentMethods, blockedReason = null, onback } = $props();

    const CUSTOMER_KEY = 'umkm-customer-v1';

    function rememberedCustomer() {
        try {
            return JSON.parse(localStorage.getItem(CUSTOMER_KEY) ?? '{}') ?? {};
        } catch {
            return {};
        }
    }

    // Nilai awal form (nama/WA/alamat diingat dari pesanan sebelumnya di perangkat ini)
    const form = useForm(() => {
        const saved = rememberedCustomer();
        return {
            customer_name: saved.customer_name ?? '',
            whatsapp_number: saved.whatsapp_number ?? '',
            fulfillment: settings.allow_pickup ? 'pickup' : 'delivery',
            address: saved.address ?? '',
            payment_method_id: paymentMethods.length === 1 ? paymentMethods[0].id : null,
            notes: '',
            consent: false,
        };
    });

    const subtotal = $derived(cart.subtotal(productMap));
    const itemCount = $derived(cart.count());
    const normalizedPhone = $derived(normalizePhone(form.whatsapp_number));
    const selectedMethod = $derived(paymentMethods.find((m) => m.id === form.payment_method_id));
    const itemErrors = $derived(Object.entries(form.errors).filter(([key]) => key === 'items' || key.startsWith('items.')).map(([, message]) => message));

    function remember() {
        try {
            localStorage.setItem(CUSTOMER_KEY, JSON.stringify({
                customer_name: form.customer_name,
                whatsapp_number: form.whatsapp_number,
                address: form.address,
            }));
        } catch {
            // abaikan: penyimpanan tidak tersedia
        }
    }

    function submit(event) {
        event.preventDefault();
        if (blockedReason) return;
        remember();
        form
            .transform((data) => ({
                ...data,
                address: data.fulfillment === 'delivery' ? data.address : null,
                items: cart.payload(),
            }))
            .post('/menu/checkout', {
                preserveScroll: true,
                onSuccess: () => cart.clear(),
            });
    }
</script>

<form class="mx-auto max-w-2xl px-4 pt-4 pb-36" onsubmit={submit} novalidate>
    <div class="mb-4 flex items-center gap-2">
        <button type="button" class="btn-ghost btn-sm text-lg" aria-label="Kembali ke keranjang" onclick={onback}>←</button>
        <h2 class="text-lg font-bold">Checkout</h2>
    </div>

    {#if form.errors.store || itemErrors.length}
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">
            {#if form.errors.store}<p>{form.errors.store}</p>{/if}
            {#each itemErrors as message (message)}<p>{message}</p>{/each}
            {#if itemErrors.length}
                <button type="button" class="mt-1 font-semibold underline" onclick={onback}>Perbarui keranjang</button>
            {/if}
        </div>
    {/if}

    <section class="card space-y-4 p-4">
        <div>
            <label class="label" for="customer_name">Nama <span class="text-red-600">*</span></label>
            <input id="customer_name" class="input" bind:value={form.customer_name} minlength="2" maxlength="50" autocomplete="name" required />
            {#if form.errors.customer_name}<p class="error">{form.errors.customer_name}</p>{/if}
        </div>

        <div>
            <label class="label" for="whatsapp_number">Nomor WhatsApp <span class="text-red-600">*</span></label>
            <input id="whatsapp_number" class="input" type="tel" inputmode="tel" bind:value={form.whatsapp_number} placeholder="08xx / 62xx / +62xx" autocomplete="tel" required />
            {#if form.errors.whatsapp_number}
                <p class="error">{form.errors.whatsapp_number}</p>
            {:else if form.whatsapp_number && normalizedPhone}
                <p class="mt-1 text-xs text-emerald-700">Akan dihubungi di {normalizedPhone}</p>
            {:else if form.whatsapp_number}
                <p class="mt-1 text-xs text-amber-700">Format nomor belum valid (contoh: 081234567890).</p>
            {/if}
        </div>

        <fieldset>
            <legend class="label">Tipe pengambilan <span class="text-red-600">*</span></legend>
            <div class="grid grid-cols-2 gap-2">
                {#if settings.allow_pickup}
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-3 text-sm {form.fulfillment === 'pickup' ? 'border-brand-500 bg-brand-50' : 'border-stone-300'}">
                        <input type="radio" name="fulfillment" value="pickup" class="accent-brand-600" bind:group={form.fulfillment} />
                        Ambil Sendiri
                    </label>
                {/if}
                {#if settings.allow_delivery}
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-3 text-sm {form.fulfillment === 'delivery' ? 'border-brand-500 bg-brand-50' : 'border-stone-300'}">
                        <input type="radio" name="fulfillment" value="delivery" class="accent-brand-600" bind:group={form.fulfillment} />
                        Diantar
                    </label>
                {/if}
            </div>
            {#if form.fulfillment === 'delivery' && settings.delivery_note}
                <p class="mt-1 text-xs text-emerald-700">🛵 {settings.delivery_note}</p>
            {/if}
            {#if form.errors.fulfillment}<p class="error">{form.errors.fulfillment}</p>{/if}
        </fieldset>

        {#if form.fulfillment === 'delivery'}
            <div>
                <label class="label" for="address">Alamat / patokan <span class="text-red-600">*</span></label>
                <textarea id="address" class="input" rows="2" maxlength="255" bind:value={form.address} placeholder="Misal: Kos Melati kamar 5, depan gerbang belakang" required></textarea>
                {#if form.errors.address}<p class="error">{form.errors.address}</p>{/if}
            </div>
        {/if}
    </section>

    <section class="card mt-4 space-y-3 p-4">
        <fieldset>
            <legend class="label">Rencana metode bayar <span class="text-red-600">*</span></legend>
            {#if paymentMethods.length === 0}
                <p class="text-sm text-stone-500">Belum ada metode pembayaran untuk order online.</p>
            {/if}
            <div class="grid gap-2 sm:grid-cols-2">
                {#each paymentMethods as method (method.id)}
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-3 text-sm {form.payment_method_id === method.id ? 'border-brand-500 bg-brand-50' : 'border-stone-300'}">
                        <input type="radio" name="payment_method_id" value={method.id} class="accent-brand-600" bind:group={form.payment_method_id} />
                        {method.name}
                    </label>
                {/each}
            </div>
            {#if form.errors.payment_method_id}<p class="error">{form.errors.payment_method_id}</p>{/if}
        </fieldset>
        <PaymentInfo method={selectedMethod} />
        <p class="text-xs text-stone-500">Pembayaran dilakukan saat konfirmasi / pengambilan pesanan.</p>
    </section>

    <section class="card mt-4 space-y-3 p-4">
        <div>
            <label class="label" for="notes">Catatan untuk toko <span class="font-normal text-stone-400">(opsional)</span></label>
            <textarea id="notes" class="input" rows="2" maxlength="255" bind:value={form.notes} placeholder="Misal: tolong sendok plastik"></textarea>
            {#if form.errors.notes}<p class="error">{form.errors.notes}</p>{/if}
        </div>
        <label class="flex items-start gap-2 text-sm text-stone-700">
            <input type="checkbox" class="checkbox mt-0.5" bind:checked={form.consent} />
            <span>Saya setuju nomor WA dipakai untuk konfirmasi pesanan dan pengiriman struk.</span>
        </label>
        {#if form.errors.consent}<p class="error">{form.errors.consent}</p>{/if}
    </section>

    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200 bg-white px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
        <div class="mx-auto max-w-2xl">
            <div class="mb-2 flex justify-between text-sm">
                <span class="text-stone-500">{itemCount} item</span>
                <span class="font-bold tabular-nums">Total {rupiah(subtotal)}</span>
            </div>
            {#if blockedReason}
                <p class="mb-2 text-center text-sm font-semibold text-red-600">{blockedReason}</p>
            {/if}
            <button type="submit" class="btn-primary w-full py-3 text-base" disabled={form.processing || !!blockedReason || itemCount === 0 || !form.consent}>
                {form.processing ? 'Mengirim pesanan…' : 'Kirim pesanan'}
            </button>
        </div>
    </div>
</form>
