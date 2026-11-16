<script>
    /** Kartu bagian dashboard: judul ringkas + isi. */
    let { title, subtitle = '', class: className = '', children } = $props();
</script>

<section class="card p-4 sm:p-5 {className}">
    <header class="mb-4">
        <h3 class="font-semibold text-ink">{title}</h3>
        {#if subtitle}<p class="mt-0.5 text-xs text-muted">{subtitle}</p>{/if}
    </header>
    {@render children()}
</section>
