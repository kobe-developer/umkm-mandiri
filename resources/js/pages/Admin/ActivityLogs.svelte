<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import { router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import ChevronDown from '@lucide/svelte/icons/chevron-down';
    import ScrollText from '@lucide/svelte/icons/scroll-text';
    import PageHeader from '../../components/PageHeader.svelte';
    import Pagination from '../../components/Pagination.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import SearchSelect from '../../components/SearchSelect.svelte';
    import { dateTime } from '../../lib/format.js';

    let { logs, users = [], subjectTypes = [], filters = {} } = $props();

    let userId = $state(untrack(() => (filters?.user_id ? String(filters.user_id) : '')));
    let subjectType = $state(untrack(() => filters?.subject_type ?? ''));
    let expanded = $state({});

    const userOptions = $derived([{ value: '', label: 'Semua user' }, ...users.map((u) => ({ value: String(u.id), label: u.name }))]);
    const subjectOptions = $derived([{ value: '', label: 'Semua entitas' }, ...subjectTypes.map((t) => ({ value: t, label: t }))]);

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

<PageTitle title="Log Aktivitas" />

<PageHeader title="Log Aktivitas" subtitle="Siapa mengubah apa dan kapan" />

<div class="mb-6 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
    <SearchSelect class="sm:w-56" options={userOptions} bind:value={userId} onchange={apply} ariaLabel="User" searchPlaceholder="Cari user…" />
    <SearchSelect class="sm:w-56" options={subjectOptions} bind:value={subjectType} onchange={apply} ariaLabel="Entitas" searchPlaceholder="Cari entitas…" />
</div>

{#snippet detail(log)}
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="min-w-0">
            <p class="mb-1 text-xs font-medium text-muted">Sebelum</p>
            <pre class="overflow-x-auto rounded-xl border border-line bg-surface p-3 text-xs leading-relaxed text-ink-soft">{pretty(log.before)}</pre>
        </div>
        <div class="min-w-0">
            <p class="mb-1 text-xs font-medium text-muted">Sesudah</p>
            <pre class="overflow-x-auto rounded-xl border border-line bg-surface p-3 text-xs leading-relaxed text-ink-soft">{pretty(log.after)}</pre>
        </div>
    </div>
{/snippet}

{#snippet toggleButton(log)}
    <button type="button" class="btn-ghost btn-sm" onclick={() => toggle(log.id)} aria-expanded={!!expanded[log.id]}>
        {expanded[log.id] ? 'Tutup' : 'Detail'}
        <ChevronDown size={14} class="transition-transform {expanded[log.id] ? 'rotate-180' : ''}" />
    </button>
{/snippet}

{#snippet actionBadge(action)}
    <span class="inline-flex rounded-full bg-paper-deep px-2.5 py-0.5 text-xs font-semibold text-ink-soft">{action}</span>
{/snippet}

{#if logs.data.length === 0}
    <EmptyState icon={ScrollText} title="Belum ada aktivitas" />
{:else}
    <!-- Mobile & tablet: linimasa ringkas -->
    <ul class="card divide-y divide-line xl:hidden">
        {#each logs.data as log (log.id)}
            <li class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            {@render actionBadge(log.action)}
                            <span class="text-sm text-ink-soft">{log.subject_type}{#if log.subject_id}<span class="num text-faint"> #{log.subject_id}</span>{/if}</span>
                        </div>
                        {#if log.description}<p class="mt-1.5 text-sm text-ink">{log.description}</p>{/if}
                        <p class="mt-1 text-xs text-muted">{log.user?.name ?? 'Sistem'} · {dateTime(log.created_at)}</p>
                    </div>
                    {#if hasDetail(log)}<div class="-mr-2 shrink-0">{@render toggleButton(log)}</div>{/if}
                </div>
                {#if expanded[log.id]}<div class="mt-3 rounded-xl bg-paper p-3">{@render detail(log)}</div>{/if}
            </li>
        {/each}
    </ul>

    <!-- Layar lebar: tabel -->
    <div class="card hidden overflow-x-auto xl:block">
        <table class="table">
            <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Entitas</th><th>Keterangan</th><th></th></tr></thead>
            <tbody>
                {#each logs.data as log (log.id)}
                    <tr>
                        <td class="whitespace-nowrap text-ink-soft">{dateTime(log.created_at)}</td>
                        <td>{log.user?.name ?? 'Sistem'}</td>
                        <td>{@render actionBadge(log.action)}</td>
                        <td class="whitespace-nowrap">{log.subject_type}{#if log.subject_id}<span class="num text-faint"> #{log.subject_id}</span>{/if}</td>
                        <td class="text-ink-soft">{log.description ?? '-'}</td>
                        <td class="py-1.5 text-right">
                            {#if hasDetail(log)}{@render toggleButton(log)}{/if}
                        </td>
                    </tr>
                    {#if expanded[log.id]}
                        <tr>
                            <td colspan="6" class="bg-paper">{@render detail(log)}</td>
                        </tr>
                    {/if}
                {/each}
            </tbody>
        </table>
    </div>
    <Pagination paginator={logs} />
{/if}
