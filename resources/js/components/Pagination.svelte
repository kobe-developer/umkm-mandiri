<script>
    import { Link } from '@inertiajs/svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';

    /**
     * Mendukung dua bentuk paginator Laravel:
     * - ResourceCollection: { meta: { links, from, to, total } }
     * - Paginator biasa:   { links, from, to, total }
     */
    let { paginator } = $props();

    const meta = $derived(paginator?.meta ?? paginator ?? {});
    const links = $derived(Array.isArray(meta.links) ? meta.links : []);

    function kind(text) {
        const value = String(text);
        if (value.includes('Previous') || value.includes('&laquo;')) return 'prev';
        if (value.includes('Next') || value.includes('&raquo;')) return 'next';
        return 'page';
    }
</script>

{#snippet content(link)}
    {#if kind(link.label) === 'prev'}<ChevronLeft size={16} /><span class="sr-only">Sebelumnya</span>
    {:else if kind(link.label) === 'next'}<ChevronRight size={16} /><span class="sr-only">Berikutnya</span>
    {:else}{link.label}{/if}
{/snippet}

{#if links.length > 3}
    <nav class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm" aria-label="Halaman">
        <p class="num text-muted">{meta.from ?? 0}–{meta.to ?? 0} dari {meta.total ?? 0}</p>
        <div class="flex flex-wrap items-center gap-1">
            {#each links as link (link.label + link.url)}
                {@const isPage = kind(link.label) === 'page'}
                {#if link.url}
                    <Link
                        href={link.url}
                        preserveScroll
                        class="num flex size-9 items-center justify-center rounded-full font-medium transition {link.active ? 'bg-ink text-paper' : 'text-ink-soft hover:bg-ink/5'} {isPage ? 'hidden sm:flex' : ''} {link.active ? '!flex' : ''}"
                    >{@render content(link)}</Link>
                {:else}
                    <span class="flex size-9 items-center justify-center rounded-full text-faint {isPage ? 'hidden sm:flex' : ''}">{@render content(link)}</span>
                {/if}
            {/each}
        </div>
    </nav>
{/if}
