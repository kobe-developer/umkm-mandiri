<script>
    /** Switch on/off yang aksesibel. */
    let { checked = $bindable(false), label = '', description = '', disabled = false, onchange } = $props();

    function toggle() {
        if (disabled) return;
        checked = !checked;
        onchange?.(checked);
    }
</script>

<div class="flex items-center justify-between gap-4">
    {#if label}
        <div>
            <p class="text-sm font-medium text-ink">{label}</p>
            {#if description}<p class="mt-0.5 text-xs text-muted">{description}</p>{/if}
        </div>
    {/if}
    <button
        type="button"
        role="switch"
        aria-checked={checked}
        aria-label={label}
        {disabled}
        onclick={toggle}
        class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors duration-200 disabled:opacity-50 {checked ? 'bg-leaf-600' : 'bg-line-strong'}"
    >
        <span class="inline-block size-6 rounded-full bg-white shadow-soft transition-transform duration-200 {checked ? 'translate-x-[1.375rem]' : 'translate-x-0.5'}"></span>
    </button>
</div>
