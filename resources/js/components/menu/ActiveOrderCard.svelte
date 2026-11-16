<script>
    import { Link } from '@inertiajs/svelte';
    import Clock from '@lucide/svelte/icons/clock';
    import ChefHat from '@lucide/svelte/icons/chef-hat';
    import Bike from '@lucide/svelte/icons/bike';
    import ShoppingBag from '@lucide/svelte/icons/shopping-bag';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import { ORDER_STEPS, stepIndex, statusHint } from '../../lib/orderStatus.js';
    import { time, dateTime } from '../../lib/format.js';

    /** Kartu order yang sedang berjalan: status terkini, jam masuk status, progres 5 langkah. */
    let { order } = $props();

    const Icon = $derived.by(() => {
        if (order.status === 'baru') return Clock;
        if (order.status === 'siap') return order.fulfillment === 'delivery' ? Bike : ShoppingBag;
        return ChefHat;
    });
    const current = $derived(stepIndex(order.status));
</script>

<Link href={order.status_url} class="card flex items-center gap-3.5 p-3.5 transition hover:border-line-strong active:scale-[0.99]">
    {#key order.status}
        <span class="flex size-11 shrink-0 animate-pop items-center justify-center rounded-full {order.status === 'baru' ? 'bg-amber-soft text-amber-ink' : 'bg-brand-600 text-white'}" aria-hidden="true">
            <Icon size={20} strokeWidth={2.25} />
        </span>
    {/key}

    <div class="min-w-0 flex-1">
        <div class="flex items-baseline justify-between gap-2">
            <p class="truncate text-[15px] font-semibold text-ink">{statusHint(order.status, order.fulfillment)}</p>
            <time datetime={order.status_time} title={dateTime(order.status_time)} class="num shrink-0 text-xs text-muted">sejak {time(order.status_time)}</time>
        </div>

        <div class="mt-2 flex gap-1" role="progressbar" aria-label="Progres pesanan" aria-valuemin="1" aria-valuemax={ORDER_STEPS.length} aria-valuenow={current + 1} aria-valuetext={order.status_label}>
            {#each ORDER_STEPS as step, index (step.value)}
                <span class="h-1 flex-1 rounded-full transition-colors duration-500 {index <= current ? 'bg-brand-600' : 'bg-line-strong'}"></span>
            {/each}
        </div>

        <p class="mt-1.5 truncate text-xs text-muted">
            <span class="num font-mono">{order.order_number}</span> · {order.items_count} item
        </p>
    </div>

    <ChevronRight size={18} class="shrink-0 text-faint" />
</Link>
