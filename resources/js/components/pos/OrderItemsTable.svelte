<script>
    import { rupiah } from '../../lib/format.js';

    /** Daftar item + subtotal/diskon/total sebuah order. */
    let { order } = $props();
</script>

<ul class="divide-y divide-line">
    {#each order.items ?? [] as item (item.id)}
        <li class="flex items-start justify-between gap-3 py-3">
            <div class="min-w-0">
                <p class="font-medium text-ink">{item.product_name}</p>
                <p class="num text-xs text-muted">{item.quantity} × {rupiah(item.unit_price)}</p>
                {#if item.notes}<p class="mt-0.5 text-xs text-amber-ink italic">“{item.notes}”</p>{/if}
            </div>
            <p class="num font-semibold text-ink">{rupiah(item.subtotal)}</p>
        </li>
    {/each}
</ul>
<dl class="mt-1 space-y-1.5 border-t border-dashed border-line-strong pt-3 text-sm">
    <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="num text-ink-soft">{rupiah(order.subtotal)}</dd></div>
    {#if order.discount > 0}
        <div class="flex justify-between text-leaf-600">
            <dt>Diskon{order.discount_type === 'percent' ? ` (${order.discount_value}%)` : ''}</dt>
            <dd class="num">−{rupiah(order.discount)}</dd>
        </div>
    {/if}
    <div class="flex items-baseline justify-between pt-1"><dt class="font-semibold text-ink">Total</dt><dd class="num text-xl font-semibold text-ink">{rupiah(order.total)}</dd></div>
    {#if order.amount_received !== null && order.amount_received !== undefined}
        <div class="flex justify-between text-muted"><dt>Uang diterima</dt><dd class="num">{rupiah(order.amount_received)}</dd></div>
        <div class="flex justify-between text-muted"><dt>Kembalian</dt><dd class="num">{rupiah(order.change_amount ?? 0)}</dd></div>
    {/if}
</dl>
