<script>
    import Check from '@lucide/svelte/icons/check';
    import Ban from '@lucide/svelte/icons/ban';
    import { ORDER_STEPS, stepIndex, statusHint } from '../../lib/orderStatus.js';
    import { time, dateTime } from '../../lib/format.js';

    /** Timeline status order + jam masuk tiap status: Baru → Dikonfirmasi → Diproses → Siap/Diantar → Selesai */
    let { order } = $props();

    const currentIndex = $derived(stepIndex(order.status));
    const times = $derived(order.status_times ?? {});
</script>

{#snippet stamp(value)}
    {#if value}
        <time datetime={value} title={dateTime(value)} class="num shrink-0 text-xs leading-7 text-muted">{time(value)}</time>
    {/if}
{/snippet}

{#if order.status === 'dibatalkan'}
    <div class="flex gap-3 rounded-2xl bg-brand-50 p-4 text-sm text-brand-700">
        <Ban size={18} class="mt-0.5 shrink-0" />
        <div class="min-w-0 flex-1">
            <p class="font-semibold">Pesanan dibatalkan{#if times.dibatalkan}<span class="num font-normal"> · {time(times.dibatalkan)}</span>{/if}</p>
            {#if order.cancel_reason}<p class="mt-0.5">Alasan: {order.cancel_reason}</p>{/if}
        </div>
    </div>
{:else}
    <ol>
        {#each ORDER_STEPS as step, index (step.value)}
            {@const done = index < currentIndex}
            {@const current = index === currentIndex}
            <li class="flex gap-4">
                <div class="flex flex-col items-center">
                    <span
                        class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition
                            {done ? 'bg-ink text-paper' : current ? 'bg-brand-600 text-white ring-4 ring-brand-100' : 'border border-line-strong bg-surface text-faint'}"
                        aria-hidden="true"
                    >
                        {#if done || (current && step.value === 'selesai')}<Check size={14} strokeWidth={3} />{:else}{index + 1}{/if}
                    </span>
                    {#if index < ORDER_STEPS.length - 1}
                        <span class="my-1 w-px flex-1 {done ? 'bg-ink' : 'bg-line-strong'} min-h-5"></span>
                    {/if}
                </div>
                <div class="min-w-0 flex-1 pb-5">
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="text-sm leading-7 {current ? 'font-semibold text-ink' : done ? 'font-medium text-ink-soft' : 'text-faint'}">
                            {step.label}{#if current}<span class="sr-only"> (status saat ini)</span>{/if}
                        </p>
                        {#if done || current}{@render stamp(times[step.value])}{/if}
                    </div>
                    {#if current}
                        <p class="text-[13px] text-muted">{statusHint(step.value, order.fulfillment)}</p>
                    {/if}
                </div>
            </li>
        {/each}
    </ol>
{/if}
