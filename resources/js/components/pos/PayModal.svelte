<script>
    import { useForm } from '@inertiajs/svelte';
    import Modal from '../Modal.svelte';
    import CashInput from './CashInput.svelte';
    import ErrorSummary from './ErrorSummary.svelte';
    import { rupiah } from '../../lib/format.js';

    /** Tandai pembayaran lunas: pilih metode, isi uang diterima untuk tunai. */
    let { open = $bindable(false), order, methods } = $props();

    const form = useForm({ payment_method_id: null, amount_received: null });

    const selected = $derived(methods.find((m) => m.id === form.payment_method_id));
    const isCash = $derived(selected?.type === 'tunai');
    const canSubmit = $derived(selected && (!isCash || (form.amount_received ?? 0) >= order.total) && !form.processing);

    $effect(() => {
        if (open && form.payment_method_id === null) {
            const planned = methods.find((m) => m.id === order.planned_payment_method?.id);
            form.payment_method_id = (planned ?? methods[0])?.id ?? null;
        }
    });

    function submit() {
        form
            .transform((data) => ({ ...data, amount_received: isCash ? data.amount_received : null }))
            .post(`/pos/orders/${order.order_number}/pay`, { preserveScroll: true, onSuccess: () => { open = false; form.reset(); } });
    }
</script>

<Modal bind:open title="Tandai pembayaran · {rupiah(order.total)}">
    <div class="space-y-4">
        <ErrorSummary errors={form.errors} />
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            {#each methods as method (method.id)}
                <button
                    type="button"
                    class="rounded-xl border px-2 py-2.5 text-sm font-semibold {form.payment_method_id === method.id ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-stone-200'}"
                    onclick={() => (form.payment_method_id = method.id)}
                >{method.name}</button>
            {/each}
        </div>
        {#if isCash}
            <CashInput bind:value={form.amount_received} total={order.total} />
        {/if}
    </div>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (open = false)}>Batal</button>
        <button type="button" class="btn-success" disabled={!canSubmit} onclick={submit}>Tandai lunas</button>
    {/snippet}
</Modal>
