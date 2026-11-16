<script>
    import { onMount } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import StatusTimeline from '../../components/menu/StatusTimeline.svelte';
    import PaymentInfo from '../../components/menu/PaymentInfo.svelte';
    import StatusBadge from '../../components/StatusBadge.svelte';
    import { rupiah, dateTime } from '../../lib/format.js';
    import { cart } from '../../lib/cart.svelte.js';

    let { order, isNew = false, storeWhatsapp = null, paymentMethod = null } = $props();

    const REFRESH_MS = 15000;
    const FINAL_STATUSES = ['selesai', 'dibatalkan'];

    const isFinal = $derived(FINAL_STATUSES.includes(order.status));
    const storeChatUrl = $derived(
        storeWhatsapp ? `https://wa.me/${storeWhatsapp}?text=${encodeURIComponent(`Halo, saya mau tanya pesanan ${order.order_number} atas nama ${order.customer_name}.`)}` : null,
    );
    const showPaymentInfo = $derived(order.payment_status === 'belum_bayar' && order.status !== 'dibatalkan');

    onMount(() => {
        if (isNew) cart.clear();
    });

    // Perbarui status otomatis selama pesanan belum final
    $effect(() => {
        if (isFinal) return;
        const timer = setInterval(() => {
            if (document.visibilityState === 'visible') {
                router.reload({ only: ['order'], preserveScroll: true, showProgress: false });
            }
        }, REFRESH_MS);
        return () => clearInterval(timer);
    });
</script>

<svelte:head><title>Pesanan {order.order_number}</title></svelte:head>

<div class="min-h-screen bg-stone-50 pb-10">
    <header class="bg-gradient-to-br from-brand-600 to-orange-500 px-4 pt-8 pb-16 text-center text-white">
        {#if isNew}
            <div class="mx-auto mb-2 flex size-14 items-center justify-center rounded-full bg-white text-3xl" aria-hidden="true">✅</div>
            <h1 class="text-xl font-bold">Pesanan terkirim!</h1>
            <p class="mx-auto mt-1 max-w-xs text-sm text-white/90">Pesananmu akan dikonfirmasi via WhatsApp oleh kasir.</p>
        {:else}
            <h1 class="text-xl font-bold">Status Pesanan</h1>
        {/if}
        <p class="mt-3 text-xs tracking-wide text-white/80 uppercase">Nomor order</p>
        <p class="font-mono text-2xl font-black tracking-wider">{order.order_number}</p>
    </header>

    <main class="mx-auto -mt-10 max-w-lg space-y-4 px-4">
        <section class="card p-4">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <StatusBadge status={order.status} label={order.status_label} />
                <StatusBadge status={order.payment_status} label={order.payment_status_label} kind="payment" />
            </div>
            <StatusTimeline {order} />
            {#if !isFinal}
                <p class="mt-4 text-center text-xs text-stone-400">Halaman ini diperbarui otomatis. Simpan link ini untuk cek status.</p>
            {/if}
        </section>

        <section class="card p-4 text-sm">
            <h2 class="mb-2 font-semibold">Ringkasan pesanan</h2>
            <ul class="divide-y divide-stone-100">
                {#each order.items ?? [] as item (item.id)}
                    <li class="flex justify-between gap-3 py-2">
                        <div class="min-w-0">
                            <p>{item.product_name} <span class="text-stone-500">× {item.quantity}</span></p>
                            {#if item.notes}<p class="text-xs text-stone-500">Catatan: {item.notes}</p>{/if}
                        </div>
                        <p class="tabular-nums">{rupiah(item.subtotal)}</p>
                    </li>
                {/each}
            </ul>
            {#if order.discount > 0}
                <div class="flex justify-between border-t border-stone-100 pt-2 text-stone-600">
                    <span>Diskon</span><span class="tabular-nums">-{rupiah(order.discount)}</span>
                </div>
            {/if}
            <div class="flex justify-between border-t border-stone-200 pt-2 text-base font-bold">
                <span>Total</span><span class="tabular-nums">{rupiah(order.total)}</span>
            </div>

            <dl class="mt-4 grid grid-cols-3 gap-x-3 gap-y-1.5 text-stone-600">
                <dt class="text-stone-400">Nama</dt><dd class="col-span-2">{order.customer_name}</dd>
                <dt class="text-stone-400">WhatsApp</dt><dd class="col-span-2">{order.whatsapp_number}</dd>
                <dt class="text-stone-400">Pengambilan</dt><dd class="col-span-2">{order.fulfillment_label ?? '-'}</dd>
                {#if order.address}<dt class="text-stone-400">Alamat</dt><dd class="col-span-2">{order.address}</dd>{/if}
                {#if order.notes}<dt class="text-stone-400">Catatan</dt><dd class="col-span-2">{order.notes}</dd>{/if}
                <dt class="text-stone-400">Waktu</dt><dd class="col-span-2">{dateTime(order.created_at)}</dd>
            </dl>
        </section>

        {#if paymentMethod || order.payment_method}
            <section class="card space-y-2 p-4 text-sm">
                <h2 class="font-semibold">Pembayaran</h2>
                {#if order.payment_status === 'lunas'}
                    <p class="text-emerald-700">Lunas{order.payment_method ? ` via ${order.payment_method.name}` : ''}. Terima kasih!</p>
                {:else if paymentMethod}
                    <p class="text-stone-600">Rencana bayar: <strong>{paymentMethod.name}</strong></p>
                    {#if showPaymentInfo}<PaymentInfo method={paymentMethod} />{/if}
                {/if}
            </section>
        {/if}

        <div class="flex flex-col gap-2 sm:flex-row">
            {#if storeChatUrl}
                <a href={storeChatUrl} target="_blank" rel="noopener noreferrer" class="btn flex-1 bg-emerald-600 text-white hover:bg-emerald-700">
                    💬 Chat toko via WhatsApp
                </a>
            {/if}
            <a href="/menu" class="btn-secondary flex-1">Pesan lagi</a>
        </div>
    </main>
</div>
