<script>
    import { Link } from '@inertiajs/svelte';

    /**
     * Mendukung dua bentuk paginator Laravel:
     * - ResourceCollection: { meta: { links, from, to, total } }
     * - Paginator biasa:   { links, from, to, total }
     */
    let { paginator } = $props();

    const meta = $derived(paginator?.meta ?? paginator ?? {});
    const links = $derived(Array.isArray(meta.links) ? meta.links : []);

    function label(text) {
        return String(text).replace('&laquo; Previous', '‹').replace('Next &raquo;', '›').replace('&laquo;', '‹').replace('&raquo;', '›');
    }
</script>

{#if links.length > 3}
    <nav class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm" aria-label="Halaman">
        <p class="text-stone-500">{meta.from ?? 0}–{meta.to ?? 0} dari {meta.total ?? 0}</p>
        <div class="flex flex-wrap gap-1">
            {#each links as link (link.label + link.url)}
                {#if link.url}
                    <Link href={link.url} preserveScroll class="rounded-lg px-3 py-1.5 {link.active ? 'bg-brand-600 text-white' : 'bg-white text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50'}">
                        {label(link.label)}
                    </Link>
                {:else}
                    <span class="rounded-lg px-3 py-1.5 text-stone-400">{label(link.label)}</span>
                {/if}
            {/each}
        </div>
    </nav>
{/if}
