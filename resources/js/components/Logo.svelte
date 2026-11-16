<script>
    /** Monogram toko: inisial nama dalam serif di atas lingkaran merah cabai. */
    let { name = '', size = 40, class: className = '' } = $props();

    const initials = $derived(
        String(name || 'UM')
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((word) => word[0])
            .join('')
            .toUpperCase(),
    );
</script>

<span
    class="display inline-flex shrink-0 items-center justify-center rounded-full bg-brand-600 font-semibold text-white {className}"
    style="width:{size}px;height:{size}px;font-size:{Math.round(size * 0.4)}px"
    aria-hidden="true"
>{initials}</span>
