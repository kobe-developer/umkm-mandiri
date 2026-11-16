<script>
    import Check from '@lucide/svelte/icons/check';
    import Ban from '@lucide/svelte/icons/ban';

    /** Timeline status order: Baru → Dikonfirmasi → Diproses → Siap/Diantar → Selesai */
    let { order } = $props();

    const STEPS = [
        { value: 'baru', label: 'Pesanan diterima', hint: 'Menunggu konfirmasi kasir' },
        { value: 'dikonfirmasi', label: 'Dikonfirmasi', hint: 'Pesanan sudah dicek kasir' },
        { value: 'diproses', label: 'Diproses', hint: 'Sedang disiapkan di dapur' },
        { value: 'siap', label: 'Siap / Diantar', hint: 'Siap diambil atau sedang diantar' },
        { value: 'selesai', label: 'Selesai', hint: 'Selamat menikmati!' },
    ];

    const currentIndex = $derived(STEPS.findIndex((s) => s.value === order.status));
    const readyHint = $derived(order.fulfillment === 'delivery' ? 'Pesanan sedang diantar' : 'Pesanan siap diambil');
</script>

{#if order.status === 'dibatalkan'}
    <div class="flex gap-3 rounded-2xl bg-brand-50 p-4 text-sm text-brand-700">
        <Ban size={18} class="mt-0.5 shrink-0" />
        <div>
            <p class="font-semibold">Pesanan dibatalkan</p>
            {#if order.cancel_reason}<p class="mt-0.5">Alasan: {order.cancel_reason}</p>{/if}
        </div>
    </div>
{:else}
    <ol>
        {#each STEPS as step, index (step.value)}
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
                    {#if index < STEPS.length - 1}
                        <span class="my-1 w-px flex-1 {done ? 'bg-ink' : 'bg-line-strong'} min-h-5"></span>
                    {/if}
                </div>
                <div class="pb-5">
                    <p class="text-sm leading-7 {current ? 'font-semibold text-ink' : done ? 'font-medium text-ink-soft' : 'text-faint'}">
                        {step.label}{#if current}<span class="sr-only"> (status saat ini)</span>{/if}
                    </p>
                    {#if current}
                        <p class="text-[13px] text-muted">{step.value === 'siap' ? readyHint : step.hint}</p>
                    {/if}
                </div>
            </li>
        {/each}
    </ol>
{/if}
