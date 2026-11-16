<script>
    import { page, useForm } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash from '@lucide/svelte/icons/trash';
    import Tags from '@lucide/svelte/icons/tags';
    import CircleAlert from '@lucide/svelte/icons/circle-exclamation-point';
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
        <button type="button" class="btn-primary" onclick={openCreate}><Plus size={18} />Tambah kategori</button>
    {/snippet}
</PageHeader>

{#if deleteError}
    <div class="mb-4 flex items-start gap-2.5 rounded-card bg-brand-50 px-4 py-3 text-sm text-brand-700" role="alert">
        <CircleAlert size={18} class="mt-px shrink-0" aria-hidden="true" />{deleteError}
    </div>
{/if}

{#snippet activeBadge(isActive)}
    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {isActive ? 'bg-leaf-50 text-leaf-700' : 'bg-paper-deep text-muted'}">
        <span class="size-1.5 rounded-full {isActive ? 'bg-leaf-500' : 'bg-faint'}"></span>{isActive ? 'Aktif' : 'Nonaktif'}
    </span>
{/snippet}

{#snippet rowActions(category)}
    <button type="button" class="btn-icon" onclick={() => openEdit(category)} aria-label="Ubah {category.name}" title="Ubah"><Pencil size={17} /></button>
    <button
        type="button"
        class="btn-icon enabled:hover:bg-brand-50 enabled:hover:text-brand-700 disabled:cursor-not-allowed disabled:opacity-30"
        onclick={() => askDelete(category)}
        disabled={category.products_count > 0}
        aria-label="Hapus {category.name}"
        title={category.products_count > 0 ? 'Masih punya produk — nonaktifkan saja' : 'Hapus'}
    ><Trash size={17} /></button>
{/snippet}

{#if categories.length === 0}
    <EmptyState icon={Tags} title="Belum ada kategori" />
{:else}
    <!-- Mobile: daftar -->
    <ul class="card divide-y divide-line md:hidden">
        {#each categories as category (category.id)}
            <li class="flex items-center gap-3 py-2 pr-2 pl-4">
                <span class="num w-6 shrink-0 text-sm text-muted">{category.sort_order}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-ink">{category.name}</p>
                    <div class="mt-1 flex items-center gap-2">
                        {@render activeBadge(category.is_active)}
                        <span class="num text-xs text-muted">{category.products_count} produk</span>
                    </div>
                </div>
                <div class="flex shrink-0">{@render rowActions(category)}</div>
            </li>
        {/each}
    </ul>

    <!-- md+: tabel -->
    <div class="card hidden md:block">
        <table class="table">
            <thead><tr><th class="w-20">Urutan</th><th>Nama</th><th>Produk</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                {#each categories as category (category.id)}
                    <tr>
                        <td class="num align-middle text-muted">{category.sort_order}</td>
                        <td class="align-middle font-medium text-ink">{category.name}</td>
                        <td class="num align-middle">{category.products_count}</td>
                        <td class="align-middle">{@render activeBadge(category.is_active)}</td>
                        <td class="py-1.5 text-right whitespace-nowrap">{@render rowActions(category)}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
    <p class="hint mt-3">Kategori yang masih punya produk tidak bisa dihapus, hanya dinonaktifkan.</p>
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
    <p class="text-sm text-ink-soft">Kategori <strong>{deleting?.name}</strong> akan dihapus permanen.</p>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showDelete = false)}>Batal</button>
        <button type="button" class="btn-accent" disabled={deleteForm.processing} onclick={destroy}>Hapus</button>
    {/snippet}
</Modal>
