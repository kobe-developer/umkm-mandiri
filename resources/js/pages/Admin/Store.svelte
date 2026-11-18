<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import { router, useForm } from '@inertiajs/svelte';
    import Copy from '@lucide/svelte/icons/copy';
    import Check from '@lucide/svelte/icons/check';
    import ExternalLink from '@lucide/svelte/icons/external-link';
    import LinkIcon from '@lucide/svelte/icons/link';
    import Power from '@lucide/svelte/icons/power';
    import PageHeader from '../../components/PageHeader.svelte';
    import Toggle from '../../components/Toggle.svelte';
    import MoneyInput from '../../components/MoneyInput.svelte';
    import OperatingHours from '../../components/admin/OperatingHours.svelte';
    import StoreLogoField from '../../components/admin/StoreLogoField.svelte';

    let { settings, days = [], withinHours = false, menuUrl, brandIcons = {} } = $props();

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

<PageTitle title="Pengaturan Toko" />

<PageHeader title="Pengaturan Toko" />

<section class="mb-4 rounded-card p-5 sm:p-6 {settings.is_open ? 'border border-leaf-100 bg-leaf-50' : 'bg-paper-deep'}">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm text-ink-soft">Status toko saat ini</p>
            <p class="display mt-1 flex items-center gap-3 text-4xl leading-none {settings.is_open ? 'text-leaf-700' : 'text-ink'}">
                <span class="relative flex size-3" aria-hidden="true">
                    {#if settings.is_open}<span class="absolute inline-flex size-full animate-ping rounded-full bg-leaf-500 opacity-50"></span>{/if}
                    <span class="relative inline-flex size-3 rounded-full {settings.is_open ? 'bg-leaf-500' : 'bg-faint'}"></span>
                </span>
                {settings.is_open ? 'Buka' : 'Tutup'}
            </p>
            <p class="mt-3 max-w-xl text-xs leading-relaxed text-ink-soft">
                {#if settings.auto_schedule}
                    Jadwal otomatis aktif · sekarang {withinHours ? 'dalam' : 'di luar'} jam operasional. Override manual berlaku sampai batas jadwal berikutnya.
                {:else}
                    Jadwal otomatis nonaktif — buka/tutup hanya manual.
                {/if}
            </p>
            {#if !settings.is_open}<p class="mt-1 max-w-xl text-xs leading-relaxed text-ink-soft">Saat tutup, menu online tetap bisa dilihat tetapi checkout dinonaktifkan.</p>{/if}
        </div>
        {#if settings.is_open}
            <button type="button" class="btn-danger w-full shrink-0 sm:w-auto" disabled={toggling} onclick={() => setOpen(false)}><Power size={18} />Tutup toko</button>
        {:else}
            <button type="button" class="btn-success w-full shrink-0 sm:w-auto" disabled={toggling} onclick={() => setOpen(true)}><Power size={18} />Buka toko</button>
        {/if}
    </div>
</section>

<section class="card mb-8 flex flex-wrap items-center gap-x-3 gap-y-3 p-4">
    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-paper-deep text-ink-soft" aria-hidden="true"><LinkIcon size={18} /></div>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-ink">Link menu online</p>
        <p class="truncate text-sm text-muted">{menuUrl}</p>
    </div>
    <div class="flex w-full gap-2 sm:w-auto [&>*]:flex-1 sm:[&>*]:flex-none">
        <button type="button" class="btn-secondary" onclick={copyLink}>
            {#if copied}<Check size={15} class="text-leaf-600" />Tersalin{:else}<Copy size={15} />Salin link{/if}
        </button>
        <a href={menuUrl} target="_blank" rel="noopener" class="btn-ghost"><ExternalLink size={15} />Buka</a>
    </div>
</section>

<form class="grid gap-4 lg:grid-cols-2" onsubmit={submit}>
    <section class="card space-y-4 p-4 sm:p-5">
        <h2 class="display text-lg text-ink">Profil toko</h2>
        <StoreLogoField icons={brandIcons} />
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
        <section class="card p-4 sm:p-5">
            <h2 class="display mb-1 text-lg text-ink">Order online</h2>
            <div class="divide-y divide-line [&>*]:py-3.5 [&>*:last-child]:pb-0">
                <Toggle bind:checked={form.online_order_enabled} label="Order online aktif" description="Matikan untuk menonaktifkan checkout tanpa menutup toko." />
                <Toggle bind:checked={form.allow_pickup} label="Ambil sendiri" />
                <Toggle bind:checked={form.allow_delivery} label="Diantar" />
                <div>
                    <label class="label" for="s-min">Minimal order</label>
                    <MoneyInput id="s-min" bind:value={form.min_order} />
                    {#if form.errors.min_order}<p class="error">{form.errors.min_order}</p>{/if}
                </div>
            </div>
        </section>

        <section class="card p-4 sm:p-5">
            <h2 class="display mb-1 text-lg text-ink">Hak akses kasir</h2>
            <div class="divide-y divide-line [&>*]:py-3.5 [&>*:last-child]:pb-0">
                <Toggle bind:checked={form.cashier_can_view_revenue} label="Kasir boleh melihat omzet" description="Jika mati, kasir hanya melihat jumlah transaksi & produk terlaris." />
                <Toggle bind:checked={form.cashier_can_discount} label="Kasir boleh memberi diskon manual" />
            </div>
        </section>
    </div>

    <section class="card p-4 sm:p-5 lg:col-span-2">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="display text-lg text-ink">Jam operasional</h2>
            <div class="w-full rounded-xl bg-paper px-4 py-3 sm:w-auto sm:min-w-80">
                <Toggle bind:checked={form.auto_schedule} label="Buka/tutup otomatis sesuai jadwal" />
            </div>
        </div>
        <OperatingHours bind:hours={form.operating_hours} {days} errors={form.errors} />
    </section>

    <div class="flex justify-end lg:col-span-2">
        <button class="btn-primary w-full sm:w-auto" type="submit" disabled={form.processing}>{form.processing ? 'Menyimpan…' : 'Simpan pengaturan'}</button>
    </div>
</form>
