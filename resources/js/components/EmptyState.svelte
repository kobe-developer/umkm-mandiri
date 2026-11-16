<script>
    let { title = 'Belum ada data', description = '', children } = $props();
</script>

<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-10 text-center">
    <div class="mb-2 text-3xl" aria-hidden="true">🍗</div>
    <p class="font-semibold text-stone-700">{title}</p>
    {#if description}<p class="mt-1 max-w-sm text-sm text-stone-500">{description}</p>{/if}
    {#if children}<div class="mt-4">{@render children()}</div>{/if}
</div>
