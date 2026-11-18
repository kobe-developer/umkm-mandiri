<script>
    import { page } from '@inertiajs/svelte';

    /**
     * Judul tab "{title} · {nama toko}" + favicon toko.
     * Favicon ikut diperbarui tanpa reload saat profil/logo toko diubah.
     */
    let { title = '' } = $props();

    const storeName = $derived(page.props.store?.name ?? 'UMKM Mandiri');
    const fullTitle = $derived(title ? `${title} · ${storeName}` : storeName);
    const faviconUrl = $derived(page.props.store?.favicon_url);

    $effect(() => {
        if (!faviconUrl) return;
        const link = document.querySelector('link[data-brand-favicon]');
        if (link && link.getAttribute('href') !== faviconUrl) link.setAttribute('href', faviconUrl);
    });
</script>

<svelte:head><title>{fullTitle}</title></svelte:head>
