<script>
    /** Dialog sederhana. Tutup dengan tombol ×, klik latar, atau Escape. */
    let { open = $bindable(false), title = '', size = 'md', onclose, children, footer } = $props();

    const widths = { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' };

    function close() {
        open = false;
        onclose?.();
    }

    function onKeydown(event) {
        if (open && event.key === 'Escape') close();
    }
</script>

<svelte:window onkeydown={onKeydown} />

{#if open}
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" role="presentation" onclick={(e) => e.target === e.currentTarget && close()}>
        <div class="flex max-h-[92vh] w-full {widths[size]} flex-col rounded-t-2xl bg-white shadow-xl sm:rounded-2xl" role="dialog" aria-modal="true" aria-label={title}>
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-3">
                <h2 class="text-base font-semibold">{title}</h2>
                <button type="button" class="btn-ghost btn-sm text-lg" onclick={close} aria-label="Tutup">×</button>
            </div>
            <div class="overflow-y-auto px-5 py-4">
                {@render children?.()}
            </div>
            {#if footer}
                <div class="flex flex-wrap justify-end gap-2 border-t border-stone-100 px-5 py-3">
                    {@render footer()}
                </div>
            {/if}
        </div>
    </div>
{/if}
