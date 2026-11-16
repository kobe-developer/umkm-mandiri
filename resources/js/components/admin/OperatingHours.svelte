<script>
    import Copy from '@lucide/svelte/icons/copy';

    /** Editor jam operasional per hari. hours: { monday: {open, close, closed}, ... } */
    let { hours = $bindable(), days = [], errors = {} } = $props();

    const DAY_LABELS = {
        monday: 'Senin', tuesday: 'Selasa', wednesday: 'Rabu', thursday: 'Kamis',
        friday: 'Jumat', saturday: 'Sabtu', sunday: 'Minggu',
    };

    function copyToAll(day) {
        const source = hours[day];
        hours = Object.fromEntries(days.map((d) => [d, { ...source }]));
    }
</script>

<div class="divide-y divide-line">
    {#each days as day (day)}
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 py-3 first:pt-0 last:pb-0">
            <span class="w-20 text-sm font-medium {hours[day].closed ? 'text-muted' : 'text-ink'}">{DAY_LABELS[day] ?? day}</span>
            <div class="order-last grid w-full grid-cols-[1fr_auto_1fr] items-center gap-2 sm:order-none sm:w-auto sm:max-w-xs sm:flex-1">
                <input type="time" class="input num" bind:value={hours[day].open} disabled={hours[day].closed} aria-label="Jam buka {DAY_LABELS[day]}" />
                <span class="text-faint">–</span>
                <input type="time" class="input num" bind:value={hours[day].close} disabled={hours[day].closed} aria-label="Jam tutup {DAY_LABELS[day]}" />
            </div>
            <div class="ml-auto flex items-center gap-1">
                <label class="flex min-h-11 items-center gap-2 px-2 text-sm text-ink-soft">
                    <input type="checkbox" class="checkbox" bind:checked={hours[day].closed} /> Libur
                </label>
                <button type="button" class="btn-ghost btn-sm" onclick={() => copyToAll(day)}>
                    <Copy size={14} /><span>Salin ke semua</span>
                </button>
            </div>
            {#if errors[`operating_hours.${day}.open`] || errors[`operating_hours.${day}.close`]}
                <p class="error order-last mt-0 w-full">{errors[`operating_hours.${day}.open`] ?? errors[`operating_hours.${day}.close`]}</p>
            {/if}
        </div>
    {/each}
</div>
<p class="hint mt-4">Jam tutup lebih kecil dari jam buka berarti lewat tengah malam (mis. 16:00–01:00).</p>
