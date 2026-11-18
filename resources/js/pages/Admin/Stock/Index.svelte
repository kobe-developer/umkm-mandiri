<script>
    import PageTitle from '../../../components/PageTitle.svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import SlidersHorizontal from '@lucide/svelte/icons/sliders-horizontal';
    import Search from '@lucide/svelte/icons/search';
    import History from '@lucide/svelte/icons/rotate-ccw-clock';
    import TriangleAlert from '@lucide/svelte/icons/triangle-exclamation-point';
    import Boxes from '@lucide/svelte/icons/boxes';
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

<PageTitle title="Stok" />

<PageHeader title="Stok Produk" subtitle="Stok per produk (porsi/pcs). Berkurang otomatis saat order lunas.">
    {#snippet actions()}
        <Link href="/admin/stok/riwayat" class="btn-secondary"><History size={17} />Riwayat stok</Link>
    {/snippet}
</PageHeader>

<div class="mb-4 flex flex-wrap items-center gap-3">
    <div class="relative w-full sm:max-w-xs">
        <Search size={17} class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-faint" aria-hidden="true" />
        <input class="input pl-10" type="search" placeholder="Cari produk" bind:value={search} aria-label="Cari produk" />
    </div>
    {#if lowCount > 0}
        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-soft px-3 py-1.5 text-sm font-semibold text-amber-ink">
            <TriangleAlert size={15} aria-hidden="true" /><span class="num">{lowCount}</span> stok menipis
        </span>
    {/if}
</div>

{#if untrackedCount > 0}
    <p class="mb-5 text-sm text-muted">
        {untrackedCount} produk tidak dilacak stoknya. Aktifkan "Lacak stok" di <Link href="/admin/produk" class="font-medium text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">halaman produk</Link> bila perlu.
    </p>
{/if}

{#snippet stockBadge(product)}
    {#if product.stock <= 0}<span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700">Habis</span>
    {:else if product.is_low_stock}<span class="rounded-full bg-amber-soft px-2 py-0.5 text-xs font-semibold text-amber-ink">Menipis</span>{/if}
{/snippet}

{#if products.length === 0}
    <EmptyState icon={Boxes} title="Belum ada produk yang dilacak stoknya" description="Aktifkan opsi Lacak stok pada produk untuk mulai mencatat stok." />
{:else}
    <!-- Mobile: daftar -->
    <ul class="card divide-y divide-line md:hidden">
        {#each filtered as product (product.id)}
            <li class="p-4">
                <div class="flex items-start justify-between gap-3 {product.is_active ? '' : 'opacity-55'}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <p class="font-medium text-ink">{product.name}</p>
                            {@render stockBadge(product)}
                        </div>
                        <p class="mt-0.5 text-xs text-muted">{product.category ?? '-'} · min. <span class="num">{product.stock_minimum}</span></p>
                    </div>
                    <p class="num shrink-0 text-2xl leading-none font-semibold {product.stock <= 0 ? 'text-brand-600' : 'text-ink'}">{product.stock}</p>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button type="button" class="btn-success px-3" onclick={() => open(product, 'masuk')}><Plus size={16} />Stok masuk</button>
                    <button type="button" class="btn-secondary px-3" onclick={() => open(product, 'penyesuaian')}><SlidersHorizontal size={15} />Penyesuaian</button>
                </div>
            </li>
        {/each}
    </ul>

    <!-- md+: tabel -->
    <div class="card hidden overflow-x-auto md:block">
        <table class="table">
            <thead><tr><th>Produk</th><th>Kategori</th><th class="text-right">Stok</th><th class="text-right">Minimum</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                {#each filtered as product (product.id)}
                    <tr class={product.is_active ? '' : 'opacity-55'}>
                        <td class="align-middle font-medium text-ink">
                            <span class="mr-1">{product.name}</span>
                            {@render stockBadge(product)}
                        </td>
                        <td class="align-middle text-ink-soft">{product.category ?? '-'}</td>
                        <td class="num text-right align-middle text-base font-semibold {product.stock <= 0 ? 'text-brand-600' : 'text-ink'}">{product.stock}</td>
                        <td class="num text-right align-middle text-muted">{product.stock_minimum}</td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <button type="button" class="btn-success btn-sm" onclick={() => open(product, 'masuk')}><Plus size={15} />Stok masuk</button>
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
        <p class="flex items-baseline justify-between rounded-xl bg-paper px-4 py-3 text-sm text-ink-soft">Stok saat ini <strong class="num text-lg text-ink">{selected?.stock}</strong></p>
        <div>
            <label class="label" for="qty">{form.type === 'masuk' ? 'Jumlah masuk' : 'Jumlah (+ tambah / − kurangi)'}</label>
            <input id="qty" type="number" class="input" bind:value={form.quantity} min={form.type === 'masuk' ? 1 : undefined} required />
            {#if form.errors.quantity}<p class="error">{form.errors.quantity}</p>{/if}
            {#if stockAfter !== null}<p class="hint {stockAfter < 0 ? 'font-medium text-brand-600' : ''}">Stok setelahnya: <span class="num">{stockAfter}</span></p>{/if}
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
