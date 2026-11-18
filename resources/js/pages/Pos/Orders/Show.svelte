<script>
    import PageTitle from '../../../components/PageTitle.svelte';
    import { Link, page, router } from '@inertiajs/svelte';
    import StatusBadge from '../../../components/StatusBadge.svelte';
    import OrderItemsTable from '../../../components/pos/OrderItemsTable.svelte';
    import ErrorSummary from '../../../components/pos/ErrorSummary.svelte';
    import PayModal from '../../../components/pos/PayModal.svelte';
    import ReasonModal from '../../../components/pos/ReasonModal.svelte';
    import EditItemsModal from '../../../components/pos/EditItemsModal.svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import Check from '@lucide/svelte/icons/check';
    import Banknote from '@lucide/svelte/icons/banknote';
    import MessageCircle from '@lucide/svelte/icons/message-circle';
    import Printer from '@lucide/svelte/icons/printer';
    import ExternalLink from '@lucide/svelte/icons/external-link';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import SquarePen from '@lucide/svelte/icons/square-pen';
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

<PageTitle title={`Order ${order.order_number}`} />

{#snippet info(label, value)}
    <div class="flex justify-between gap-4 py-2.5"><dt class="shrink-0 text-muted">{label}</dt><dd class="text-right font-medium text-ink">{value}</dd></div>
{/snippet}

<div class="mb-6">
    <Link href={isOnline ? '/pos/online' : '/pos/orders'} class="-ml-1 mb-3 inline-flex min-h-9 items-center gap-1 rounded-full pr-3 pl-1 text-sm font-medium text-muted hover:text-ink">
        <ChevronLeft size={18} />{isOnline ? 'Order online' : 'Transaksi'}
    </Link>
    <h1 class="display num text-[1.75rem] leading-tight text-ink sm:text-[2rem]">{order.order_number}</h1>
    <div class="mt-2 flex flex-wrap items-center gap-1.5">
        <StatusBadge status={order.status} label={order.status_label} />
        <StatusBadge kind="payment" status={order.payment_status} label={order.payment_status_label} />
        <span class="ml-1 text-xs text-muted">{order.channel_label}{order.table_number ? ` · Meja ${order.table_number}` : ''}</span>
    </div>
</div>

<ErrorSummary errors={page.props.errors} />

<div class="grid gap-3 lg:grid-cols-[1fr_340px] lg:items-start">
    <div class="space-y-3">
        <section class="card p-4 sm:p-5">
            <div class="mb-1 flex items-center justify-between">
                <h2 class="font-semibold text-ink">Item</h2>
                {#if isOnline && isNew}
                    <button type="button" class="btn-secondary btn-sm" onclick={() => (editOpen = true)}><SquarePen size={14} />Ubah item</button>
                {/if}
            </div>
            <OrderItemsTable {order} />
            {#if order.notes}<p class="mt-4 rounded-xl bg-amber-soft px-3.5 py-2.5 text-sm text-amber-ink">Catatan: {order.notes}</p>{/if}
        </section>

        <section class="card px-4 py-2 text-sm sm:px-5">
            <dl class="divide-y divide-line">
                {@render info('Kanal', order.channel_label)}
                {#if order.table_number}{@render info('Meja', order.table_number)}{/if}
                {#if order.customer_name}{@render info('Pelanggan', order.customer_name)}{/if}
                {#if order.whatsapp_number}{@render info('WhatsApp', `+${order.whatsapp_number}`)}{/if}
                {#if order.fulfillment_label}{@render info('Pengambilan', order.fulfillment_label)}{/if}
                {#if order.address}{@render info('Alamat/patokan', order.address)}{/if}
                {#if order.planned_payment_method}{@render info('Rencana bayar', order.planned_payment_method.name)}{/if}
                {#if order.payment_method}{@render info('Dibayar via', order.payment_method.name)}{/if}
                {#if order.cashier}{@render info('Kasir', order.cashier.name)}{/if}
                {@render info('Dibuat', dateTime(order.created_at))}
                {#if order.paid_at}{@render info('Dibayar', dateTime(order.paid_at))}{/if}
                {#if order.completed_at}{@render info('Selesai', dateTime(order.completed_at))}{/if}
            </dl>
            {#if order.cancel_reason}<p class="mb-3 rounded-xl bg-brand-50 px-3.5 py-2.5 text-brand-700">{order.voided_at ? 'Void' : 'Dibatalkan'}: {order.cancel_reason}</p>{/if}
        </section>
    </div>

    <aside class="card space-y-2.5 p-4 sm:p-5 lg:sticky lg:top-8">
        <h2 class="eyebrow pb-1">Tindakan</h2>
        {#if canPay && !hasShift}
            <p class="rounded-xl bg-amber-soft px-3.5 py-2.5 text-xs text-amber-ink">Buka shift untuk mencatat pembayaran. <Link href="/pos/shift" class="font-semibold underline underline-offset-2">Buka shift</Link></p>
        {/if}
        {#if isNew}
            <button type="button" class="btn-accent min-h-12 w-full" disabled={busy} onclick={() => post('advance')}><Check size={18} strokeWidth={2.5} />Konfirmasi order</button>
        {:else if canAdvance}
            <button type="button" class="btn-primary min-h-12 w-full" disabled={busy} onclick={() => post('advance')}>Tandai {order.next_status_label}<ArrowRight size={17} /></button>
        {/if}
        {#if canPay}
            <button type="button" class="btn-success min-h-12 w-full" disabled={!hasShift} onclick={() => (payOpen = true)}><Banknote size={18} />Tandai bayar</button>
        {/if}
        {#if canComplete}
            <button type="button" class="btn-secondary w-full" disabled={busy} onclick={() => post('complete')}>Langsung selesai</button>
        {/if}
        {#if !isFinal && isUnpaid && nextIsComplete}
            <p class="text-xs text-muted">Order harus lunas sebelum ditandai selesai.</p>
        {/if}
        {#if whatsappLink}
            <a href={whatsappLink} target="_blank" rel="noopener noreferrer" class="btn-secondary w-full"><MessageCircle size={17} class="text-leaf-600" />Chat WhatsApp</a>
        {/if}
        <div class="grid grid-cols-2 gap-2">
            <Link href={`${base}/struk`} class="btn-secondary"><Printer size={16} />Struk</Link>
            {#if statusUrl}
                <a href={statusUrl} target="_blank" rel="noopener noreferrer" class="btn-secondary"><ExternalLink size={16} />Status</a>
            {/if}
        </div>
        {#if (!isFinal && isUnpaid) || isPaid}
            <div class="space-y-2 border-t border-line pt-3">
                {#if !isFinal && isUnpaid}
                    <button type="button" class="btn-danger w-full" onclick={() => (cancelOpen = true)}>{isNew ? 'Tolak order' : 'Batalkan order'}</button>
                {/if}
                {#if isPaid}
                    <button type="button" class="btn-danger w-full" onclick={() => (voidOpen = true)}>Void / refund{isOwner ? '' : ' (PIN owner)'}</button>
                {/if}
            </div>
        {/if}
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
