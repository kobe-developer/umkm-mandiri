<script>
    import { Link, page, router } from '@inertiajs/svelte';
    import StatusBadge from '../../../components/StatusBadge.svelte';
    import OrderItemsTable from '../../../components/pos/OrderItemsTable.svelte';
    import ErrorSummary from '../../../components/pos/ErrorSummary.svelte';
    import PayModal from '../../../components/pos/PayModal.svelte';
    import ReasonModal from '../../../components/pos/ReasonModal.svelte';
    import EditItemsModal from '../../../components/pos/EditItemsModal.svelte';
    import { dateTime } from '../../../lib/format.js';

    let { order, statusUrl, whatsappLink, paymentMethods, products, isOnline } = $props();

    let payOpen = $state(false);
    let cancelOpen = $state(false);
    let voidOpen = $state(false);
    let editOpen = $state(false);
    let busy = $state(false);

    const base = $derived(`/pos/orders/${order.order_number}`);
    const isOwner = $derived(page.props.auth.user?.is_owner ?? false);
    const hasShift = $derived(Boolean(page.props.pos?.open_shift));
    const isFinal = $derived(order.status === 'selesai' || order.status === 'dibatalkan');
    const isUnpaid = $derived(order.payment_status === 'belum_bayar');
    const isPaid = $derived(order.payment_status === 'lunas');
    const isNew = $derived(order.status === 'baru');
    const nextIsComplete = $derived(order.next_status === 'selesai');
    const canAdvance = $derived(!isFinal && order.next_status && (!nextIsComplete || isPaid));
    const canComplete = $derived(isPaid && order.status === 'diproses');
    const canPay = $derived(isUnpaid && !isNew && order.status !== 'dibatalkan');

    function post(action) {
        busy = true;
        router.post(`${base}/${action}`, {}, { preserveScroll: true, onFinish: () => (busy = false) });
    }
</script>

<svelte:head><title>Order {order.order_number}</title></svelte:head>

<div class="mb-4 flex flex-wrap items-center gap-2">
    <Link href={isOnline ? '/pos/online' : '/pos/orders'} class="btn-ghost btn-sm">← Kembali</Link>
    <h1 class="text-xl font-bold">{order.order_number}</h1>
    <StatusBadge status={order.status} label={order.status_label} />
    <StatusBadge kind="payment" status={order.payment_status} label={order.payment_status_label} />
</div>

<ErrorSummary errors={page.props.errors} />

<div class="grid gap-4 lg:grid-cols-[1fr_340px]">
    <section class="card p-4">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="font-semibold">Item</h2>
            {#if isOnline && isNew}
                <button type="button" class="btn-secondary btn-sm" onclick={() => (editOpen = true)}>Ubah item</button>
            {/if}
        </div>
        <OrderItemsTable {order} />
        {#if order.notes}<p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">Catatan: {order.notes}</p>{/if}
    </section>

    <aside class="space-y-4">
        <section class="card space-y-2 p-4 text-sm">
            <div class="flex justify-between"><span class="text-stone-500">Kanal</span><span class="font-medium">{order.channel_label}</span></div>
            {#if order.table_number}<div class="flex justify-between"><span class="text-stone-500">Meja</span><span class="font-medium">{order.table_number}</span></div>{/if}
            {#if order.customer_name}<div class="flex justify-between"><span class="text-stone-500">Pelanggan</span><span class="font-medium">{order.customer_name}</span></div>{/if}
            {#if order.whatsapp_number}<div class="flex justify-between"><span class="text-stone-500">WhatsApp</span><span class="font-medium">+{order.whatsapp_number}</span></div>{/if}
            {#if order.fulfillment_label}<div class="flex justify-between"><span class="text-stone-500">Pengambilan</span><span class="font-medium">{order.fulfillment_label}</span></div>{/if}
            {#if order.address}<div><span class="text-stone-500">Alamat/patokan</span><p class="font-medium">{order.address}</p></div>{/if}
            {#if order.planned_payment_method}<div class="flex justify-between"><span class="text-stone-500">Rencana bayar</span><span class="font-medium">{order.planned_payment_method.name}</span></div>{/if}
            {#if order.payment_method}<div class="flex justify-between"><span class="text-stone-500">Dibayar via</span><span class="font-medium">{order.payment_method.name}</span></div>{/if}
            {#if order.cashier}<div class="flex justify-between"><span class="text-stone-500">Kasir</span><span class="font-medium">{order.cashier.name}</span></div>{/if}
            <div class="flex justify-between"><span class="text-stone-500">Dibuat</span><span>{dateTime(order.created_at)}</span></div>
            {#if order.paid_at}<div class="flex justify-between"><span class="text-stone-500">Dibayar</span><span>{dateTime(order.paid_at)}</span></div>{/if}
            {#if order.completed_at}<div class="flex justify-between"><span class="text-stone-500">Selesai</span><span>{dateTime(order.completed_at)}</span></div>{/if}
            {#if order.cancel_reason}<p class="rounded-xl bg-red-50 px-3 py-2 text-red-700">{order.voided_at ? 'Void' : 'Dibatalkan'}: {order.cancel_reason}</p>{/if}
        </section>

        <section class="card space-y-2 p-4">
            {#if canPay && !hasShift}
                <p class="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">Buka shift untuk mencatat pembayaran. <Link href="/pos/shift" class="font-semibold underline">Buka shift</Link></p>
            {/if}
            {#if isNew}
                <button type="button" class="btn-primary w-full" disabled={busy} onclick={() => post('advance')}>✓ Konfirmasi order</button>
            {:else if canAdvance}
                <button type="button" class="btn-primary w-full" disabled={busy} onclick={() => post('advance')}>Tandai {order.next_status_label}</button>
            {/if}
            {#if canPay}
                <button type="button" class="btn-success w-full" disabled={!hasShift} onclick={() => (payOpen = true)}>💵 Tandai bayar</button>
            {/if}
            {#if canComplete}
                <button type="button" class="btn-secondary w-full" disabled={busy} onclick={() => post('complete')}>Langsung selesai</button>
            {/if}
            {#if !isFinal && isUnpaid && nextIsComplete}
                <p class="text-xs text-stone-500">Order harus lunas sebelum ditandai selesai.</p>
            {/if}
            {#if whatsappLink}
                <a href={whatsappLink} target="_blank" rel="noopener noreferrer" class="btn w-full bg-[#25D366] text-white hover:brightness-95">💬 Chat WA</a>
            {/if}
            <div class="grid grid-cols-2 gap-2">
                <Link href={`${base}/struk`} class="btn-secondary">🧾 Struk</Link>
                {#if statusUrl}
                    <a href={statusUrl} target="_blank" rel="noopener noreferrer" class="btn-secondary">Halaman status</a>
                {/if}
            </div>
            {#if !isFinal && isUnpaid}
                <button type="button" class="btn-danger w-full" onclick={() => (cancelOpen = true)}>{isNew ? 'Tolak order' : 'Batalkan order'}</button>
            {/if}
            {#if isPaid}
                <button type="button" class="btn-danger w-full" onclick={() => (voidOpen = true)}>Void / refund{isOwner ? '' : ' (PIN owner)'}</button>
            {/if}
        </section>
    </aside>
</div>

<PayModal bind:open={payOpen} {order} methods={paymentMethods} />
<ReasonModal
    bind:open={cancelOpen}
    title={isNew ? 'Tolak order' : 'Batalkan order'}
    url={`${base}/cancel`}
    confirmLabel={isNew ? 'Tolak order' : 'Batalkan'}
    presets={isNew ? ['Menu habis', 'Di luar area delivery', 'Order iseng/tidak bisa dihubungi'] : ['Pelanggan batal', 'Salah input']}
/>
<ReasonModal bind:open={voidOpen} title="Void / refund order" url={`${base}/void`} confirmLabel="Void order" requirePin={!isOwner} presets={['Salah input', 'Refund pelanggan']} />
{#if isOnline && isNew}
    <EditItemsModal bind:open={editOpen} {order} {products} />
{/if}
