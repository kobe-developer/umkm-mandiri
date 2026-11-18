<script>
    import { useForm, page } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import BellRing from '@lucide/svelte/icons/bell-ring';
    import ShoppingBag from '@lucide/svelte/icons/shopping-bag';
    import Bike from '@lucide/svelte/icons/bike';
    import Banknote from '@lucide/svelte/icons/banknote';
    import QrCode from '@lucide/svelte/icons/qr-code';
    import Landmark from '@lucide/svelte/icons/landmark';
    import Wallet from '@lucide/svelte/icons/wallet';
    import CircleCheck from '@lucide/svelte/icons/circle-check';
    import CircleAlert from '@lucide/svelte/icons/circle-alert';
    import Send from '@lucide/svelte/icons/send';
    import PaymentInfo from './PaymentInfo.svelte';
    import FlowHeader from './FlowHeader.svelte';
    import { rupiah, normalizePhone } from '../../lib/format.js';
    import { cart } from '../../lib/cart.svelte.js';
    import { pushSupport, subscribePush } from '../../lib/push.js';

    /** Form checkout tanpa login: nama, WA, pengambilan, alamat, rencana bayar, catatan. */
    let { productMap, settings, paymentMethods, blockedReason = null, onback } = $props();

    const CUSTOMER_KEY = 'umkm-customer-v1';
    const PAYMENT_ICONS = { tunai: Banknote, qris: QrCode, transfer: Landmark, ewallet: Wallet };

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

    // Notifikasi status pesanan (web push), opsional
    const vapidKey = $derived(page.props.config?.vapidPublicKey);
    let pushState = $state('unsupported');
    let wantsPush = $state(false);
    let preparingPush = $state(false);
    onMount(() => {
        pushState = pushSupport(vapidKey);
        wantsPush = pushState === 'granted';
    });
    const canOfferPush = $derived(pushState === 'ready' || pushState === 'granted');

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

    /** Izin notifikasi diminta di dalam gestur submit; gagal/ditolak → pesanan tetap dikirim tanpa notifikasi. */
    async function preparePush() {
        if (!wantsPush || !canOfferPush) return null;
        preparingPush = true;
        try {
            return await subscribePush(vapidKey);
        } catch {
            return null;
        } finally {
            preparingPush = false;
            pushState = pushSupport(vapidKey);
        }
    }

    async function submit(event) {
        event.preventDefault();
        if (blockedReason || form.processing || preparingPush) return;
        remember();
        const pushSubscription = await preparePush();
        form
            .transform((data) => ({
                ...data,
                address: data.fulfillment === 'delivery' ? data.address : null,
                items: cart.payload(),
                push_subscription: pushSubscription,
            }))
            .post('/menu/checkout', {
                preserveScroll: true,
                onSuccess: () => cart.clear(),
            });
    }
</script>

{#snippet choice(group, value, label, Icon, description = '')}
    {@const checked = group === 'fulfillment' ? form.fulfillment === value : form.payment_method_id === value}
    <label class="relative flex min-h-16 cursor-pointer items-center gap-3 rounded-2xl border bg-surface p-3.5 transition {checked ? 'border-ink ring-1 ring-ink' : 'border-line hover:border-line-strong'}">
        {#if group === 'fulfillment'}
            <input type="radio" name="fulfillment" {value} class="sr-only" bind:group={form.fulfillment} />
        {:else}
            <input type="radio" name="payment_method_id" {value} class="sr-only" bind:group={form.payment_method_id} />
        {/if}
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full transition {checked ? 'bg-ink text-paper' : 'bg-paper-deep text-ink-soft'}">
            <Icon size={19} />
        </span>
        <span class="min-w-0">
            <span class="block text-sm font-semibold text-ink">{label}</span>
            {#if description}<span class="block text-xs text-muted">{description}</span>{/if}
        </span>
    </label>
{/snippet}

<FlowHeader title="Checkout" step={2} backLabel="Kembali ke keranjang" {onback} />

<form class="mx-auto max-w-2xl space-y-8 px-5 pt-5 pb-44" onsubmit={submit} novalidate>
    {#if form.errors.store || itemErrors.length}
        <div class="flex gap-3 rounded-2xl bg-brand-50 p-4 text-sm text-brand-700" role="alert">
            <CircleAlert size={18} class="mt-px shrink-0" />
            <div>
                {#if form.errors.store}<p>{form.errors.store}</p>{/if}
                {#each itemErrors as message (message)}<p>{message}</p>{/each}
                {#if itemErrors.length}
                    <button type="button" class="mt-1 font-semibold underline underline-offset-2" onclick={onback}>Perbarui keranjang</button>
                {/if}
            </div>
        </div>
    {/if}

    <section class="space-y-4" aria-labelledby="sec-pemesan">
        <h2 id="sec-pemesan" class="display text-xl text-ink">Data pemesan</h2>
        <div>
            <label class="label" for="customer_name">Nama</label>
            <input id="customer_name" class="input" bind:value={form.customer_name} minlength="2" maxlength="50" autocomplete="name" placeholder="Nama panggilan" required />
            {#if form.errors.customer_name}<p class="error">{form.errors.customer_name}</p>{/if}
        </div>
        <div>
            <label class="label" for="whatsapp_number">Nomor WhatsApp</label>
            <input id="whatsapp_number" class="input num" type="tel" inputmode="tel" bind:value={form.whatsapp_number} placeholder="0812 3456 7890" autocomplete="tel" required />
            {#if form.errors.whatsapp_number}
                <p class="error">{form.errors.whatsapp_number}</p>
            {:else if form.whatsapp_number && normalizedPhone}
                <p class="mt-1.5 flex items-center gap-1 text-xs text-leaf-600"><CircleCheck size={13} />Akan dihubungi di +{normalizedPhone}</p>
            {:else if form.whatsapp_number}
                <p class="mt-1.5 text-xs text-amber-ink">Format nomor belum valid (contoh: 081234567890).</p>
            {:else}
                <p class="hint">Untuk konfirmasi pesanan. Format 08xx, 62xx, atau +62xx.</p>
            {/if}
        </div>
    </section>

    <section class="space-y-3" aria-labelledby="sec-ambil">
        <h2 id="sec-ambil" class="display text-xl text-ink">Pengambilan</h2>
        <fieldset>
            <legend class="sr-only">Tipe pengambilan</legend>
            <div class="grid grid-cols-2 gap-2.5">
                {#if settings.allow_pickup}{@render choice('fulfillment', 'pickup', 'Ambil sendiri', ShoppingBag, 'Di toko')}{/if}
                {#if settings.allow_delivery}{@render choice('fulfillment', 'delivery', 'Diantar', Bike, 'Ke lokasimu')}{/if}
            </div>
            {#if form.fulfillment === 'delivery' && settings.delivery_note}
                <p class="hint">{settings.delivery_note}</p>
            {/if}
            {#if form.errors.fulfillment}<p class="error">{form.errors.fulfillment}</p>{/if}
        </fieldset>

        {#if form.fulfillment === 'delivery'}
            <div class="animate-fade-in">
                <label class="label" for="address">Alamat / patokan</label>
                <textarea id="address" class="input" rows="2" maxlength="255" bind:value={form.address} placeholder="Misal: Kos Melati kamar 5, depan gerbang belakang" required></textarea>
                {#if form.errors.address}<p class="error">{form.errors.address}</p>{/if}
            </div>
        {/if}
    </section>

    <section class="space-y-3" aria-labelledby="sec-bayar">
        <div>
            <h2 id="sec-bayar" class="display text-xl text-ink">Rencana pembayaran</h2>
            <p class="mt-0.5 text-[13px] text-muted">Dibayar saat konfirmasi / pengambilan pesanan.</p>
        </div>
        <fieldset>
            <legend class="sr-only">Rencana metode bayar</legend>
            {#if paymentMethods.length === 0}
                <p class="text-sm text-muted">Belum ada metode pembayaran untuk order online.</p>
            {/if}
            <div class="grid grid-cols-2 gap-2.5">
                {#each paymentMethods as method (method.id)}
                    {@render choice('payment', method.id, method.name, PAYMENT_ICONS[method.type] ?? Wallet)}
                {/each}
            </div>
            {#if form.errors.payment_method_id}<p class="error">{form.errors.payment_method_id}</p>{/if}
        </fieldset>
        <PaymentInfo method={selectedMethod} />
    </section>

    <section class="space-y-4" aria-labelledby="sec-catatan">
        <h2 id="sec-catatan" class="display text-xl text-ink">Catatan <span class="font-sans text-sm font-normal text-faint">opsional</span></h2>
        <div>
            <label class="sr-only" for="notes">Catatan untuk toko</label>
            <textarea id="notes" class="input" rows="2" maxlength="255" bind:value={form.notes} placeholder="Misal: tolong sendok plastik"></textarea>
            {#if form.errors.notes}<p class="error">{form.errors.notes}</p>{/if}
        </div>
        <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-surface p-4 text-sm text-ink-soft ring-1 ring-line">
            <input type="checkbox" class="checkbox mt-px" bind:checked={form.consent} />
            <span>Saya setuju nomor WA dipakai untuk konfirmasi pesanan dan pengiriman struk.</span>
        </label>
        {#if form.errors.consent}<p class="error">{form.errors.consent}</p>{/if}

        {#if canOfferPush}
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-surface p-4 text-sm text-ink-soft ring-1 ring-line">
                <input type="checkbox" class="checkbox mt-px" bind:checked={wantsPush} />
                <span>
                    <span class="flex items-center gap-1.5 font-semibold text-ink"><BellRing size={15} class="text-brand-600" aria-hidden="true" />Kabari saya lewat notifikasi</span>
                    <span class="mt-0.5 block text-xs text-muted">Dapat notifikasi di perangkat ini saat pesanan dikonfirmasi, diproses, siap/diantar, selesai, dan saat pembayaran diterima.</span>
                </span>
            </label>
        {:else if pushState === 'needs-install'}
            <p class="flex items-start gap-2 rounded-2xl bg-paper-deep/60 p-4 text-xs text-muted">
                <BellRing size={15} class="mt-px shrink-0" aria-hidden="true" />
                <span>Ingin notifikasi status pesanan di iPhone? Ketuk Bagikan → "Tambahkan ke Layar Utama", lalu pesan dari aplikasi di layar utama.</span>
            </p>
        {/if}
    </section>

    <div class="safe-bottom fixed inset-x-0 bottom-0 z-30 border-t border-line bg-paper/95 px-4 pt-3 backdrop-blur-xl">
        <div class="mx-auto max-w-2xl">
            <div class="mb-3 flex items-baseline justify-between px-1">
                <span class="text-sm text-muted">{itemCount} item</span>
                <span class="num text-lg font-semibold text-ink">{rupiah(subtotal)}</span>
            </div>
            {#if blockedReason}
                <p class="mb-2 text-center text-sm font-semibold text-brand-600">{blockedReason}</p>
            {/if}
            <button type="submit" class="btn-accent min-h-13 w-full text-base" disabled={form.processing || preparingPush || !!blockedReason || itemCount === 0 || !form.consent}>
                {#if preparingPush}Menyiapkan notifikasi…{:else if form.processing}Mengirim pesanan…{:else}<Send size={18} />Kirim pesanan{/if}
            </button>
            {#if !form.consent && !form.processing}
                <p class="mt-2 text-center text-xs text-muted">Centang persetujuan WA untuk mengirim pesanan.</p>
            {/if}
        </div>
    </div>
</form>
