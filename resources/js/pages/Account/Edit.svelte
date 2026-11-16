<script>
    import { useForm, page } from '@inertiajs/svelte';
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

<div class="grid max-w-4xl gap-4 md:grid-cols-2">
    <form class="card space-y-3 p-5" onsubmit={updatePassword}>
        <h2 class="font-semibold">Ganti password</h2>
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
        <button class="btn-primary w-full" type="submit" disabled={passwordForm.processing}>Simpan password</button>
    </form>

    {#if isOwner}
        <form class="card space-y-3 p-5" onsubmit={updatePin}>
            <div>
                <h2 class="font-semibold">PIN owner</h2>
                <p class="text-sm text-stone-500">
                    Dipakai untuk menyetujui void/refund oleh kasir.
                    <span class="font-semibold {hasPin ? 'text-emerald-700' : 'text-amber-700'}">{hasPin ? 'PIN sudah diatur.' : 'PIN belum diatur.'}</span>
                </p>
            </div>
            <div>
                <label class="label" for="pin_current_password">Password saat ini</label>
                <input id="pin_current_password" type="password" class="input" autocomplete="current-password" bind:value={pinForm.current_password} required />
                {#if pinForm.errors.current_password}<p class="error">{pinForm.errors.current_password}</p>{/if}
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="label" for="pin">PIN baru (6 angka)</label>
                    <input id="pin" type="password" inputmode="numeric" pattern="[0-9]{'{'}6{'}'}" minlength="6" maxlength="6" autocomplete="off" class="input tracking-widest" bind:value={pinForm.pin} required />
                </div>
                <div>
                    <label class="label" for="pin_confirmation">Ulangi PIN</label>
                    <input id="pin_confirmation" type="password" inputmode="numeric" maxlength="6" autocomplete="off" class="input tracking-widest" bind:value={pinForm.pin_confirmation} required />
                </div>
            </div>
            {#if pinForm.errors.pin}<p class="error">{pinForm.errors.pin}</p>{/if}
            <button class="btn-primary w-full" type="submit" disabled={pinForm.processing}>{hasPin ? 'Ganti PIN' : 'Simpan PIN'}</button>
        </form>
    {/if}
</div>
