<script>
    import { page, useForm } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import Modal from '../../components/Modal.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import PaymentMethodForm from '../../components/admin/PaymentMethodForm.svelte';

    let { methods = [], types = [], channels = [] } = $props();

    let showForm = $state(false);
    let editing = $state(null);
    let formKey = $state(0);
    let showDelete = $state(false);
    let deleting = $state(null);
    const deleteForm = useForm({});

    const deleteError = $derived(page.props.errors?.method);
    const typeLabel = $derived(Object.fromEntries(types.map((t) => [t.value, t.label])));
    const channelLabel = $derived(Object.fromEntries(channels.map((c) => [c.value, c.label])));

    function open(method = null) {
        editing = method;
        formKey += 1;
        showForm = true;
    }

    function askDelete(method) {
        deleting = method;
        showDelete = true;
    }

    function destroy() {
        deleteForm.delete(`/admin/pembayaran/${deleting.id}`, { preserveScroll: true, onFinish: () => (showDelete = false) });
    }
</script>

<svelte:head><title>Metode Pembayaran</title></svelte:head>

<PageHeader title="Metode Pembayaran" subtitle="Atur metode aktif per kanal (dine in, take away, online)">
    {#snippet actions()}
        <button type="button" class="btn-primary" onclick={() => open()}>+ Tambah metode</button>
    {/snippet}
</PageHeader>

{#if deleteError}
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{deleteError}</div>
{/if}

{#if methods.length === 0}
    <EmptyState title="Belum ada metode pembayaran" />
{:else}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        {#each methods as method (method.id)}
            <div class="card p-4 {method.is_active ? '' : 'opacity-60'}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold">{method.name}</p>
                        <p class="text-xs text-stone-500">{typeLabel[method.type] ?? method.type}</p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {method.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-200 text-stone-600'}">
                        {method.is_active ? 'Aktif' : 'Nonaktif'}
                    </span>
                </div>
                {#if method.bank_name || method.account_number}
                    <p class="mt-2 text-sm text-stone-700">{method.bank_name ?? ''} {method.account_number ?? ''}{#if method.account_holder}<span class="text-stone-500"> a.n. {method.account_holder}</span>{/if}</p>
                {/if}
                {#if method.qris_image_url}
                    <img src={method.qris_image_url} alt="QRIS {method.name}" class="mt-2 size-24 rounded-lg border border-stone-200 object-contain" loading="lazy" />
                {/if}
                <div class="mt-2 flex flex-wrap gap-1">
                    {#each method.channels ?? [] as channel (channel)}
                        <span class="rounded-full bg-stone-100 px-2 py-0.5 text-[11px] font-medium text-stone-700">{channelLabel[channel] ?? channel}</span>
                    {/each}
                </div>
                <div class="mt-3 flex gap-1">
                    <button type="button" class="btn-secondary btn-sm" onclick={() => open(method)}>Ubah</button>
                    <button type="button" class="btn-danger btn-sm" onclick={() => askDelete(method)}>Hapus</button>
                </div>
            </div>
        {/each}
    </div>
{/if}

<Modal bind:open={showForm} title={editing ? `Ubah ${editing.name}` : 'Tambah metode pembayaran'}>
    {#key formKey}
        <PaymentMethodForm method={editing} {types} {channels} formId="payment-method-form" onsaved={() => (showForm = false)} />
    {/key}
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showForm = false)}>Batal</button>
        <button type="submit" form="payment-method-form" class="btn-primary">Simpan</button>
    {/snippet}
</Modal>

<Modal bind:open={showDelete} title="Hapus metode?" size="sm">
    <p class="text-sm text-stone-600">Metode <strong>{deleting?.name}</strong> akan dihapus. Metode yang sudah dipakai transaksi hanya bisa dinonaktifkan.</p>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showDelete = false)}>Batal</button>
        <button type="button" class="btn-primary" disabled={deleteForm.processing} onclick={destroy}>Hapus</button>
    {/snippet}
</Modal>
