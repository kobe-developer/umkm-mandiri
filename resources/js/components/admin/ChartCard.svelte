<script>
    let { title, subtitle = '', class: className = '', children } = $props();
</script>

<section class="card p-4 {className}">
    <div class="mb-3">
        <h2 class="text-sm font-semibold text-stone-800">{title}</h2>
        {#if subtitle}<p class="text-xs text-stone-500">{subtitle}</p>{/if}
    </div>
    {@render children()}
</section>
