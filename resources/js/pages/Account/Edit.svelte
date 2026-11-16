<script>
    import { useForm, page } from '@inertiajs/svelte';
    import Lock from '@lucide/svelte/icons/lock';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import PageHeader from '../../components/PageHeader.svelte';

    let { hasPin } = $props();

    const isOwner = $derived(page.props.auth.user?.is_owner ?? false);

    const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
    const pinForm = useForm({ current_password: '', pin: '', pin_confirmation: '' });

    function updatePassword(event) {
        event.preventDefault();
        passwordForm.put('/akun/password', { preserveScroll: true, onSuccess: () => passwordForm.reset() });
    }

    function updatePin(event) {
        event.preventDefault();
        pinForm.put('/akun/pin', { preserveScroll: true, onSuccess: () => pinForm.reset() });
    }
</script>

<svelte:head><title>Akun Saya</title></svelte:head>

<PageHeader title="Akun Saya" subtitle={`${page.props.auth.user?.name} · ${page.props.auth.user?.username}`} />

<div class="grid max-w-4xl items-start gap-4 md:grid-cols-2">
    <form class="card space-y-4 p-4 sm:p-5" onsubmit={updatePassword}>
        <div class="flex items-center gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-paper-deep text-ink-soft" aria-hidden="true"><Lock size={18} /></div>
            <h2 class="display text-lg text-ink">Ganti password</h2>
        </div>
        <div>
            <label class="label" for="current_password">Password saat ini</label>
            <input id="current_password" type="password" class="input" autocomplete="current-password" bind:value={passwordForm.current_password} required />
            {#if passwordForm.errors.current_password}<p class="error">{passwordForm.errors.current_password}</p>{/if}
        </div>
        <div>
            <label class="label" for="password">Password baru</label>
            <input id="password" type="password" class="input" autocomplete="new-password" minlength="8" bind:value={passwordForm.password} required />
            {#if passwordForm.errors.password}<p class="error">{passwordForm.errors.password}</p>{/if}
        </div>
        <div>
            <label class="label" for="password_confirmation">Ulangi password baru</label>
            <input id="password_confirmation" type="password" class="input" autocomplete="new-password" bind:value={passwordForm.password_confirmation} required />
        </div>
        <button class="btn-primary mt-2 w-full" type="submit" disabled={passwordForm.processing}>Simpan password</button>
    </form>

    {#if isOwner}
        <form class="card space-y-4 p-4 sm:p-5" onsubmit={updatePin}>
            <div>
                <div class="flex items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-paper-deep text-ink-soft" aria-hidden="true"><ShieldCheck size={18} /></div>
                    <div class="flex min-w-0 flex-1 flex-wrap items-center justify-between gap-2">
                        <h2 class="display text-lg text-ink">PIN owner</h2>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {hasPin ? 'bg-leaf-50 text-leaf-700' : 'bg-amber-soft text-amber-ink'}">
                            <span class="size-1.5 rounded-full {hasPin ? 'bg-leaf-500' : 'bg-amber-ink'}"></span>{hasPin ? 'PIN sudah diatur.' : 'PIN belum diatur.'}
                        </span>
                    </div>
                </div>
                <p class="mt-3 text-sm text-muted">Dipakai untuk menyetujui void/refund oleh kasir.</p>
            </div>
            <div>
                <label class="label" for="pin_current_password">Password saat ini</label>
                <input id="pin_current_password" type="password" class="input" autocomplete="current-password" bind:value={pinForm.current_password} required />
                {#if pinForm.errors.current_password}<p class="error">{pinForm.errors.current_password}</p>{/if}
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label" for="pin">PIN baru (6 angka)</label>
                    <input id="pin" type="password" inputmode="numeric" pattern="[0-9]{'{'}6{'}'}" minlength="6" maxlength="6" autocomplete="off" class="input num tracking-[0.3em]" bind:value={pinForm.pin} required />
                </div>
                <div>
                    <label class="label" for="pin_confirmation">Ulangi PIN</label>
                    <input id="pin_confirmation" type="password" inputmode="numeric" maxlength="6" autocomplete="off" class="input num tracking-[0.3em]" bind:value={pinForm.pin_confirmation} required />
                </div>
            </div>
            {#if pinForm.errors.pin}<p class="error">{pinForm.errors.pin}</p>{/if}
            <button class="btn-primary mt-2 w-full" type="submit" disabled={pinForm.processing}>{hasPin ? 'Ganti PIN' : 'Simpan PIN'}</button>
        </form>
    {/if}
</div>
