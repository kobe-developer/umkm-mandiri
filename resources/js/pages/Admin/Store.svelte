<script>
    import { router, useForm } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import Toggle from '../../components/Toggle.svelte';
    import MoneyInput from '../../components/MoneyInput.svelte';
    import OperatingHours from '../../components/admin/OperatingHours.svelte';

    let { settings, days = [], withinHours = false, menuUrl } = $props();

    let toggling = $state(false);
    let copied = $state(false);

    const form = useForm(() => ({
        store_name: settings.store_name ?? '',
        address: settings.address ?? '',
        whatsapp_number: settings.whatsapp_number ?? '',
        delivery_note: settings.delivery_note ?? '',
        receipt_note: settings.receipt_note ?? '',
        auto_schedule: settings.auto_schedule,
        online_order_enabled: settings.online_order_enabled,
        min_order: settings.min_order ?? 0,
        allow_pickup: settings.allow_pickup,
        allow_delivery: settings.allow_delivery,
        cashier_can_discount: settings.cashier_can_discount,
        cashier_can_view_revenue: settings.cashier_can_view_revenue,
        operating_hours: Object.fromEntries(days.map((d) => [d, { open: '10:00', close: '22:00', closed: false, ...(settings.operating_hours?.[d] ?? {}) }])),
    }));

    function setOpen(isOpen) {
        toggling = true;
        router.put('/admin/toko/status', { is_open: isOpen }, { preserveScroll: true, onFinish: () => (toggling = false) });
    }

    async function copyLink() {
        try {
            await navigator.clipboard.writeText(menuUrl);
            copied = true;
            setTimeout(() => (copied = false), 2000);
        } catch {
            window.prompt('Salin link menu:', menuUrl);
        }
    }

    function submit(event) {
        event.preventDefault();
        form.transform((data) => ({ ...data, min_order: data.min_order ?? 0 })).put('/admin/toko', { preserveScroll: true });
    }
</script>

<svelte:head><title>Pengaturan Toko</title></svelte:head>

<PageHeader title="Pengaturan Toko" />

<section class="card mb-4 flex flex-wrap items-center justify-between gap-4 p-5 {settings.is_open ? 'border-emerald-300 bg-emerald-50' : 'border-stone-300 bg-stone-100'}">
    <div>
        <p class="text-sm text-stone-600">Status toko saat ini</p>
        <p class="text-3xl font-black {settings.is_open ? 'text-emerald-700' : 'text-stone-700'}">{settings.is_open ? 'BUKA' : 'TUTUP'}</p>
        <p class="mt-1 text-xs text-stone-600">
            {#if settings.auto_schedule}
                Jadwal otomatis aktif · sekarang {withinHours ? 'dalam' : 'di luar'} jam operasional. Override manual berlaku sampai batas jadwal berikutnya.
            {:else}
                Jadwal otomatis nonaktif — buka/tutup hanya manual.
            {/if}
        </p>
        {#if !settings.is_open}<p class="mt-1 text-xs text-stone-600">Saat tutup, menu online tetap bisa dilihat tetapi checkout dinonaktifkan.</p>{/if}
    </div>
    {#if settings.is_open}
        <button type="button" class="btn-danger px-6 py-3 text-base" disabled={toggling} onclick={() => setOpen(false)}>Tutup toko</button>
    {:else}
        <button type="button" class="btn-success px-6 py-3 text-base" disabled={toggling} onclick={() => setOpen(true)}>Buka toko</button>
    {/if}
</section>

<section class="card mb-4 flex flex-wrap items-center gap-3 p-4">
    <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold">Link menu online</p>
        <p class="truncate text-sm text-stone-600">{menuUrl}</p>
    </div>
    <button type="button" class="btn-secondary btn-sm" onclick={copyLink}>{copied ? '✓ Tersalin' : 'Salin link'}</button>
    <a href={menuUrl} target="_blank" rel="noopener" class="btn-ghost btn-sm">Buka</a>
</section>

<form class="grid gap-4 lg:grid-cols-2" onsubmit={submit}>
    <section class="card space-y-4 p-5">
        <h2 class="font-semibold">Profil toko</h2>
        <div>
            <label class="label" for="s-name">Nama toko</label>
            <input id="s-name" class="input" bind:value={form.store_name} maxlength="100" required />
            {#if form.errors.store_name}<p class="error">{form.errors.store_name}</p>{/if}
        </div>
        <div>
            <label class="label" for="s-address">Alamat</label>
            <textarea id="s-address" class="input" rows="2" maxlength="255" bind:value={form.address}></textarea>
            {#if form.errors.address}<p class="error">{form.errors.address}</p>{/if}
        </div>
        <div>
            <label class="label" for="s-wa">Nomor WA toko</label>
            <input id="s-wa" class="input" inputmode="tel" bind:value={form.whatsapp_number} placeholder="08xx / 62xx" />
            {#if form.errors.whatsapp_number}<p class="error">{form.errors.whatsapp_number}</p>{/if}
        </div>
        <div>
            <label class="label" for="s-delivery">Info area delivery</label>
            <input id="s-delivery" class="input" bind:value={form.delivery_note} maxlength="255" placeholder="Free delivery sekitaran kampus" />
            {#if form.errors.delivery_note}<p class="error">{form.errors.delivery_note}</p>{/if}
        </div>
        <div>
            <label class="label" for="s-receipt">Catatan di struk</label>
            <input id="s-receipt" class="input" bind:value={form.receipt_note} maxlength="255" />
            {#if form.errors.receipt_note}<p class="error">{form.errors.receipt_note}</p>{/if}
        </div>
    </section>

    <div class="space-y-4">
        <section class="card space-y-4 p-5">
            <h2 class="font-semibold">Order online</h2>
            <Toggle bind:checked={form.online_order_enabled} label="Order online aktif" description="Matikan untuk menonaktifkan checkout tanpa menutup toko." />
            <Toggle bind:checked={form.allow_pickup} label="Ambil sendiri" />
            <Toggle bind:checked={form.allow_delivery} label="Diantar" />
            <div>
                <label class="label" for="s-min">Minimal order</label>
                <MoneyInput id="s-min" bind:value={form.min_order} />
                {#if form.errors.min_order}<p class="error">{form.errors.min_order}</p>{/if}
            </div>
        </section>

        <section class="card space-y-4 p-5">
            <h2 class="font-semibold">Hak akses kasir</h2>
            <Toggle bind:checked={form.cashier_can_view_revenue} label="Kasir boleh melihat omzet" description="Jika mati, kasir hanya melihat jumlah transaksi & produk terlaris." />
            <Toggle bind:checked={form.cashier_can_discount} label="Kasir boleh memberi diskon manual" />
        </section>
    </div>

    <section class="card p-5 lg:col-span-2">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold">Jam operasional</h2>
            <div class="w-full sm:w-auto sm:min-w-80">
                <Toggle bind:checked={form.auto_schedule} label="Buka/tutup otomatis sesuai jadwal" />
            </div>
        </div>
        <OperatingHours bind:hours={form.operating_hours} {days} errors={form.errors} />
    </section>

    <div class="flex justify-end lg:col-span-2">
        <button class="btn-primary" type="submit" disabled={form.processing}>{form.processing ? 'Menyimpan…' : 'Simpan pengaturan'}</button>
    </div>
</form>
