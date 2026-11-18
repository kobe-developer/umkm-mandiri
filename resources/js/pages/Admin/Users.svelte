<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import { Link, page, useForm } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Pencil from '@lucide/svelte/icons/pencil';
    import KeyRound from '@lucide/svelte/icons/key-round';
    import Check from '@lucide/svelte/icons/check';
    import PageHeader from '../../components/PageHeader.svelte';
    import Modal from '../../components/Modal.svelte';
    import Toggle from '../../components/Toggle.svelte';
    import SearchSelect from '../../components/SearchSelect.svelte';
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

<PageTitle title="User" />

<PageHeader title="User" subtitle="Akun Owner & Kasir">
    {#snippet actions()}
        <button type="button" class="btn-primary" onclick={openCreate}><Plus size={18} />Tambah user</button>
    {/snippet}
</PageHeader>

<p class="mb-5 text-sm text-muted">
    PIN owner untuk menyetujui void/refund diatur dari <Link href="/akun" class="font-medium text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">halaman Akun saya</Link>.
</p>

{#snippet roleBadge(user)}
    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {user.role === 'owner' ? 'bg-ink text-paper' : 'bg-paper-deep text-ink-soft'}">{roleLabel[user.role] ?? user.role}</span>
{/snippet}

{#snippet pinStatus(user)}
    {#if user.role !== 'owner'}<span class="text-faint">-</span>
    {:else if user.has_pin}<span class="inline-flex items-center gap-1 text-leaf-700"><Check size={14} />Sudah</span>
    {:else}<span class="text-amber-ink">Belum</span>{/if}
{/snippet}

{#snippet rowActions(user)}
    <button type="button" class="btn-icon" onclick={() => openEdit(user)} aria-label="Ubah {user.username}" title="Ubah"><Pencil size={17} /></button>
    <button type="button" class="btn-icon" onclick={() => openReset(user)} aria-label="Reset password {user.username}" title="Reset password"><KeyRound size={17} /></button>
{/snippet}

<!-- Mobile & tablet: daftar -->
<ul class="card divide-y divide-line xl:hidden">
    {#each users as user (user.id)}
        <li class="flex items-start gap-3 py-3 pr-2 pl-4">
            <div class="min-w-0 flex-1 {user.is_active ? '' : 'opacity-55'}">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <p class="font-medium text-ink">{user.name}{#if user.id === currentUserId}<span class="ml-1 text-xs font-normal text-muted">(Anda)</span>{/if}</p>
                    {@render roleBadge(user)}
                </div>
                <p class="mt-0.5 truncate text-sm text-muted">{user.username}{#if user.email} · {user.email}{/if}</p>
                <p class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-muted">
                    <span class={user.is_active ? 'text-ink-soft' : ''}>{user.is_active ? 'Aktif' : 'Nonaktif'}</span>
                    {#if user.role === 'owner'}<span aria-hidden="true">·</span><span class="inline-flex items-center gap-1">PIN {@render pinStatus(user)}</span>{/if}
                    <span aria-hidden="true">·</span><span>Login {user.last_login_at ? dateTime(user.last_login_at) : '-'}</span>
                </p>
            </div>
            <div class="flex shrink-0">{@render rowActions(user)}</div>
        </li>
    {/each}
</ul>

<!-- Layar lebar: tabel -->
<div class="card hidden overflow-x-auto xl:block">
    <table class="table">
        <thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>PIN</th><th>Login terakhir</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
            {#each users as user (user.id)}
                <tr class={user.is_active ? '' : 'text-muted'}>
                    <td class="align-middle font-medium {user.is_active ? 'text-ink' : ''}">{user.name}{#if user.id === currentUserId}<span class="ml-1 text-xs font-normal text-muted">(Anda)</span>{/if}</td>
                    <td class="align-middle">{user.username}{#if user.email}<span class="block text-xs text-muted">{user.email}</span>{/if}</td>
                    <td class="align-middle">{@render roleBadge(user)}</td>
                    <td class="align-middle">{user.is_active ? 'Aktif' : 'Nonaktif'}</td>
                    <td class="align-middle">{@render pinStatus(user)}</td>
                    <td class="align-middle whitespace-nowrap">{user.last_login_at ? dateTime(user.last_login_at) : '-'}</td>
                    <td class="py-1.5 text-right whitespace-nowrap">{@render rowActions(user)}</td>
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
                <SearchSelect id="u-role" options={roles} bind:value={form.role} invalid={!!form.errors.role} />
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
    <form id="reset-form" onsubmit={reset}>
        <label class="label" for="r-password">Password baru (min. 8 karakter)</label>
        <input id="r-password" type="password" class="input" bind:value={resetForm.password} minlength="8" autocomplete="new-password" required />
        {#if resetForm.errors.password}<p class="error">{resetForm.errors.password}</p>{/if}
    </form>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showReset = false)}>Batal</button>
        <button type="submit" form="reset-form" class="btn-primary" disabled={resetForm.processing}>Reset</button>
    {/snippet}
</Modal>
