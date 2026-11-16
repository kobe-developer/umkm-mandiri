<script>
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
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <p class="font-semibold">Pesanan dibatalkan</p>
        {#if order.cancel_reason}<p class="mt-1">Alasan: {order.cancel_reason}</p>{/if}
    </div>
{:else}
    <ol class="relative space-y-4">
        {#each STEPS as step, index (step.value)}
            {@const done = index <= currentIndex}
            {@const current = index === currentIndex}
            <li class="flex gap-3">
                <div class="flex flex-col items-center">
                    <span
                        class="flex size-7 items-center justify-center rounded-full text-xs font-bold {done ? 'bg-emerald-500 text-white' : 'bg-stone-200 text-stone-500'} {current ? 'ring-4 ring-emerald-100' : ''}"
                        aria-hidden="true"
                    >
                        {done ? '✓' : index + 1}
                    </span>
                    {#if index < STEPS.length - 1}
                        <span class="mt-1 h-full min-h-4 w-0.5 {index < currentIndex ? 'bg-emerald-400' : 'bg-stone-200'}"></span>
                    {/if}
                </div>
                <div class="pb-1">
                    <p class="text-sm font-semibold {done ? 'text-stone-900' : 'text-stone-400'}">
                        {step.label}{#if current}<span class="sr-only"> (status saat ini)</span>{/if}
                    </p>
                    {#if current}
                        <p class="text-xs text-stone-500">{step.value === 'siap' ? readyHint : step.hint}</p>
                    {/if}
                </div>
            </li>
        {/each}
    </ol>
{/if}
