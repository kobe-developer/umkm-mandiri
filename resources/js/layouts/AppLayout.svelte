<script>
    import { Link, page, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { playNotification, unlockAudio } from '../lib/sound.js';

    let { children } = $props();

    const user = $derived(page.props.auth?.user);
    const pos = $derived(page.props.pos);
    const store = $derived(page.props.store);
    const pendingOnline = $derived(pos?.pending_online_count ?? 0);
    const currentUrl = $derived(page.url);

    let menuOpen = $state(false);
    let toast = $state(null);
    let toastTimer;

    const POS_NAV = [
        { href: '/pos', label: 'Dashboard', icon: '📊', exact: true },
        { href: '/pos/kasir', label: 'Kasir', icon: '🧾' },
        { href: '/pos/online', label: 'Order Online', icon: '🛵', badge: true },
        { href: '/pos/orders', label: 'Transaksi', icon: '📋' },
        { href: '/pos/produk', label: 'Ketersediaan', icon: '✅' },
        { href: '/pos/shift', label: 'Shift', icon: '💰' },
    ];
    const ADMIN_NAV = [
        { href: '/admin', label: 'Laporan & Tren', icon: '📈', exact: true },
        { href: '/admin/transaksi', label: 'Semua Transaksi', icon: '🗂️' },
        { href: '/admin/shift', label: 'Laporan Shift', icon: '🧮' },
        { href: '/admin/produk', label: 'Produk', icon: '🍗' },
        { href: '/admin/kategori', label: 'Kategori', icon: '🏷️' },
        { href: '/admin/stok', label: 'Stok', icon: '📦' },
        { href: '/admin/pembayaran', label: 'Metode Bayar', icon: '💳' },
        { href: '/admin/user', label: 'User', icon: '👥' },
        { href: '/admin/toko', label: 'Toko', icon: '🏪' },
        { href: '/admin/log', label: 'Log Aktivitas', icon: '🕵️' },
    ];
    const BOTTOM_NAV = POS_NAV.slice(0, 4);

    function isActive(item) {
        const path = currentUrl.split('?')[0];
        return item.exact ? path === item.href : path === item.href || path.startsWith(`${item.href}/`);
    }

    // --- Flash message → toast ---
    $effect(() => {
        const flash = page.props.flash ?? {};
        const message = flash.success ?? flash.error;
        if (!message) return;
        toast = { type: flash.success ? 'success' : 'error', message };
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => (toast = null), 4000);
    });

    // --- Notifikasi order online baru: suara + badge ---
    let previousPending = null;
    $effect(() => {
        const count = pendingOnline;
        if (previousPending !== null && count > previousPending) {
            playNotification();
            if (page.component === 'Pos/Online') router.reload({ preserveScroll: true });
        }
        previousPending = count;
    });

    function refreshPos() {
        if (document.visibilityState === 'visible') {
            router.reload({ only: ['pos'], preserveScroll: true, preserveState: true, showProgress: false });
        }
    }

    onMount(() => {
        // Polling cadangan (≤ 10 detik) bila WebSocket tidak tersedia
        const interval = setInterval(refreshPos, (page.props.config?.pollInterval ?? 10) * 1000);

        // WebSocket (Reverb) dimuat terpisah agar bundle menu publik tetap ringan
        let echo = null;
        if (page.props.config?.realtime) {
            import('../echo.js').then(({ getEcho }) => {
                echo = getEcho();
                echo?.private('orders')
                    .listen('.order.created', refreshPos)
                    .listen('.order.updated', () => {
                        refreshPos();
                        if (page.component === 'Pos/Online') router.reload({ preserveScroll: true, showProgress: false });
                    });
            });
        }

        const unlock = () => unlockAudio();
        window.addEventListener('pointerdown', unlock, { once: true });

        return () => {
            clearInterval(interval);
            echo?.leave('orders');
            window.removeEventListener('pointerdown', unlock);
        };
    });

    function logout() {
        router.post('/logout');
    }
</script>

{#snippet navLink(item)}
    <Link
        href={item.href}
        onclick={() => (menuOpen = false)}
        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {isActive(item) ? 'bg-brand-50 text-brand-700' : 'text-stone-700 hover:bg-stone-100'}"
    >
        <span aria-hidden="true">{item.icon}</span>
        <span class="flex-1">{item.label}</span>
        {#if item.badge && pendingOnline > 0}
            <span class="rounded-full bg-brand-600 px-2 py-0.5 text-xs font-bold text-white">{pendingOnline}</span>
        {/if}
    </Link>
{/snippet}

{#snippet sidebar()}
    <div class="flex h-full flex-col gap-4 overflow-y-auto p-4">
        <div class="flex items-center gap-2 px-1">
            <div class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-sm font-black text-white">UM</div>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold">{store?.name}</p>
                <p class="text-xs {store?.is_open ? 'text-emerald-600' : 'text-stone-500'}">● {store?.is_open ? 'Buka' : 'Tutup'}</p>
            </div>
        </div>

        <nav class="space-y-1">
            <p class="px-3 text-xs font-semibold tracking-wide text-stone-400 uppercase">Penjualan</p>
            {#each POS_NAV as item (item.href)}{@render navLink(item)}{/each}
        </nav>

        {#if user?.is_owner}
            <nav class="space-y-1">
                <p class="px-3 text-xs font-semibold tracking-wide text-stone-400 uppercase">Back-office</p>
                {#each ADMIN_NAV as item (item.href)}{@render navLink(item)}{/each}
            </nav>
        {/if}

        <div class="mt-auto space-y-1 border-t border-stone-100 pt-3">
            <Link href="/akun" class="block rounded-xl px-3 py-2 text-sm hover:bg-stone-100">
                <span class="font-semibold">{user?.name}</span>
                <span class="block text-xs text-stone-500 capitalize">{user?.role} · Akun saya</span>
            </Link>
            <button type="button" class="w-full rounded-xl px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50" onclick={logout}>Keluar</button>
        </div>
    </div>
{/snippet}

<div class="min-h-screen lg:pl-64">
    <!-- Sidebar desktop -->
    <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-stone-200 bg-white lg:block">
        {@render sidebar()}
    </aside>

    <!-- Drawer mobile -->
    {#if menuOpen}
        <div class="fixed inset-0 z-40 bg-black/40 lg:hidden" role="presentation" onclick={() => (menuOpen = false)}></div>
        <aside class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-xl lg:hidden">
            {@render sidebar()}
        </aside>
    {/if}

    <!-- Top bar mobile -->
    <header class="no-print sticky top-0 z-30 flex items-center gap-3 border-b border-stone-200 bg-white/95 px-4 py-2.5 backdrop-blur lg:hidden">
        <button type="button" class="btn-ghost btn-sm text-xl" aria-label="Buka menu" onclick={() => (menuOpen = true)}>☰</button>
        <p class="flex-1 truncate font-bold">{store?.name}</p>
        <span class="text-xs font-semibold {store?.is_open ? 'text-emerald-600' : 'text-stone-500'}">● {store?.is_open ? 'Buka' : 'Tutup'}</span>
    </header>

    {#if pos && !pos.open_shift && !currentUrl.startsWith('/pos/shift')}
        <div class="no-print border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
            Shift belum dibuka. <Link href="/pos/shift" class="font-semibold underline">Buka shift</Link> untuk mencatat transaksi.
        </div>
    {/if}

    <main class="mx-auto max-w-7xl px-4 py-5 pb-24 sm:px-6 lg:pb-8">
        {@render children()}
    </main>

    <!-- Bottom nav mobile (POS) -->
    <nav class="no-print fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-stone-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden">
        {#each BOTTOM_NAV as item (item.href)}
            <Link href={item.href} class="relative flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium {isActive(item) ? 'text-brand-600' : 'text-stone-500'}">
                <span class="text-lg" aria-hidden="true">{item.icon}</span>
                {item.label}
                {#if item.badge && pendingOnline > 0}
                    <span class="absolute top-1 right-[22%] rounded-full bg-brand-600 px-1.5 text-[10px] font-bold text-white">{pendingOnline}</span>
                {/if}
            </Link>
        {/each}
    </nav>

    {#if toast}
        <div class="no-print fixed inset-x-4 top-4 z-[60] mx-auto max-w-md sm:right-6 sm:left-auto" role="status">
            <div class="flex items-start gap-3 rounded-2xl px-4 py-3 text-sm font-medium shadow-lg {toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'}">
                <span class="flex-1">{toast.message}</span>
                <button type="button" class="opacity-80 hover:opacity-100" aria-label="Tutup" onclick={() => (toast = null)}>×</button>
            </div>
        </div>
    {/if}
</div>
