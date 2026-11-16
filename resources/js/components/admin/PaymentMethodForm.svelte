<script>
    import { useForm } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import Toggle from '../Toggle.svelte';

    /** Form metode pembayaran (dipakai di dalam Modal). */
    let { method = null, types = [], channels = [], formId, onsaved } = $props();

    const form = useForm(() => ({
        name: method?.name ?? '',
        type: method?.type ?? 'tunai',
        bank_name: method?.bank_name ?? '',
        account_number: method?.account_number ?? '',
        account_holder: method?.account_holder ?? '',
        qris_image: null,
        channels: method?.channels ?? channels.map((c) => c.value),
        is_active: method?.is_active ?? true,
        sort_order: method?.sort_order ?? 0,
    }));

    let preview = $state(untrack(() => method?.qris_image_url ?? null));

    const showAccount = $derived(form.type === 'transfer' || form.type === 'ewallet');

    function onImage(event) {
        const file = event.currentTarget.files?.[0] ?? null;
        form.qris_image = file;
        if (preview?.startsWith('blob:')) URL.revokeObjectURL(preview);
        preview = file ? URL.createObjectURL(file) : (method?.qris_image_url ?? null);
    }

    function toggleChannel(value, checked) {
        form.channels = checked ? [...new Set([...form.channels, value])] : form.channels.filter((c) => c !== value);
    }

    function submit(event) {
        event?.preventDefault();
        const options = { forceFormData: true, preserveScroll: true, onSuccess: () => onsaved?.() };
        if (method) {
            form.transform((data) => ({ ...data, _method: 'put' })).post(`/admin/pembayaran/${method.id}`, options);
        } else {
            form.post('/admin/pembayaran', options);
        }
    }
</script>

<form id={formId} class="space-y-4" onsubmit={submit}>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="pm-name">Nama</label>
            <input id="pm-name" class="input" bind:value={form.name} maxlength="50" placeholder="mis. QRIS, BCA, DANA" required />
            {#if form.errors.name}<p class="error">{form.errors.name}</p>{/if}
        </div>
        <div>
            <label class="label" for="pm-type">Tipe</label>
            <select id="pm-type" class="input" bind:value={form.type}>
                {#each types as type (type.value)}<option value={type.value}>{type.label}</option>{/each}
            </select>
            {#if form.errors.type}<p class="error">{form.errors.type}</p>{/if}
        </div>
    </div>

    {#if form.type === 'transfer'}
        <div>
            <label class="label" for="pm-bank">Nama bank</label>
            <input id="pm-bank" class="input" bind:value={form.bank_name} maxlength="50" />
            {#if form.errors.bank_name}<p class="error">{form.errors.bank_name}</p>{/if}
        </div>
    {/if}

    {#if showAccount}
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="pm-number">{form.type === 'transfer' ? 'No. rekening' : 'No. akun / HP'}</label>
                <input id="pm-number" class="input" bind:value={form.account_number} maxlength="50" />
                {#if form.errors.account_number}<p class="error">{form.errors.account_number}</p>{/if}
            </div>
            <div>
                <label class="label" for="pm-holder">Atas nama</label>
                <input id="pm-holder" class="input" bind:value={form.account_holder} maxlength="100" />
                {#if form.errors.account_holder}<p class="error">{form.errors.account_holder}</p>{/if}
            </div>
        </div>
    {/if}

    {#if form.type === 'qris'}
        <div>
            <p class="label">Gambar QR statis</p>
            {#if preview}<img src={preview} alt="QRIS" class="mb-2 size-40 rounded-xl border border-stone-200 object-contain" />{/if}
            <input type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm" onchange={onImage} aria-label="Pilih gambar QRIS" />
            <p class="mt-1 text-xs text-stone-500">Ditampilkan ke pelanggan saat memilih QRIS.</p>
            {#if form.errors.qris_image}<p class="error">{form.errors.qris_image}</p>{/if}
        </div>
    {/if}

    <fieldset>
        <legend class="label">Berlaku untuk kanal</legend>
        <div class="flex flex-wrap gap-3">
            {#each channels as channel (channel.value)}
                <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3 py-2 text-sm">
                    <input type="checkbox" class="checkbox" checked={form.channels.includes(channel.value)} onchange={(e) => toggleChannel(channel.value, e.currentTarget.checked)} />
                    {channel.label}
                </label>
            {/each}
        </div>
        {#if form.errors.channels}<p class="error">{form.errors.channels}</p>{/if}
    </fieldset>

    <div class="grid items-center gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="pm-sort">Urutan</label>
            <input id="pm-sort" type="number" min="0" class="input" bind:value={form.sort_order} />
        </div>
        <Toggle bind:checked={form.is_active} label="Aktif" />
    </div>
</form>
