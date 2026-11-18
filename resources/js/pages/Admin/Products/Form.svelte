<script>
    import PageTitle from '../../../components/PageTitle.svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import ImageUp from '@lucide/svelte/icons/image-up';
    import Drumstick from '@lucide/svelte/icons/drumstick';
    import PageHeader from '../../../components/PageHeader.svelte';
    import MoneyInput from '../../../components/MoneyInput.svelte';
    import Toggle from '../../../components/Toggle.svelte';
    import SearchSelect from '../../../components/SearchSelect.svelte';

    let { product = null, categories = [] } = $props();

    const isEdit = $derived(product !== null);
    const categoryOptions = $derived(categories.map((c) => ({ value: c.id, label: c.is_active ? c.name : `${c.name} (nonaktif)` })));

    const form = useForm(() => ({
        category_id: product?.category_id ?? categories.find((c) => c.is_active)?.id ?? '',
        name: product?.name ?? '',
        price: product?.price ?? null,
        description: product?.description ?? '',
        photo: null,
        remove_photo: false,
        is_active: product?.is_active ?? true,
        show_online: product?.show_online ?? true,
        track_stock: product?.track_stock ?? false,
        stock_minimum: product?.stock_minimum ?? 0,
        initial_stock: 0,
        is_available: product?.is_available ?? true,
        sort_order: product?.sort_order ?? 0,
    }));

    let preview = $state(untrack(() => product?.photo_url ?? null));

    function onPhoto(event) {
        const file = event.currentTarget.files?.[0] ?? null;
        form.photo = file;
        form.remove_photo = false;
        if (preview?.startsWith('blob:')) URL.revokeObjectURL(preview);
        preview = file ? URL.createObjectURL(file) : (product?.photo_url ?? null);
    }

    function removePhoto() {
        form.photo = null;
        form.remove_photo = true;
        preview = null;
    }

    function submit(event) {
        event.preventDefault();
        if (isEdit) {
            form.transform((data) => ({ ...data, initial_stock: undefined, _method: 'put' }))
                .post(`/admin/produk/${product.id}`, { forceFormData: true, preserveScroll: true });
        } else {
            form.post('/admin/produk', { forceFormData: true, preserveScroll: true });
        }
    }
</script>

<PageTitle title={`${isEdit ? 'Ubah' : 'Tambah'} Produk`} />

<PageHeader title={isEdit ? `Ubah ${product.name}` : 'Tambah Produk'} subtitle={isEdit ? 'Perubahan harga tidak mengubah harga di transaksi lama.' : ''}>
    {#snippet actions()}
        <Link href="/admin/produk" class="btn-ghost -ml-2 sm:ml-0"><ArrowLeft size={17} />Kembali</Link>
    {/snippet}
</PageHeader>

<form class="grid gap-4 lg:grid-cols-3" onsubmit={submit}>
    <div class="card space-y-5 p-4 sm:p-5 lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="name">Nama produk</label>
                <input id="name" class="input" bind:value={form.name} maxlength="100" required />
                {#if form.errors.name}<p class="error">{form.errors.name}</p>{/if}
            </div>
            <div>
                <label class="label" for="category">Kategori</label>
                <SearchSelect
                    id="category"
                    options={categoryOptions}
                    bind:value={form.category_id}
                    placeholder="Pilih kategori…"
                    searchPlaceholder="Cari kategori…"
                    invalid={!!form.errors.category_id}
                />
                {#if form.errors.category_id}<p class="error">{form.errors.category_id}</p>{/if}
            </div>
            <div>
                <label class="label" for="price">Harga jual</label>
                <MoneyInput id="price" bind:value={form.price} required />
                {#if form.errors.price}<p class="error">{form.errors.price}</p>{/if}
            </div>
            <div>
                <label class="label" for="sort">Urutan tampil</label>
                <input id="sort" type="number" min="0" class="input" bind:value={form.sort_order} />
                {#if form.errors.sort_order}<p class="error">{form.errors.sort_order}</p>{/if}
            </div>
        </div>
        <div>
            <label class="label" for="description">Deskripsi singkat (opsional)</label>
            <textarea id="description" class="input" rows="2" maxlength="255" bind:value={form.description}></textarea>
            {#if form.errors.description}<p class="error">{form.errors.description}</p>{/if}
        </div>

        <div class="divide-y divide-line border-t border-line [&>*]:py-3.5 [&>*:last-child]:pb-0">
            <Toggle bind:checked={form.is_active} label="Aktif" description="Produk nonaktif tidak muncul di kasir maupun menu online." />
            <Toggle bind:checked={form.show_online} label="Tampil di menu online" description="Matikan untuk produk yang hanya dijual di tempat." />
            <Toggle bind:checked={form.is_available} label="Tersedia" description="Matikan untuk menandai produk habis (kasir juga bisa)." />
            <Toggle bind:checked={form.track_stock} label="Lacak stok" description="Jika aktif, produk otomatis tampil Habis saat stok 0." />
        </div>

        {#if form.track_stock}
            <div class="grid gap-4 rounded-xl bg-paper p-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="minimum">Batas stok minimum</label>
                    <input id="minimum" type="number" min="0" class="input" bind:value={form.stock_minimum} />
                    <p class="hint">Muncul peringatan "stok menipis" di dashboard.</p>
                    {#if form.errors.stock_minimum}<p class="error">{form.errors.stock_minimum}</p>{/if}
                </div>
                {#if isEdit}
                    <div>
                        <p class="label">Stok saat ini</p>
                        <p class="num text-2xl leading-tight font-semibold text-ink">{product.stock}</p>
                        <Link href="/admin/stok" class="mt-1 inline-flex min-h-9 items-center gap-1 text-sm font-medium text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">
                            Tambah / sesuaikan stok <ArrowRight size={14} />
                        </Link>
                    </div>
                {:else}
                    <div>
                        <label class="label" for="initial">Stok awal</label>
                        <input id="initial" type="number" min="0" class="input" bind:value={form.initial_stock} />
                        {#if form.errors.initial_stock}<p class="error">{form.errors.initial_stock}</p>{/if}
                    </div>
                {/if}
            </div>
        {/if}
    </div>

    <div class="space-y-4">
        <div class="card p-4 sm:p-5">
            <p class="label">Foto (opsional)</p>
            <div class="mb-3 aspect-[4/3] w-full overflow-hidden rounded-xl bg-paper-deep sm:aspect-square">
                {#if preview}
                    <img src={preview} alt="Pratinjau foto" class="size-full object-cover" />
                {:else}
                    <div class="flex size-full items-center justify-center text-faint" aria-hidden="true"><Drumstick size={44} strokeWidth={1.25} /></div>
                {/if}
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label class="btn-secondary btn-sm cursor-pointer">
                    <ImageUp size={15} /><span>{preview ? 'Ganti foto' : 'Pilih foto'}</span>
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" onchange={onPhoto} aria-label="Pilih foto" />
                </label>
                {#if preview}
                    <button type="button" class="btn-danger btn-sm" onclick={removePhoto}>Hapus foto</button>
                {/if}
            </div>
            <p class="hint">JPG/PNG/WebP, maks 5 MB. Otomatis dikompres.</p>
            {#if form.errors.photo}<p class="error">{form.errors.photo}</p>{/if}
        </div>

        <button class="btn-primary w-full" type="submit" disabled={form.processing}>
            {form.processing ? 'Menyimpan…' : isEdit ? 'Simpan perubahan' : 'Tambah produk'}
        </button>
    </div>
</form>
