<script>
    import { Link, page, useForm } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import Modal from '../../components/Modal.svelte';
    import Toggle from '../../components/Toggle.svelte';
    import { dateTime } from '../../lib/format.js';

    let { users = [], roles = [] } = $props();

    let showForm = $state(false);
    let editing = $state(null);
    let showReset = $state(false);
    let resetting = $state(null);

    const form = useForm({ name: '', username: '', email: '', role: 'kasir', is_active: true, password: '' });
    const resetForm = useForm({ password: '' });

    const roleLabel = $derived(Object.fromEntries(roles.map((r) => [r.value, r.label])));
    const currentUserId = $derived(page.props.auth?.user?.id);

    function openCreate() {
        editing = null;
        form.clearErrors();
        Object.assign(form, { name: '', username: '', email: '', role: 'kasir', is_active: true, password: '' });
        showForm = true;
    }

    function openEdit(user) {
        editing = user;
        form.clearErrors();
        Object.assign(form, { name: user.name, username: user.username, email: user.email ?? '', role: user.role, is_active: user.is_active, password: '' });
        showForm = true;
    }

    function save(event) {
        event?.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => (showForm = false) };
        if (editing) form.put(`/admin/user/${editing.id}`, options);
        else form.post('/admin/user', options);
    }

    function openReset(user) {
        resetting = user;
        resetForm.clearErrors();
        resetForm.password = '';
        showReset = true;
    }

    function reset(event) {
        event?.preventDefault();
        resetForm.put(`/admin/user/${resetting.id}/password`, { preserveScroll: true, onSuccess: () => (showReset = false) });
    }
</script>

<svelte:head><title>User</title></svelte:head>

<PageHeader title="User" subtitle="Akun Owner & Kasir">
    {#snippet actions()}
        <button type="button" class="btn-primary" onclick={openCreate}>+ Tambah user</button>
    {/snippet}
</PageHeader>

<p class="mb-4 text-sm text-stone-500">
    PIN owner untuk menyetujui void/refund diatur dari <Link href="/akun" class="font-semibold text-brand-700 underline">halaman Akun saya</Link>.
</p>

<div class="card overflow-x-auto">
    <table class="table min-w-[720px]">
        <thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>PIN</th><th>Login terakhir</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
            {#each users as user (user.id)}
                <tr class={user.is_active ? '' : 'opacity-60'}>
                    <td class="font-medium">{user.name}{#if user.id === currentUserId}<span class="ml-1 text-xs text-stone-400">(Anda)</span>{/if}</td>
                    <td>{user.username}{#if user.email}<span class="block text-xs text-stone-500">{user.email}</span>{/if}</td>
                    <td><span class="rounded-full px-2 py-0.5 text-xs font-semibold {user.role === 'owner' ? 'bg-brand-100 text-brand-700' : 'bg-stone-100 text-stone-700'}">{roleLabel[user.role] ?? user.role}</span></td>
                    <td>{user.is_active ? 'Aktif' : 'Nonaktif'}</td>
                    <td>{user.role === 'owner' ? (user.has_pin ? '✓ Sudah' : '— Belum') : '-'}</td>
                    <td class="whitespace-nowrap">{user.last_login_at ? dateTime(user.last_login_at) : '-'}</td>
                    <td class="text-right whitespace-nowrap">
                        <button type="button" class="btn-secondary btn-sm" onclick={() => openEdit(user)}>Ubah</button>
                        <button type="button" class="btn-ghost btn-sm" onclick={() => openReset(user)}>Reset password</button>
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>

<Modal bind:open={showForm} title={editing ? `Ubah ${editing.username}` : 'Tambah user'}>
    <form id="user-form" class="space-y-4" onsubmit={save}>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="u-name">Nama</label>
                <input id="u-name" class="input" bind:value={form.name} maxlength="100" required />
                {#if form.errors.name}<p class="error">{form.errors.name}</p>{/if}
            </div>
            <div>
                <label class="label" for="u-username">Username</label>
                <input id="u-username" class="input" bind:value={form.username} maxlength="50" autocapitalize="none" required />
                {#if form.errors.username}<p class="error">{form.errors.username}</p>{/if}
            </div>
            <div>
                <label class="label" for="u-email">Email (opsional)</label>
                <input id="u-email" type="email" class="input" bind:value={form.email} />
                {#if form.errors.email}<p class="error">{form.errors.email}</p>{/if}
            </div>
            <div>
                <label class="label" for="u-role">Role</label>
                <select id="u-role" class="input" bind:value={form.role}>
                    {#each roles as role (role.value)}<option value={role.value}>{role.label}</option>{/each}
                </select>
                {#if form.errors.role}<p class="error">{form.errors.role}</p>{/if}
            </div>
        </div>
        {#if !editing}
            <div>
                <label class="label" for="u-password">Password awal</label>
                <input id="u-password" type="password" class="input" bind:value={form.password} minlength="8" autocomplete="new-password" required />
                {#if form.errors.password}<p class="error">{form.errors.password}</p>{/if}
            </div>
        {/if}
        <Toggle bind:checked={form.is_active} label="Aktif" description="User nonaktif tidak bisa login dan langsung keluar dari sesinya." />
        {#if form.errors.is_active}<p class="error">{form.errors.is_active}</p>{/if}
    </form>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showForm = false)}>Batal</button>
        <button type="submit" form="user-form" class="btn-primary" disabled={form.processing}>Simpan</button>
    {/snippet}
</Modal>

<Modal bind:open={showReset} title="Reset password {resetting?.username ?? ''}" size="sm">
    <form id="reset-form" class="space-y-3" onsubmit={reset}>
        <label class="label" for="r-password">Password baru (min. 8 karakter)</label>
        <input id="r-password" type="password" class="input" bind:value={resetForm.password} minlength="8" autocomplete="new-password" required />
        {#if resetForm.errors.password}<p class="error">{resetForm.errors.password}</p>{/if}
    </form>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showReset = false)}>Batal</button>
        <button type="submit" form="reset-form" class="btn-primary" disabled={resetForm.processing}>Reset</button>
    {/snippet}
</Modal>
