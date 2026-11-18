<script>
    import { useForm } from '@inertiajs/svelte';
    import MoneyInput from '../MoneyInput.svelte';
    import { rupiah } from '../../lib/format.js';

    /** Form promo produk (dipakai di dalam Modal). Tanggal berlaku penuh satu hari (WIB). */
    let { promotion = null, products = [], channels = [], today, formId, onsaved } = $props();

    const form = useForm(() => ({
        product_id: promotion?.product_id ?? null,
        price: promotion?.price ?? null,
        channels: promotion?.channels ?? channels.map((c) => c.value),
        active_from: promotion?.active_from ?? today,
        active_until: promotion?.active_until ?? today,
        description: promotion?.description ?? '',
    }));

    const product = $derived(products.find((p) => p.id === Number(form.product_id)));
    const saving = $derived(product && form.price > 0 && form.price < product.price ? product.price - form.price : 0);
    const savingPercent = $derived(product && saving > 0 ? Math.round((saving / product.price) * 100) : 0);

    function toggleChannel(value, checked) {
        form.channels = checked ? [...new Set([...form.channels, value])] : form.channels.filter((c) => c !== value);
    }

    function submit(event) {
        event?.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => onsaved?.() };
        form.transform((data) => ({ ...data, product_id: Number(data.product_id) || null }));
        if (promotion) {
            form.put(`/admin/promo/${promotion.id}`, options);
        } else {
            form.post('/admin/promo', options);
        }
    }
</script>

<form id={formId} class="space-y-4" onsubmit={submit}>
    <div>
        <label class="label" for="promo-product">Produk</label>
        <select id="promo-product" class="input" bind:value={form.product_id} required>
            <option value={null} disabled>Pilih produk…</option>
            {#each products as item (item.id)}<option value={item.id}>{item.name} — {rupiah(item.price)}</option>{/each}
        </select>
        {#if form.errors.product_id}<p class="error">{form.errors.product_id}</p>{/if}
    </div>

    <div>
        <label class="label" for="promo-price">Harga promo</label>
        <MoneyInput id="promo-price" bind:value={form.price} />
        {#if form.errors.price}
            <p class="error">{form.errors.price}</p>
        {:else if product && saving > 0}
            <p class="hint">Harga normal <span class="num line-through">{rupiah(product.price)}</span> · hemat <span class="num">{rupiah(saving)}</span> ({savingPercent}%)</p>
        {:else if product}
            <p class="hint">Harus lebih kecil dari harga normal {rupiah(product.price)}.</p>
        {/if}
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="promo-from">Mulai</label>
            <input id="promo-from" type="date" class="input num" bind:value={form.active_from} required />
            {#if form.errors.active_from}<p class="error">{form.errors.active_from}</p>{/if}
        </div>
        <div>
            <label class="label" for="promo-until">Berakhir</label>
            <input id="promo-until" type="date" class="input num" min={form.active_from} bind:value={form.active_until} required />
            {#if form.errors.active_until}<p class="error">{form.errors.active_until}</p>{/if}
        </div>
    </div>
    <p class="-mt-2 text-xs text-muted">Berlaku dari 00:00 tanggal mulai sampai 23:59 tanggal berakhir (WIB).</p>

    <fieldset>
        <legend class="label">Berlaku untuk kanal</legend>
        <div class="flex flex-wrap gap-2">
            {#each channels as channel (channel.value)}
                <label class="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl border px-3.5 text-sm transition {form.channels.includes(channel.value) ? 'border-ink/40 bg-paper text-ink' : 'border-line text-ink-soft hover:border-line-strong'}">
                    <input type="checkbox" class="checkbox" checked={form.channels.includes(channel.value)} onchange={(e) => toggleChannel(channel.value, e.currentTarget.checked)} />
                    {channel.label}
                </label>
            {/each}
        </div>
        {#if form.errors.channels}<p class="error">{form.errors.channels}</p>{/if}
    </fieldset>

    <div>
        <label class="label" for="promo-desc">Deskripsi <span class="font-normal text-faint">opsional</span></label>
        <input id="promo-desc" class="input" bind:value={form.description} maxlength="255" placeholder="mis. Promo HUT toko, Menu baru!" />
        <p class="hint">Tampil di kartu produk pada tab Promo di menu online.</p>
        {#if form.errors.description}<p class="error">{form.errors.description}</p>{/if}
    </div>
</form>
