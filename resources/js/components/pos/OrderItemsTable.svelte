<script>
    import { rupiah } from '../../lib/format.js';

    /** Daftar item + subtotal/diskon/total sebuah order. */
    let { order } = $props();
</script>

<ul class="divide-y divide-stone-100">
    {#each order.items ?? [] as item (item.id)}
        <li class="flex items-start justify-between gap-3 py-2">
            <div class="min-w-0">
                <p class="font-medium">{item.product_name}</p>
                <p class="text-xs text-stone-500">{item.quantity} × {rupiah(item.unit_price)}</p>
                {#if item.notes}<p class="text-xs text-amber-700 italic">“{item.notes}”</p>{/if}
            </div>
            <p class="font-semibold tabular-nums">{rupiah(item.subtotal)}</p>
        </li>
    {/each}
</ul>
<dl class="mt-2 space-y-1 border-t border-stone-200 pt-2 text-sm">
    <div class="flex justify-between"><dt class="text-stone-500">Subtotal</dt><dd class="tabular-nums">{rupiah(order.subtotal)}</dd></div>
    {#if order.discount > 0}
        <div class="flex justify-between text-emerald-700">
            <dt>Diskon{order.discount_type === 'percent' ? ` (${order.discount_value}%)` : ''}</dt>
            <dd class="tabular-nums">−{rupiah(order.discount)}</dd>
        </div>
    {/if}
    <div class="flex justify-between text-base font-bold"><dt>Total</dt><dd class="tabular-nums">{rupiah(order.total)}</dd></div>
    {#if order.amount_received !== null && order.amount_received !== undefined}
        <div class="flex justify-between text-stone-500"><dt>Uang diterima</dt><dd class="tabular-nums">{rupiah(order.amount_received)}</dd></div>
        <div class="flex justify-between text-stone-500"><dt>Kembalian</dt><dd class="tabular-nums">{rupiah(order.change_amount ?? 0)}</dd></div>
    {/if}
</dl>
