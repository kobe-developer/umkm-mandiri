<script>
    import { router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import DatePicker from './DatePicker.svelte';

    /** Filter periode: hari ini, 7 hari, 30 hari, bulan ini, rentang custom. */
    let { period, url, extra = {}, presets = ['today', '7d', '30d', 'month', 'custom'] } = $props();

    const LABELS = { today: 'Hari ini', '7d': '7 hari', '30d': '30 hari', month: 'Bulan ini', custom: 'Custom' };

    // Nilai awal form diambil sekali dari props; setelah itu diatur pengguna
    let from = $state(untrack(() => period.from));
    let to = $state(untrack(() => period.to));
    let showCustom = $state(untrack(() => period.preset === 'custom'));

    function go(preset) {
        if (preset === 'custom') {
            showCustom = true;
            return;
        }
        showCustom = false;
        router.get(url, { ...extra, period: preset }, { preserveState: true, preserveScroll: true });
    }

    function applyCustom(event) {
        event.preventDefault();
        router.get(url, { ...extra, period: 'custom', from, to }, { preserveState: true, preserveScroll: true });
    }
</script>

<div class="flex flex-wrap items-center gap-2">
    <div class="segmented scroll-x max-w-full">
        {#each presets as preset (preset)}
            <button
                type="button"
                class="segment shrink-0 {period.preset === preset || (preset === 'custom' && showCustom) ? 'segment-active' : ''}"
                onclick={() => go(preset)}
            >
                {LABELS[preset]}
            </button>
        {/each}
    </div>
    {#if showCustom}
        <form class="flex flex-wrap items-center gap-2" onsubmit={applyCustom}>
            <DatePicker class="w-44" bind:value={from} max={to} ariaLabel="Dari tanggal" />
            <span class="text-faint">–</span>
            <DatePicker class="w-44" bind:value={to} min={from} ariaLabel="Sampai tanggal" />
            <button class="btn-primary" type="submit">Terapkan</button>
        </form>
    {/if}
</div>
