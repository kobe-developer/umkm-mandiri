<script>
    /** Stepper "− qty +" */
    let { value, min = 0, max = 50, onchange, size = 'md' } = $props();

    const btn = $derived(size === 'sm' ? 'size-7 text-base' : 'size-9 text-lg');
</script>

<div class="inline-flex items-center gap-1 rounded-full bg-stone-100 p-0.5">
    <button type="button" class="{btn} rounded-full bg-white font-bold text-stone-700 shadow-sm disabled:opacity-40" aria-label="Kurangi" disabled={value <= min} onclick={() => onchange(value - 1)}>−</button>
    <span class="min-w-6 text-center text-sm font-semibold tabular-nums">{value}</span>
    <button type="button" class="{btn} rounded-full bg-brand-600 font-bold text-white shadow-sm disabled:opacity-40" aria-label="Tambah" disabled={value >= max} onclick={() => onchange(value + 1)}>+</button>
</div>
