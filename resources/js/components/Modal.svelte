<script>
    import X from '@lucide/svelte/icons/x';

    /** Dialog: bottom sheet di mobile, modal di tengah pada layar besar. Tutup via ×, latar, atau Escape. */
    let { open = $bindable(false), title = '', size = 'md', onclose, children, footer } = $props();

    const widths = { sm: 'sm:max-w-sm', md: 'sm:max-w-lg', lg: 'sm:max-w-2xl', xl: 'sm:max-w-4xl' };

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
    <div
        class="fixed inset-0 z-50 flex animate-fade-in items-end justify-center bg-ink/40 backdrop-blur-[2px] sm:items-center sm:p-6"
        role="presentation"
        onclick={(e) => e.target === e.currentTarget && close()}
    >
        <div
            class="flex max-h-[92dvh] w-full {widths[size]} animate-sheet-up flex-col rounded-t-[1.75rem] bg-surface shadow-float sm:rounded-card"
            role="dialog"
            aria-modal="true"
            aria-label={title}
        >
            <div class="flex justify-center pt-2.5 sm:hidden" aria-hidden="true"><span class="h-1 w-10 rounded-full bg-line-strong"></span></div>
            <div class="flex items-center justify-between gap-3 px-5 pt-3 pb-2 sm:pt-5">
                <h2 class="display text-xl text-ink">{title}</h2>
                <button type="button" class="btn-icon -mr-2 size-10" onclick={close} aria-label="Tutup"><X size={20} /></button>
            </div>
            <div class="overflow-y-auto px-5 pt-2 pb-5">
                {@render children?.()}
            </div>
            {#if footer}
                <div class="safe-bottom flex flex-wrap justify-end gap-2 border-t border-line px-5 pt-3 sm:pb-4 [&>*]:flex-1 sm:[&>*]:flex-none">
                    {@render footer()}
                </div>
            {/if}
        </div>
    </div>
{/if}
