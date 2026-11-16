<script>
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

<div class="divide-y divide-stone-100">
    {#each days as day (day)}
        <div class="flex flex-wrap items-center gap-2 py-2">
            <span class="w-20 text-sm font-medium">{DAY_LABELS[day] ?? day}</span>
            <input type="time" class="input w-auto py-1.5" bind:value={hours[day].open} disabled={hours[day].closed} aria-label="Jam buka {DAY_LABELS[day]}" />
            <span class="text-stone-400">–</span>
            <input type="time" class="input w-auto py-1.5" bind:value={hours[day].close} disabled={hours[day].closed} aria-label="Jam tutup {DAY_LABELS[day]}" />
            <label class="flex items-center gap-1.5 text-sm text-stone-600">
                <input type="checkbox" class="checkbox" bind:checked={hours[day].closed} /> Libur
            </label>
            <button type="button" class="btn-ghost btn-sm ml-auto" onclick={() => copyToAll(day)}>Salin ke semua</button>
            {#if errors[`operating_hours.${day}.open`] || errors[`operating_hours.${day}.close`]}
                <p class="error w-full">{errors[`operating_hours.${day}.open`] ?? errors[`operating_hours.${day}.close`]}</p>
            {/if}
        </div>
    {/each}
</div>
<p class="mt-2 text-xs text-stone-500">Jam tutup lebih kecil dari jam buka berarti lewat tengah malam (mis. 16:00–01:00).</p>
