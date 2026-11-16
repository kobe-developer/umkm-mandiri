<script>
    import CircleAlert from '@lucide/svelte/icons/circle-alert';

    /** Ringkasan semua error validasi / aturan bisnis dari server. */
    let { errors = {} } = $props();

    const messages = $derived([...new Set(Object.values(errors ?? {}).flat().filter(Boolean))]);
</script>

{#if messages.length > 0}
    <div class="mb-4 flex gap-3 rounded-2xl bg-brand-50 px-4 py-3 text-sm text-brand-700" role="alert">
        <CircleAlert size={18} class="mt-px shrink-0" />
        {#if messages.length === 1}
            <p>{messages[0]}</p>
        {:else}
            <ul class="list-inside list-disc space-y-0.5">
                {#each messages as message (message)}<li>{message}</li>{/each}
            </ul>
        {/if}
    </div>
{/if}
