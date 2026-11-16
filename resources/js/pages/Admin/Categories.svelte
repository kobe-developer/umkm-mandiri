<script>
    import { page, useForm } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import Modal from '../../components/Modal.svelte';
    import Toggle from '../../components/Toggle.svelte';
    import EmptyState from '../../components/EmptyState.svelte';

    let { categories = [] } = $props();

    let showForm = $state(false);
    let editing = $state(null);
    let showDelete = $state(false);
    let deleting = $state(null);

    const form = useForm({ name: '', sort_order: 0, is_active: true });
    const deleteForm = useForm({});
    const deleteError = $derived(page.props.errors?.category);

    function openCreate() {
        editing = null;
        form.clearErrors();
        form.name = '';
        form.sort_order = (categories.at(-1)?.sort_order ?? 0) + 1;
        form.is_active = true;
        showForm = true;
    }

    function openEdit(category) {
        editing = category;
        form.clearErrors();
        form.name = category.name;
        form.sort_order = category.sort_order;
        form.is_active = category.is_active;
        showForm = true;
    }

    function save(event) {
        event?.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => (showForm = false) };
        if (editing) form.put(`/admin/kategori/${editing.id}`, options);
        else form.post('/admin/kategori', options);
    }

    function askDelete(category) {
        deleting = category;
        showDelete = true;
    }

    function destroy() {
        deleteForm.delete(`/admin/kategori/${deleting.id}`, { preserveScroll: true, onFinish: () => (showDelete = false) });
    }
</script>

<svelte:head><title>Kategori</title></svelte:head>

<PageHeader title="Kategori" subtitle="Urutan tampil di kasir & menu online">
    {#snippet actions()}
        <button type="button" class="btn-primary" onclick={openCreate}>+ Tambah kategori</button>
    {/snippet}
</PageHeader>

{#if deleteError}
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{deleteError}</div>
{/if}

{#if categories.length === 0}
    <EmptyState title="Belum ada kategori" />
{:else}
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Urutan</th><th>Nama</th><th>Produk</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                {#each categories as category (category.id)}
                    <tr>
                        <td class="tabular-nums">{category.sort_order}</td>
                        <td class="font-medium">{category.name}</td>
                        <td class="tabular-nums">{category.products_count}</td>
                        <td>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {category.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-200 text-stone-600'}">
                                {category.is_active ? 'Aktif' : 'Nonaktif'}
                            </span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <button type="button" class="btn-secondary btn-sm" onclick={() => openEdit(category)}>Ubah</button>
                            <button type="button" class="btn-danger btn-sm" onclick={() => askDelete(category)} disabled={category.products_count > 0} title={category.products_count > 0 ? 'Masih punya produk — nonaktifkan saja' : 'Hapus'}>Hapus</button>
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-stone-500">Kategori yang masih punya produk tidak bisa dihapus, hanya dinonaktifkan.</p>
{/if}

<Modal bind:open={showForm} title={editing ? 'Ubah kategori' : 'Tambah kategori'} size="sm">
    <form id="category-form" class="space-y-4" onsubmit={save}>
        <div>
            <label class="label" for="cat-name">Nama</label>
            <input id="cat-name" class="input" bind:value={form.name} maxlength="50" required />
            {#if form.errors.name}<p class="error">{form.errors.name}</p>{/if}
        </div>
        <div>
            <label class="label" for="cat-sort">Urutan tampil</label>
            <input id="cat-sort" type="number" min="0" class="input" bind:value={form.sort_order} required />
            {#if form.errors.sort_order}<p class="error">{form.errors.sort_order}</p>{/if}
        </div>
        <Toggle bind:checked={form.is_active} label="Aktif" description="Kategori nonaktif disembunyikan beserta produknya dari menu online." />
    </form>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showForm = false)}>Batal</button>
        <button type="submit" form="category-form" class="btn-primary" disabled={form.processing}>Simpan</button>
    {/snippet}
</Modal>

<Modal bind:open={showDelete} title="Hapus kategori?" size="sm">
    <p class="text-sm text-stone-600">Kategori <strong>{deleting?.name}</strong> akan dihapus permanen.</p>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showDelete = false)}>Batal</button>
        <button type="button" class="btn-primary" disabled={deleteForm.processing} onclick={destroy}>Hapus</button>
    {/snippet}
</Modal>
