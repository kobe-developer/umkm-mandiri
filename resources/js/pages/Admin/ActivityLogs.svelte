<script>
    import { router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import Pagination from '../../components/Pagination.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import { dateTime } from '../../lib/format.js';

    let { logs, users = [], subjectTypes = [], filters = {} } = $props();

    let userId = $state(untrack(() => (filters?.user_id ? String(filters.user_id) : '')));
    let subjectType = $state(untrack(() => filters?.subject_type ?? ''));
    let expanded = $state({});

    function apply() {
        const params = {};
        if (userId) params.user_id = userId;
        if (subjectType) params.subject_type = subjectType;
        router.get('/admin/log', params, { preserveState: true, preserveScroll: true });
    }

    function toggle(id) {
        expanded = { ...expanded, [id]: !expanded[id] };
    }

    function hasDetail(log) {
        return (log.before && Object.keys(log.before).length > 0) || (log.after && Object.keys(log.after).length > 0);
    }

    function pretty(value) {
        return value ? JSON.stringify(value, null, 2) : '-';
    }
</script>

<svelte:head><title>Log Aktivitas</title></svelte:head>

<PageHeader title="Log Aktivitas" subtitle="Siapa mengubah apa dan kapan" />

<div class="mb-4 flex flex-wrap gap-2">
    <select class="input w-auto" bind:value={userId} onchange={apply} aria-label="User">
        <option value="">Semua user</option>
        {#each users as user (user.id)}<option value={String(user.id)}>{user.name}</option>{/each}
    </select>
    <select class="input w-auto" bind:value={subjectType} onchange={apply} aria-label="Entitas">
        <option value="">Semua entitas</option>
        {#each subjectTypes as type (type)}<option value={type}>{type}</option>{/each}
    </select>
</div>

{#if logs.data.length === 0}
    <EmptyState title="Belum ada aktivitas" />
{:else}
    <div class="card overflow-x-auto">
        <table class="table min-w-[760px]">
            <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Entitas</th><th>Keterangan</th><th></th></tr></thead>
            <tbody>
                {#each logs.data as log (log.id)}
                    <tr>
                        <td class="whitespace-nowrap">{dateTime(log.created_at)}</td>
                        <td>{log.user?.name ?? 'Sistem'}</td>
                        <td><span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-semibold text-stone-700">{log.action}</span></td>
                        <td class="whitespace-nowrap">{log.subject_type}{#if log.subject_id}<span class="text-stone-400"> #{log.subject_id}</span>{/if}</td>
                        <td class="text-stone-700">{log.description ?? '-'}</td>
                        <td class="text-right">
                            {#if hasDetail(log)}
                                <button type="button" class="btn-ghost btn-sm" onclick={() => toggle(log.id)} aria-expanded={!!expanded[log.id]}>
                                    {expanded[log.id] ? 'Tutup' : 'Detail'}
                                </button>
                            {/if}
                        </td>
                    </tr>
                    {#if expanded[log.id]}
                        <tr>
                            <td colspan="6" class="bg-stone-50">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <p class="mb-1 text-xs font-semibold text-stone-500 uppercase">Sebelum</p>
                                        <pre class="overflow-x-auto rounded-lg bg-white p-2 text-xs ring-1 ring-stone-200">{pretty(log.before)}</pre>
                                    </div>
                                    <div>
                                        <p class="mb-1 text-xs font-semibold text-stone-500 uppercase">Sesudah</p>
                                        <pre class="overflow-x-auto rounded-lg bg-white p-2 text-xs ring-1 ring-stone-200">{pretty(log.after)}</pre>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    {/if}
                {/each}
            </tbody>
        </table>
    </div>
    <Pagination paginator={logs} />
{/if}
