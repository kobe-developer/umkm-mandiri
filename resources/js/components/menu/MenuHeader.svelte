<script>
    import Clock from '@lucide/svelte/icons/clock';
    import Bike from '@lucide/svelte/icons/bike';
    import MapPin from '@lucide/svelte/icons/map-pin';
    import ReceiptText from '@lucide/svelte/icons/receipt-text';
    import { Link } from '@inertiajs/svelte';
    import Logo from '../Logo.svelte';

    /** Header toko: nama, status Buka/Tutup, info delivery, jam buka hari ini, akses ke "Pesanan saya". */
    let { settings, hasOrderHistory = false } = $props();

    const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    const todayHours = $derived.by(() => {
        const day = DAYS[new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta' })).getDay()];
        const hours = settings.operating_hours?.[day];
        if (!hours) return null;
        return hours.closed ? 'Libur hari ini' : `${hours.open}–${hours.close} WIB`;
    });
</script>

<header class="mx-auto max-w-2xl px-5 pt-[max(1.5rem,env(safe-area-inset-top))] pb-6">
    <div class="flex items-center justify-between gap-3">
        <Logo name={settings.name} size={44} />
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold {settings.is_open ? 'border-leaf-100 bg-leaf-50 text-leaf-700' : 'border-line bg-surface text-muted'}">
                <span class="relative flex size-2">
                    {#if settings.is_open}<span class="absolute inline-flex size-full animate-ping rounded-full bg-leaf-500 opacity-50"></span>{/if}
                    <span class="relative inline-flex size-2 rounded-full {settings.is_open ? 'bg-leaf-500' : 'bg-faint'}"></span>
                </span>
                {settings.is_open ? 'Buka sekarang' : 'Sedang tutup'}
            </span>
            {#if hasOrderHistory}
                <Link href="/menu/pesanan" class="btn-icon border border-line bg-surface" aria-label="Pesanan saya" title="Pesanan saya">
                    <ReceiptText size={18} />
                </Link>
            {/if}
        </div>
    </div>

    <h1 class="display mt-5 text-[2.4rem] leading-[1.05] text-ink">{settings.name}</h1>
    {#if settings.address}
        <p class="mt-2 flex items-start gap-1.5 text-sm text-muted"><MapPin size={15} class="mt-0.5 shrink-0" />{settings.address}</p>
    {/if}

    {#if todayHours || (settings.delivery_note && settings.allow_delivery)}
        <dl class="mt-5 flex flex-wrap gap-x-5 gap-y-2 border-t border-line pt-4 text-sm">
            {#if todayHours}
                <div class="flex items-center gap-2 text-ink-soft">
                    <dt><Clock size={16} class="text-brand-600" /><span class="sr-only">Jam buka hari ini</span></dt>
                    <dd>{todayHours}</dd>
                </div>
            {/if}
            {#if settings.delivery_note && settings.allow_delivery}
                <div class="flex items-center gap-2 text-ink-soft">
                    <dt><Bike size={16} class="text-brand-600" /><span class="sr-only">Pengantaran</span></dt>
                    <dd>{settings.delivery_note}</dd>
                </div>
            {/if}
        </dl>
    {/if}
</header>
