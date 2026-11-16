<script>
    import Minus from '@lucide/svelte/icons/minus';
    import Plus from '@lucide/svelte/icons/plus';

    /** Stepper "− qty +" */
    let { value, min = 0, max = 50, onchange, size = 'md' } = $props();

    const btn = $derived(size === 'sm' ? 'size-8' : 'size-10');
    const icon = $derived(size === 'sm' ? 15 : 18);
</script>

<div class="inline-flex items-center rounded-full border border-line bg-surface p-0.5">
    <button type="button" class="{btn} flex items-center justify-center rounded-full text-ink-soft transition hover:bg-paper active:scale-90 disabled:opacity-30" aria-label="Kurangi" disabled={value <= min} onclick={() => onchange(value - 1)}>
        <Minus size={icon} strokeWidth={2.25} />
    </button>
    <span class="num min-w-7 text-center text-sm font-semibold">{value}</span>
    <button type="button" class="{btn} flex items-center justify-center rounded-full bg-ink text-paper transition hover:bg-ink-soft active:scale-90 disabled:opacity-30" aria-label="Tambah" disabled={value >= max} onclick={() => onchange(value + 1)}>
        <Plus size={icon} strokeWidth={2.25} />
    </button>
</div>
