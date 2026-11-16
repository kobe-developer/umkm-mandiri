<script>
    import { useForm } from '@inertiajs/svelte';
    import Modal from '../Modal.svelte';
    import ErrorSummary from './ErrorSummary.svelte';

    /** Modal alasan untuk batal / void. requirePin = minta PIN owner (kasir yang void). */
    let { open = $bindable(false), title, url, confirmLabel, requirePin = false, presets = [] } = $props();

    const form = useForm({ reason: '', pin: '' });

    function submit() {
        form
            .transform((data) => (requirePin ? data : { reason: data.reason }))
            .post(url, { preserveScroll: true, onSuccess: () => { open = false; form.reset(); } });
    }
</script>

<Modal bind:open {title} size="sm">
    <div class="space-y-3">
        <ErrorSummary errors={form.errors} />
        {#if presets.length > 0}
            <div class="flex flex-wrap gap-1.5">
                {#each presets as preset (preset)}
                    <button type="button" class="chip {form.reason === preset ? 'chip-active' : ''}" onclick={() => (form.reason = preset)}>{preset}</button>
                {/each}
            </div>
        {/if}
        <div>
            <label class="label" for="reason">Alasan</label>
            <textarea id="reason" class="input" rows="2" maxlength="255" bind:value={form.reason}></textarea>
        </div>
        {#if requirePin}
            <div>
                <label class="label" for="pin">PIN owner</label>
                <input id="pin" type="password" inputmode="numeric" autocomplete="off" maxlength="6" class="input num text-center text-xl tracking-[0.5em]" bind:value={form.pin} />
                <p class="hint">Minta owner memasukkan PIN untuk menyetujui.</p>
            </div>
        {/if}
    </div>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (open = false)}>Kembali</button>
        <button type="button" class="btn-danger" disabled={form.processing || form.reason.trim().length < 3 || (requirePin && !form.pin)} onclick={submit}>{confirmLabel}</button>
    {/snippet}
</Modal>
