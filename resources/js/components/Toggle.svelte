<script>
    /** Switch on/off yang aksesibel. */
    let { checked = $bindable(false), label = '', description = '', disabled = false, onchange } = $props();

    function toggle() {
        if (disabled) return;
        checked = !checked;
        onchange?.(checked);
    }
</script>

<div class="flex items-start justify-between gap-4">
    {#if label}
        <div>
            <p class="text-sm font-medium text-stone-800">{label}</p>
            {#if description}<p class="text-xs text-stone-500">{description}</p>{/if}
        </div>
    {/if}
    <button
        type="button"
        role="switch"
        aria-checked={checked}
        aria-label={label}
        {disabled}
        onclick={toggle}
        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition disabled:opacity-50 {checked ? 'bg-emerald-500' : 'bg-stone-300'}"
    >
        <span class="inline-block size-5 transform rounded-full bg-white shadow transition {checked ? 'translate-x-5' : 'translate-x-0.5'}"></span>
    </button>
</div>
