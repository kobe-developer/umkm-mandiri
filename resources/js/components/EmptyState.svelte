<script>
    import Soup from '@lucide/svelte/icons/soup';

    /** Keadaan kosong. `icon` = komponen ikon Lucide (opsional). */
    let { title = 'Belum ada data', description = '', icon: Icon = Soup, children } = $props();
</script>

<div class="flex flex-col items-center justify-center rounded-card border border-dashed border-line-strong px-6 py-12 text-center">
    <div class="mb-4 flex size-14 items-center justify-center rounded-full bg-paper-deep text-muted" aria-hidden="true">
        <Icon size={24} strokeWidth={1.5} />
    </div>
    <p class="display text-lg text-ink">{title}</p>
    {#if description}<p class="mt-1 max-w-xs text-sm text-muted">{description}</p>{/if}
    {#if children}<div class="mt-5">{@render children()}</div>{/if}
</div>
