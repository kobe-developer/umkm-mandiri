<script>
    let { title, subtitle = '', actions } = $props();
</script>

<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-stone-900 sm:text-2xl">{title}</h1>
        {#if subtitle}<p class="mt-0.5 text-sm text-stone-500">{subtitle}</p>{/if}
    </div>
    {#if actions}<div class="flex flex-wrap gap-2">{@render actions()}</div>{/if}
</div>
