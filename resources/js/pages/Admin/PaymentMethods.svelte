<script>
    import { page, useForm } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash from '@lucide/svelte/icons/trash';
    import Wallet from '@lucide/svelte/icons/wallet';
    import Banknote from '@lucide/svelte/icons/banknote';
    import Landmark from '@lucide/svelte/icons/landmark';
    import Smartphone from '@lucide/svelte/icons/smartphone';
    import QrCode from '@lucide/svelte/icons/qr-code';
    import CircleAlert from '@lucide/svelte/icons/circle-exclamation-point';
    import PageHeader from '../../components/PageHeader.svelte';
    import Modal from '../../components/Modal.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import PaymentMethodForm from '../../components/admin/PaymentMethodForm.svelte';

    let { methods = [], types = [], channels = [] } = $props();

    const TYPE_ICONS = { tunai: Banknote, transfer: Landmark, ewallet: Smartphone, qris: QrCode };

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
        <button type="button" class="btn-primary" onclick={() => open()}><Plus size={18} />Tambah metode</button>
    {/snippet}
</PageHeader>

{#if deleteError}
    <div class="mb-4 flex items-start gap-2.5 rounded-card bg-brand-50 px-4 py-3 text-sm text-brand-700" role="alert">
        <CircleAlert size={18} class="mt-px shrink-0" aria-hidden="true" />{deleteError}
    </div>
{/if}

{#if methods.length === 0}
    <EmptyState icon={Wallet} title="Belum ada metode pembayaran" />
{:else}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        {#each methods as method (method.id)}
            {@const Icon = TYPE_ICONS[method.type] ?? Wallet}
            <article class="card flex flex-col p-4 sm:p-5">
                <div class="flex items-start gap-3 {method.is_active ? '' : 'opacity-55'}">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-paper-deep text-ink-soft" aria-hidden="true">
                        <Icon size={18} />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-ink">{method.name}</p>
                        <p class="text-xs text-muted">{typeLabel[method.type] ?? method.type}</p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {method.is_active ? 'bg-leaf-50 text-leaf-700' : 'bg-paper-deep text-muted'}">
                        <span class="size-1.5 rounded-full {method.is_active ? 'bg-leaf-500' : 'bg-faint'}"></span>{method.is_active ? 'Aktif' : 'Nonaktif'}
                    </span>
                </div>

                <div class="flex-1 {method.is_active ? '' : 'opacity-55'}">
                    {#if method.bank_name || method.account_number}
                        <p class="mt-3 text-sm text-ink-soft">
                            {method.bank_name ?? ''} <span class="num">{method.account_number ?? ''}</span>{#if method.account_holder}<span class="text-muted"> a.n. {method.account_holder}</span>{/if}
                        </p>
                    {/if}
                    {#if method.qris_image_url}
                        <img src={method.qris_image_url} alt="QRIS {method.name}" class="mt-3 size-24 rounded-xl border border-line object-contain" loading="lazy" />
                    {/if}
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        {#each method.channels ?? [] as channel (channel)}
                            <span class="rounded-full border border-line px-2.5 py-0.5 text-xs font-medium text-ink-soft">{channelLabel[channel] ?? channel}</span>
                        {/each}
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2 border-t border-line pt-3">
                    <button type="button" class="btn-secondary btn-sm" onclick={() => open(method)}><Pencil size={14} />Ubah</button>
                    <button type="button" class="btn-icon -my-1 -mr-2 ml-auto hover:bg-brand-50 hover:text-brand-700" onclick={() => askDelete(method)} aria-label="Hapus {method.name}" title="Hapus">
                        <Trash size={16} />
                    </button>
                </div>
            </article>
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
    <p class="text-sm text-ink-soft">Metode <strong>{deleting?.name}</strong> akan dihapus. Metode yang sudah dipakai transaksi hanya bisa dinonaktifkan.</p>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showDelete = false)}>Batal</button>
        <button type="button" class="btn-accent" disabled={deleteForm.processing} onclick={destroy}>Hapus</button>
    {/snippet}
</Modal>
