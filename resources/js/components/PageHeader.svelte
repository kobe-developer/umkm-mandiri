<script>
    /** Judul halaman back-office: serif besar + keterangan, aksi di kanan. */
    let { title, subtitle = '', eyebrow = '', actions } = $props();
</script>

<header class="mb-6 flex flex-wrap items-end justify-between gap-x-4 gap-y-3">
    <div class="min-w-0">
        {#if eyebrow}<p class="eyebrow mb-1">{eyebrow}</p>{/if}
        <h1 class="display text-[1.75rem] leading-tight text-ink sm:text-[2rem]">{title}</h1>
        {#if subtitle}<p class="mt-1 max-w-xl text-sm text-muted">{subtitle}</p>{/if}
    </div>
    {#if actions}<div class="flex flex-wrap items-center gap-2">{@render actions()}</div>{/if}
</header>
