<script>
    /** Header toko: nama, status Buka/Tutup, info delivery, jam buka hari ini. */
    let { settings } = $props();

    const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    const todayHours = $derived.by(() => {
        const day = DAYS[new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta' })).getDay()];
        const hours = settings.operating_hours?.[day];
        if (!hours) return null;
        return hours.closed ? 'Libur hari ini' : `Buka ${hours.open}–${hours.close} WIB`;
    });
</script>

<header class="bg-gradient-to-br from-brand-600 to-orange-500 px-4 pt-6 pb-5 text-white">
    <div class="mx-auto max-w-2xl">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-2xl leading-tight font-black">{settings.name}</h1>
                {#if settings.address}<p class="mt-0.5 text-sm text-white/80">{settings.address}</p>{/if}
            </div>
            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {settings.is_open ? 'bg-emerald-500 text-white' : 'bg-white/90 text-stone-700'}">
                ● {settings.is_open ? 'Buka' : 'Tutup'}
            </span>
        </div>
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
            {#if settings.delivery_note && settings.allow_delivery}
                <span class="rounded-full bg-white/20 px-3 py-1 font-medium">🛵 {settings.delivery_note}</span>
            {/if}
            {#if todayHours}
                <span class="rounded-full bg-white/20 px-3 py-1 font-medium">🕒 {todayHours}</span>
            {/if}
        </div>
    </div>
</header>
