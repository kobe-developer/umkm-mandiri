<script>
    import { Link, page, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
    import Receipt from '@lucide/svelte/icons/receipt';
    import Bike from '@lucide/svelte/icons/bike';
    import ScrollText from '@lucide/svelte/icons/scroll-text';
    import ListChecks from '@lucide/svelte/icons/list-checks';
    import Wallet from '@lucide/svelte/icons/wallet';
    import ChartLine from '@lucide/svelte/icons/chart-line';
    import Layers from '@lucide/svelte/icons/layers';
    import ClipboardList from '@lucide/svelte/icons/clipboard-list';
    import UtensilsCrossed from '@lucide/svelte/icons/utensils-crossed';
    import Tag from '@lucide/svelte/icons/tag';
    import BadgePercent from '@lucide/svelte/icons/badge-percent';
    import Package from '@lucide/svelte/icons/package';
    import CreditCard from '@lucide/svelte/icons/credit-card';
    import Users from '@lucide/svelte/icons/users';
    import Store from '@lucide/svelte/icons/store';
    import Activity from '@lucide/svelte/icons/activity';
    import Ellipsis from '@lucide/svelte/icons/ellipsis';
    import LogOut from '@lucide/svelte/icons/log-out';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import CircleCheck from '@lucide/svelte/icons/circle-check';
    import CircleAlert from '@lucide/svelte/icons/circle-alert';
    import TriangleAlert from '@lucide/svelte/icons/triangle-alert';
    import X from '@lucide/svelte/icons/x';
    import BellRing from '@lucide/svelte/icons/bell-ring';
    import Logo from '../components/Logo.svelte';
    import { playNotification, unlockAudio } from '../lib/sound.js';
    import { staffPush } from '../lib/staffPush.svelte.js';

    let { children } = $props();

    const user = $derived(page.props.auth?.user);
    const pos = $derived(page.props.pos);
    const store = $derived(page.props.store);
    const pendingOnline = $derived(pos?.pending_online_count ?? 0);
    const currentUrl = $derived(page.url);

    const vapidKey = $derived(page.props.config?.vapidPublicKey);
    const push = $derived(staffPush.state);
    const showPushPrompt = $derived(push.support === 'ready' && !push.subscribed && !push.dismissed);

    let moreOpen = $state(false);
    let toast = $state(null);
    let toastTimer;

    const POS_NAV = [
        { href: '/pos', label: 'Beranda', icon: LayoutDashboard, exact: true },
        { href: '/pos/kasir', label: 'Kasir', icon: Receipt },
        { href: '/pos/online', label: 'Online', longLabel: 'Order Online', icon: Bike, badge: true },
        { href: '/pos/orders', label: 'Transaksi', icon: ScrollText },
        { href: '/pos/produk', label: 'Ketersediaan', icon: ListChecks },
        { href: '/pos/shift', label: 'Shift', icon: Wallet },
    ];
    const ADMIN_NAV = [
        { href: '/admin', label: 'Laporan & Tren', icon: ChartLine, exact: true },
        { href: '/admin/transaksi', label: 'Semua Transaksi', icon: Layers },
        { href: '/admin/shift', label: 'Laporan Shift', icon: ClipboardList },
        { href: '/admin/produk', label: 'Produk', icon: UtensilsCrossed },
        { href: '/admin/promo', label: 'Promo', icon: BadgePercent },
        { href: '/admin/kategori', label: 'Kategori', icon: Tag },
        { href: '/admin/stok', label: 'Stok', icon: Package },
        { href: '/admin/pembayaran', label: 'Metode Bayar', icon: CreditCard },
        { href: '/admin/user', label: 'User', icon: Users },
        { href: '/admin/toko', label: 'Toko', icon: Store },
        { href: '/admin/log', label: 'Log Aktivitas', icon: Activity },
    ];
    const BOTTOM_NAV = POS_NAV.slice(0, 4);
    const MORE_NAV = $derived([...POS_NAV.slice(4), ...(user?.is_owner ? ADMIN_NAV : [])]);
    const isMoreActive = $derived(MORE_NAV.some((item) => isActive(item)) || currentUrl.startsWith('/akun'));

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
        if (previousPending !== null && count > previousPending) playNotification();
        previousPending = count;
    });

    // Halaman berisi daftar order dimuat ulang penuh dalam SATU request (badge + daftar sekaligus),
    // halaman lain cukup memperbarui badge.
    const ORDER_LIST_PAGES = ['Pos/Online'];

    function refreshPos() {
        if (document.visibilityState !== 'visible') return;
        const only = ORDER_LIST_PAGES.includes(page.component) ? [] : ['pos'];
        router.reload({ only, preserveScroll: true, preserveState: true, showProgress: false });
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
                    .listen('.order.updated', refreshPos);
            });
        }

        staffPush.init(vapidKey);

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
    {@const active = isActive(item)}
    <Link
        href={item.href}
        onclick={() => (moreOpen = false)}
        aria-current={active ? 'page' : undefined}
        class="group flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition {active ? 'bg-surface text-ink shadow-soft' : 'text-ink-soft hover:bg-ink/[0.04] hover:text-ink'}"
    >
        <item.icon size={18} strokeWidth={active ? 2.25 : 1.75} class={active ? 'text-brand-600' : 'text-muted group-hover:text-ink-soft'} />
        <span class="flex-1">{item.longLabel ?? item.label}</span>
        {#if item.badge && pendingOnline > 0}
            <span class="num rounded-full bg-brand-600 px-2 py-0.5 text-xs font-bold text-white">{pendingOnline}</span>
        {/if}
    </Link>
{/snippet}

{#snippet storeStatus()}
    <span class="inline-flex items-center gap-1.5 text-xs font-medium {store?.is_open ? 'text-leaf-600' : 'text-muted'}">
        <span class="size-1.5 rounded-full {store?.is_open ? 'bg-leaf-500' : 'bg-faint'}"></span>
        {store?.is_open ? 'Buka' : 'Tutup'}
    </span>
{/snippet}

{#snippet account()}
    <div class="flex items-center gap-1 border-t border-line pt-3">
        <Link href="/akun" onclick={() => (moreOpen = false)} class="flex min-h-11 min-w-0 flex-1 items-center gap-3 rounded-xl px-2 hover:bg-ink/[0.04]">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-paper-deep text-sm font-semibold text-ink-soft">{user?.name?.[0]?.toUpperCase()}</span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-ink">{user?.name}</span>
                <span class="block text-xs text-muted"><span class="capitalize">{user?.role}</span> · Akun saya</span>
            </span>
        </Link>
        <button type="button" class="btn-icon text-muted hover:text-brand-600" aria-label="Keluar" title="Keluar" onclick={logout}><LogOut size={18} /></button>
    </div>
{/snippet}

<div class="min-h-dvh lg:pl-68">
    <!-- Sidebar desktop -->
    <aside class="no-print fixed inset-y-0 left-0 hidden w-68 flex-col border-r border-line bg-paper lg:flex">
        <div class="flex items-center gap-3 px-5 pt-6 pb-5">
            <Logo name={store?.name} size={40} />
            <div class="min-w-0">
                <p class="display truncate text-[17px] leading-tight text-ink">{store?.name}</p>
                {@render storeStatus()}
            </div>
        </div>
        <div class="flex-1 space-y-6 overflow-y-auto px-3 pb-4">
            <nav class="space-y-0.5" aria-label="Penjualan">
                <p class="eyebrow px-3 pb-1.5">Penjualan</p>
                {#each POS_NAV as item (item.href)}{@render navLink(item)}{/each}
            </nav>
            {#if user?.is_owner}
                <nav class="space-y-0.5" aria-label="Back-office">
                    <p class="eyebrow px-3 pb-1.5">Back-office</p>
                    {#each ADMIN_NAV as item (item.href)}{@render navLink(item)}{/each}
                </nav>
            {/if}
        </div>
        <div class="px-3 pb-4">{@render account()}</div>
    </aside>

    <!-- Top bar mobile -->
    <header class="no-print sticky top-0 z-30 flex items-center gap-3 border-b border-line/70 bg-paper/85 px-4 pt-[env(safe-area-inset-top)] backdrop-blur-xl lg:hidden">
        <div class="flex min-h-14 flex-1 items-center gap-2.5">
            <Logo name={store?.name} size={30} />
            <p class="display min-w-0 flex-1 truncate text-[17px] text-ink">{store?.name}</p>
            {@render storeStatus()}
        </div>
    </header>

    {#if pos && !pos.open_shift && !currentUrl.startsWith('/pos/shift')}
        <div class="no-print mx-auto max-w-6xl px-4 pt-4 sm:px-6 lg:px-10 lg:pt-6">
            <Link href="/pos/shift" class="flex items-center gap-3 rounded-2xl bg-amber-soft px-4 py-3 text-sm text-amber-ink transition hover:brightness-[0.98]">
                <TriangleAlert size={18} class="shrink-0" />
                <span class="flex-1"><strong class="font-semibold">Shift belum dibuka.</strong> Buka shift untuk mencatat transaksi.</span>
                <ChevronRight size={18} class="shrink-0 opacity-60" />
            </Link>
        </div>
    {/if}

    {#if showPushPrompt}
        <div class="no-print mx-auto max-w-6xl px-4 pt-4 sm:px-6 lg:px-10 lg:pt-6">
            <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-surface px-4 py-3 text-sm ring-1 ring-line">
                <BellRing size={18} class="shrink-0 text-brand-600" aria-hidden="true" />
                <span class="min-w-0 flex-1 text-ink-soft"><strong class="font-semibold text-ink">Aktifkan notifikasi</strong> agar order online baru tetap terdengar walau aplikasi tertutup.</span>
                <div class="flex items-center gap-1">
                    <button type="button" class="btn-ghost btn-sm text-muted" onclick={() => staffPush.dismissPrompt()}>Nanti</button>
                    <button type="button" class="btn-primary btn-sm" disabled={push.busy} onclick={() => staffPush.enable(vapidKey)}>Aktifkan</button>
                </div>
                {#if push.error}<p class="w-full text-xs text-brand-600" role="alert">{push.error}</p>{/if}
            </div>
        </div>
    {/if}

    <main class="mx-auto max-w-6xl px-4 pt-5 pb-28 sm:px-6 lg:px-10 lg:pt-8 lg:pb-12">
        {@render children()}
    </main>

    <!-- Bottom nav mobile -->
    <nav class="no-print fixed inset-x-0 bottom-0 z-30 border-t border-line/70 bg-surface/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden" aria-label="Navigasi utama">
        <div class="mx-auto grid max-w-md grid-cols-5">
            {#each BOTTOM_NAV as item (item.href)}
                {@const active = isActive(item)}
                <Link href={item.href} aria-current={active ? 'page' : undefined} class="relative flex flex-col items-center gap-1 pt-2 pb-1.5 text-[11px] font-medium {active ? 'text-ink' : 'text-muted'}">
                    <span class="flex h-8 w-14 items-center justify-center rounded-full transition {active ? 'bg-paper-deep' : ''}">
                        <item.icon size={21} strokeWidth={active ? 2.25 : 1.75} />
                    </span>
                    {item.label}
                    {#if item.badge && pendingOnline > 0}
                        <span class="num absolute top-1 left-1/2 ml-2 min-w-5 animate-pop rounded-full bg-brand-600 px-1.5 text-center text-[10px] leading-5 font-bold text-white ring-2 ring-surface">{pendingOnline}</span>
                    {/if}
                </Link>
            {/each}
            <button type="button" class="flex flex-col items-center gap-1 pt-2 pb-1.5 text-[11px] font-medium {isMoreActive || moreOpen ? 'text-ink' : 'text-muted'}" onclick={() => (moreOpen = true)} aria-haspopup="dialog">
                <span class="flex h-8 w-14 items-center justify-center rounded-full transition {isMoreActive ? 'bg-paper-deep' : ''}">
                    <Ellipsis size={21} strokeWidth={isMoreActive ? 2.25 : 1.75} />
                </span>
                Lainnya
            </button>
        </div>
    </nav>

    <!-- Sheet "Lainnya" mobile -->
    {#if moreOpen}
        <div class="fixed inset-0 z-50 animate-fade-in bg-ink/40 backdrop-blur-[2px] lg:hidden" role="presentation" onclick={() => (moreOpen = false)}></div>
        <div class="fixed inset-x-0 bottom-0 z-50 flex max-h-[85dvh] animate-sheet-up flex-col rounded-t-[1.75rem] bg-paper shadow-float lg:hidden" role="dialog" aria-modal="true" aria-label="Menu lainnya">
            <div class="flex justify-center pt-2.5" aria-hidden="true"><span class="h-1 w-10 rounded-full bg-line-strong"></span></div>
            <div class="flex items-center justify-between px-5 pt-2 pb-3">
                <p class="display text-xl text-ink">Menu</p>
                <button type="button" class="btn-icon -mr-2" aria-label="Tutup" onclick={() => (moreOpen = false)}><X size={20} /></button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto px-3 pb-3">
                <nav class="space-y-0.5" aria-label="Penjualan lainnya">
                    {#each POS_NAV.slice(4) as item (item.href)}{@render navLink(item)}{/each}
                </nav>
                {#if user?.is_owner}
                    <nav class="space-y-0.5" aria-label="Back-office">
                        <p class="eyebrow px-3 pb-1.5">Back-office</p>
                        {#each ADMIN_NAV as item (item.href)}{@render navLink(item)}{/each}
                    </nav>
                {/if}
            </div>
            <div class="safe-bottom px-3">{@render account()}</div>
        </div>
    {/if}

    {#if toast}
        <div class="no-print pointer-events-none fixed inset-x-4 bottom-24 z-[60] flex justify-center lg:inset-x-auto lg:top-6 lg:right-6 lg:bottom-auto" role="status" aria-live="polite">
            <div class="pointer-events-auto flex w-full max-w-sm animate-sheet-up items-center gap-3 rounded-2xl bg-ink py-3 pr-2 pl-4 text-sm text-paper shadow-float">
                {#if toast.type === 'success'}<CircleCheck size={18} class="shrink-0 text-leaf-100" />{:else}<CircleAlert size={18} class="shrink-0 text-brand-200" />{/if}
                <span class="flex-1">{toast.message}</span>
                <button type="button" class="flex size-8 items-center justify-center rounded-full text-paper/60 hover:bg-white/10 hover:text-paper" aria-label="Tutup" onclick={() => (toast = null)}><X size={16} /></button>
            </div>
        </div>
    {/if}
</div>
