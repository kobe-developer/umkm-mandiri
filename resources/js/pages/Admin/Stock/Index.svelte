<script>
    import { Link, useForm } from '@inertiajs/svelte';
    import PageHeader from '../../../components/PageHeader.svelte';
    import Modal from '../../../components/Modal.svelte';
    import EmptyState from '../../../components/EmptyState.svelte';

    let { products = [], untrackedCount = 0 } = $props();

    const REASONS = ['Koreksi stok opname', 'Rusak', 'Basi'];

    let showForm = $state(false);
    let selected = $state(null);
    let search = $state('');

    const form = useForm({ type: 'masuk', quantity: null, reason: '' });

    const filtered = $derived(products.filter((p) => p.name.toLowerCase().includes(search.trim().toLowerCase())));
    const lowCount = $derived(products.filter((p) => p.is_low_stock).length);
    const stockAfter = $derived(
        selected && form.quantity ? selected.stock + (form.type === 'masuk' ? Math.abs(form.quantity) : Number(form.quantity)) : null,
    );

    function open(product, type) {
        selected = product;
        form.clearErrors();
        form.type = type;
        form.quantity = null;
        form.reason = '';
        showForm = true;
    }

    function submit(event) {
        event?.preventDefault();
        form.post(`/admin/stok/${selected.id}`, { preserveScroll: true, onSuccess: () => (showForm = false) });
    }
</script>

<svelte:head><title>Stok</title></svelte:head>

<PageHeader title="Stok Produk" subtitle="Stok per produk (porsi/pcs). Berkurang otomatis saat order lunas.">
    {#snippet actions()}
        <Link href="/admin/stok/riwayat" class="btn-secondary">Riwayat stok</Link>
    {/snippet}
</PageHeader>

<div class="mb-4 flex flex-wrap items-center gap-3">
    <input class="input max-w-xs" placeholder="Cari produk" bind:value={search} aria-label="Cari produk" />
    {#if lowCount > 0}<span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">⚠️ {lowCount} stok menipis</span>{/if}
</div>

{#if untrackedCount > 0}
    <p class="mb-4 text-sm text-stone-500">
        {untrackedCount} produk tidak dilacak stoknya. Aktifkan "Lacak stok" di <Link href="/admin/produk" class="font-semibold text-brand-700 underline">halaman produk</Link> bila perlu.
    </p>
{/if}

{#if products.length === 0}
    <EmptyState title="Belum ada produk yang dilacak stoknya" description="Aktifkan opsi Lacak stok pada produk untuk mulai mencatat stok." />
{:else}
    <div class="card overflow-x-auto">
        <table class="table min-w-[640px]">
            <thead><tr><th>Produk</th><th>Kategori</th><th class="text-right">Stok</th><th class="text-right">Minimum</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                {#each filtered as product (product.id)}
                    <tr class={product.is_active ? '' : 'opacity-60'}>
                        <td class="font-medium">
                            {product.name}
                            {#if product.stock <= 0}<span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Habis</span>
                            {:else if product.is_low_stock}<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Menipis</span>{/if}
                        </td>
                        <td>{product.category ?? '-'}</td>
                        <td class="text-right text-base font-bold tabular-nums">{product.stock}</td>
                        <td class="text-right tabular-nums text-stone-500">{product.stock_minimum}</td>
                        <td class="text-right whitespace-nowrap">
                            <button type="button" class="btn-success btn-sm" onclick={() => open(product, 'masuk')}>+ Stok masuk</button>
                            <button type="button" class="btn-secondary btn-sm" onclick={() => open(product, 'penyesuaian')}>Penyesuaian</button>
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/if}

<Modal bind:open={showForm} title={form.type === 'masuk' ? `Stok masuk · ${selected?.name}` : `Penyesuaian · ${selected?.name}`} size="sm">
    <form id="stock-form" class="space-y-4" onsubmit={submit}>
        <p class="text-sm text-stone-600">Stok saat ini: <strong class="tabular-nums">{selected?.stock}</strong></p>
        <div>
            <label class="label" for="qty">{form.type === 'masuk' ? 'Jumlah masuk' : 'Jumlah (+ tambah / − kurangi)'}</label>
            <input id="qty" type="number" class="input" bind:value={form.quantity} min={form.type === 'masuk' ? 1 : undefined} required />
            {#if form.errors.quantity}<p class="error">{form.errors.quantity}</p>{/if}
            {#if stockAfter !== null}<p class="mt-1 text-xs {stockAfter < 0 ? 'text-red-600' : 'text-stone-500'}">Stok setelahnya: {stockAfter}</p>{/if}
        </div>
        <div>
            <label class="label" for="reason">Alasan {form.type === 'penyesuaian' ? '(wajib)' : '(opsional)'}</label>
            <input id="reason" class="input" bind:value={form.reason} maxlength="255" list="stock-reasons" required={form.type === 'penyesuaian'} />
            <datalist id="stock-reasons">{#each REASONS as reason (reason)}<option value={reason}></option>{/each}</datalist>
            {#if form.errors.reason}<p class="error">{form.errors.reason}</p>{/if}
        </div>
    </form>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showForm = false)}>Batal</button>
        <button type="submit" form="stock-form" class="btn-primary" disabled={form.processing}>Simpan</button>
    {/snippet}
</Modal>
