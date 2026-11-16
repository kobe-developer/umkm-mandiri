<script>
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';

    /** Header halaman alur menu: tombol kembali, judul serif, indikator langkah (opsional, untuk keranjang/checkout). */
    let { title, step = null, total = 2, backLabel, onback } = $props();
</script>

<header class="sticky top-0 z-20 border-b border-line/70 bg-paper/90 pt-[env(safe-area-inset-top)] backdrop-blur-xl">
    <div class="mx-auto flex min-h-16 max-w-2xl items-center gap-2 px-3">
        <button type="button" class="btn-icon" aria-label={backLabel} onclick={onback}><ChevronLeft size={22} /></button>
        <h1 class="display flex-1 text-2xl text-ink">{title}</h1>
        {#if step}
            <div class="flex items-center gap-1.5 pr-2" aria-label="Langkah {step} dari {total}">
                {#each Array.from({ length: total }, (_, i) => i + 1) as n (n)}
                    <span class="h-1.5 rounded-full transition-all {n === step ? 'w-6 bg-ink' : n < step ? 'w-1.5 bg-ink' : 'w-1.5 bg-line-strong'}"></span>
                {/each}
            </div>
        {/if}
    </div>
</header>
