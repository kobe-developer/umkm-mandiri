<script>
    /** Ringkasan semua error validasi / aturan bisnis dari server. */
    let { errors = {} } = $props();

    const messages = $derived([...new Set(Object.values(errors ?? {}).flat().filter(Boolean))]);
</script>

{#if messages.length > 0}
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        {#if messages.length === 1}
            {messages[0]}
        {:else}
            <ul class="list-inside list-disc space-y-0.5">
                {#each messages as message (message)}<li>{message}</li>{/each}
            </ul>
        {/if}
    </div>
{/if}
