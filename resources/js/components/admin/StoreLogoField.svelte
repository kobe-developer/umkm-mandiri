<script>
    import { router, page } from '@inertiajs/svelte';
    import ImageUp from '@lucide/svelte/icons/image-up';
    import Trash from '@lucide/svelte/icons/trash';
    import Logo from '../Logo.svelte';

    /**
     * Logo toko: diunggah/dihapus langsung (terpisah dari form profil).
     * icons: URL ikon hasil generate server untuk pratinjau (favicon, notifikasi, ikon aplikasi).
     */
    let { icons = {} } = $props();

    let busy = $state(false);

    const store = $derived(page.props.store ?? {});
    const error = $derived(page.props.errors?.logo);
    const PREVIEWS = [
        { key: 'favicon', label: 'Tab browser', class: 'size-8 rounded-full' },
        { key: 'notification', label: 'Notifikasi', class: 'size-12 rounded-full' },
        { key: 'icon-192', label: 'Ikon aplikasi', class: 'size-12 rounded-[0.9rem]' },
    ];

    function upload(event) {
        const file = event.currentTarget.files?.[0];
        event.currentTarget.value = '';
        if (!file) return;
        busy = true;
        router.post('/admin/toko/logo', { logo: file }, { forceFormData: true, preserveScroll: true, preserveState: true, onFinish: () => (busy = false) });
    }

    function remove() {
        busy = true;
        router.delete('/admin/toko/logo', { preserveScroll: true, preserveState: true, onFinish: () => (busy = false) });
    }
</script>

<div>
    <p class="label">Logo toko</p>
    <div class="flex items-center gap-4">
        <Logo size={72} />
        <div class="min-w-0 space-y-2">
            <div class="flex flex-wrap gap-2">
                <label class="btn-secondary btn-sm cursor-pointer {busy ? 'pointer-events-none opacity-60' : ''}">
                    <ImageUp size={15} /><span>{store.logo_url ? 'Ganti logo' : 'Unggah logo'}</span>
                    <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" onchange={upload} disabled={busy} aria-label="Pilih file logo" />
                </label>
                {#if store.logo_url}
                    <button type="button" class="btn-ghost btn-sm text-muted hover:text-brand-600" disabled={busy} onclick={remove}><Trash size={15} />Hapus</button>
                {/if}
            </div>
            <p class="hint !mt-0">
                Dipakai untuk favicon, ikon aplikasi & notifikasi. Disarankan PNG persegi berlatar transparan, min. 128×128 px.
                {#if !store.logo_url}Tanpa logo, dipakai inisial nama toko: <strong class="font-semibold text-ink-soft">{store.initials}</strong>.{/if}
            </p>
        </div>
    </div>
    {#if error}<p class="error">{error}</p>{/if}

    <div class="mt-3 flex flex-wrap items-end gap-5 rounded-xl bg-paper px-4 py-3">
        {#each PREVIEWS as preview (preview.key)}
            {#if icons[preview.key]}
                <figure class="flex flex-col items-center gap-1.5">
                    <img src={icons[preview.key]} alt="" class={preview.class} width="48" height="48" />
                    <figcaption class="text-[11px] text-muted">{preview.label}</figcaption>
                </figure>
            {/if}
        {/each}
    </div>
</div>
