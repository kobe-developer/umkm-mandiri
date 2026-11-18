<script>
    import PageTitle from '../../../components/PageTitle.svelte';
    import { Link } from '@inertiajs/svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import Printer from '@lucide/svelte/icons/printer';
    import { rupiah, dateTime } from '../../../lib/format.js';

    /** Struk 80mm — tampil di layar & dicetak dari browser. */
    let { order, store } = $props();
</script>

<PageTitle title={`Struk ${order.order_number}`} />

<div class="min-h-dvh bg-paper-deep py-6 print:bg-white print:py-0">
    <div class="no-print mx-auto mb-5 flex max-w-[80mm] gap-2 px-2">
        <Link href={`/pos/orders/${order.order_number}`} class="btn-secondary flex-1"><ChevronLeft size={17} />Kembali</Link>
        <button type="button" class="btn-primary flex-1" onclick={() => window.print()}><Printer size={16} />Cetak</button>
    </div>

    <article class="receipt mx-auto w-[80mm] bg-white px-4 py-5 font-mono text-[12px] leading-snug text-black shadow-float print:shadow-none">
        <header class="text-center">
            <p class="text-sm font-bold uppercase">{store.store_name}</p>
            {#if store.address}<p>{store.address}</p>{/if}
            {#if store.whatsapp_number}<p>WA +{store.whatsapp_number}</p>{/if}
        </header>

        <div class="my-2 border-t border-dashed border-black"></div>
        <p>No   : {order.order_number}</p>
        <p>Tgl  : {dateTime(order.created_at)}</p>
        <p>Tipe : {order.channel_label}{order.table_number ? ` · Meja ${order.table_number}` : ''}{order.fulfillment_label ? ` · ${order.fulfillment_label}` : ''}</p>
        {#if order.customer_name}<p>Nama : {order.customer_name}</p>{/if}
        {#if order.cashier}<p>Kasir: {order.cashier.name}</p>{/if}
        <div class="my-2 border-t border-dashed border-black"></div>

        {#each order.items as item (item.id)}
            <div class="mb-1">
                <p>{item.product_name}</p>
                <div class="flex justify-between"><span>{item.quantity} x {rupiah(item.unit_price)}{#if item.is_promo} (promo){/if}</span><span>{rupiah(item.subtotal)}</span></div>
                {#if item.notes}<p class="text-[11px]">  * {item.notes}</p>{/if}
            </div>
        {/each}

        <div class="my-2 border-t border-dashed border-black"></div>
        <div class="flex justify-between"><span>Subtotal</span><span>{rupiah(order.subtotal)}</span></div>
        {#if order.discount > 0}<div class="flex justify-between"><span>Diskon</span><span>-{rupiah(order.discount)}</span></div>{/if}
        <div class="flex justify-between text-sm font-bold"><span>TOTAL</span><span>{rupiah(order.total)}</span></div>
        {#if order.payment_method}<div class="flex justify-between"><span>Bayar ({order.payment_method.name})</span><span>{rupiah(order.amount_received ?? order.total)}</span></div>{/if}
        {#if order.change_amount !== null && order.change_amount !== undefined}<div class="flex justify-between"><span>Kembali</span><span>{rupiah(order.change_amount)}</span></div>{/if}
        <p class="mt-1 text-center font-bold">{order.payment_status_label.toUpperCase()}</p>

        <div class="my-2 border-t border-dashed border-black"></div>
        {#if store.receipt_note}<p class="text-center">{store.receipt_note}</p>{/if}
    </article>
</div>

<style>
    @media print {
        :global(body) {
            margin: 0;
        }
        .receipt {
            width: 100%;
            padding: 0 2mm;
        }
    }
    @page {
        size: 80mm auto;
        margin: 3mm;
    }
</style>
