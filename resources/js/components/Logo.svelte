<script>
    import { page } from '@inertiajs/svelte';

    /**
     * Logo toko dari profil toko: gambar logo bila diunggah, selain itu monogram inisial
     * (aturan inisial dari server: App\Support\StoreInitials) di atas lingkaran merah cabai.
     */
    let { size = 40, class: className = '' } = $props();

    const store = $derived(page.props.store ?? {});
    const initials = $derived(store.initials || 'UM');
</script>

{#if store.logo_url}
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-surface ring-1 ring-line {className}"
        style="width:{size}px;height:{size}px"
        aria-hidden="true"
    ><img src={store.logo_url} alt="" class="size-[76%] object-contain" /></span>
{:else}
    <span
        class="display inline-flex shrink-0 items-center justify-center rounded-full bg-brand-600 font-semibold text-white {className}"
        style="width:{size}px;height:{size}px;font-size:{Math.round(size * 0.4)}px"
        aria-hidden="true"
    >{initials}</span>
{/if}
