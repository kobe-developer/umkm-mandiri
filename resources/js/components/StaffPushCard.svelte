<script>
    import { page } from '@inertiajs/svelte';
    import Bell from '@lucide/svelte/icons/bell';
    import Toggle from './Toggle.svelte';
    import { staffPush } from '../lib/staffPush.svelte.js';

    /** Pengaturan web push "order online baru" untuk perangkat ini (halaman Akun). */
    const vapidKey = $derived(page.props.config?.vapidPublicKey);
    const push = $derived(staffPush.state);

    const HINTS = {
        denied: 'Izin notifikasi diblokir di browser ini. Buka pengaturan situs di browser, izinkan Notifikasi, lalu muat ulang halaman.',
        'needs-install': 'Di iPhone/iPad: ketuk tombol Bagikan → "Tambahkan ke Layar Utama", lalu buka aplikasi dari layar utama untuk mengaktifkan notifikasi (iOS 16.4+).',
        unsupported: 'Browser ini tidak mendukung notifikasi web push, atau server belum dikonfigurasi.',
    };

    function onToggle(checked) {
        if (checked) staffPush.enable(vapidKey);
        else staffPush.disable();
    }
</script>

<section class="card space-y-4 p-4 sm:p-5" aria-labelledby="push-title">
    <div class="flex items-center gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-paper-deep text-ink-soft" aria-hidden="true"><Bell size={18} /></div>
        <h2 id="push-title" class="display text-lg text-ink">Notifikasi perangkat</h2>
    </div>
    <p class="text-sm text-muted">Terima notifikasi di perangkat ini setiap ada order online baru, walau tab aplikasi sedang tertutup.</p>

    {#if HINTS[push.support]}
        <p class="rounded-xl bg-amber-soft px-3.5 py-2.5 text-sm text-amber-ink">{HINTS[push.support]}</p>
    {:else}
        <div class="rounded-xl bg-paper px-4 py-3">
            <!-- dibuat ulang setelah proses selesai agar posisi switch selalu sesuai status sebenarnya -->
            {#key push.busy}<Toggle checked={push.subscribed} disabled={push.busy} label="Order online baru" description={push.subscribed ? 'Aktif di perangkat ini' : 'Nonaktif di perangkat ini'} onchange={onToggle} />{/key}
        </div>
    {/if}
    {#if push.error}<p class="error" role="alert">{push.error}</p>{/if}
</section>
