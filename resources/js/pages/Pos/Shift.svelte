<script>
    import { useForm } from '@inertiajs/svelte';
    import PageHeader from '../../components/PageHeader.svelte';
    import MoneyInput from '../../components/MoneyInput.svelte';
    import ErrorSummary from '../../components/pos/ErrorSummary.svelte';
    import { rupiah, dateTime } from '../../lib/format.js';

    let { shift, recent } = $props();

    const openForm = useForm({ opening_cash: null });
    const closeForm = useForm({ closing_cash_actual: null, closing_note: '' });

    const difference = $derived(shift && closeForm.closing_cash_actual !== null ? closeForm.closing_cash_actual - shift.expected_cash : null);

    function open(event) {
        event.preventDefault();
        openForm.post('/pos/shift');
    }

    function close(event) {
        event.preventDefault();
        if (shift.unpaid_count > 0 && !confirm(`Masih ada ${shift.unpaid_count} order belum dibayar. Tetap tutup shift?`)) return;
        closeForm.put('/pos/shift', { preserveScroll: true, onSuccess: () => closeForm.reset() });
    }

    function diffClass(value) {
        if (value === null || value === undefined) return 'text-stone-500';
        return value === 0 ? 'text-emerald-700' : value > 0 ? 'text-sky-700' : 'text-red-600';
    }
</script>

<svelte:head><title>Shift</title></svelte:head>

<PageHeader title="Shift Kasir" subtitle="Buka shift dengan kas awal, tutup dengan menghitung uang tunai aktual." />

{#if !shift}
    <form class="card max-w-md space-y-4 p-5" onsubmit={open}>
        <ErrorSummary errors={openForm.errors} />
        <div>
            <label class="label" for="opening_cash">Kas awal (uang di laci)</label>
            <!-- svelte-ignore a11y_autofocus -->
            <MoneyInput id="opening_cash" bind:value={openForm.opening_cash} autofocus />
        </div>
        <button class="btn-primary w-full" type="submit" disabled={openForm.processing || openForm.opening_cash === null}>Buka shift</button>
    </form>
{:else}
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="card p-5">
            <h2 class="font-semibold">Shift berjalan</h2>
            <p class="text-sm text-stone-500">Dibuka {dateTime(shift.opened_at)}</p>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt>Kas awal</dt><dd class="tabular-nums">{rupiah(shift.opening_cash)}</dd></div>
                <div class="flex justify-between"><dt>Penjualan tunai</dt><dd class="tabular-nums">{rupiah(shift.cash_sales)}</dd></div>
                <div class="flex justify-between"><dt>Penjualan non-tunai</dt><dd class="tabular-nums">{rupiah(shift.non_cash_sales)}</dd></div>
                <div class="flex justify-between"><dt>Transaksi lunas</dt><dd>{shift.paid_count}</dd></div>
                <div class="flex justify-between {shift.unpaid_count > 0 ? 'text-orange-700' : ''}"><dt>Order belum dibayar</dt><dd>{shift.unpaid_count}</dd></div>
                <div class="flex justify-between border-t border-stone-200 pt-2 text-base font-bold"><dt>Uang tunai seharusnya</dt><dd class="tabular-nums">{rupiah(shift.expected_cash)}</dd></div>
            </dl>
        </section>

        <form class="card space-y-4 p-5" onsubmit={close}>
            <h2 class="font-semibold">Tutup shift</h2>
            <ErrorSummary errors={closeForm.errors} />
            <div>
                <label class="label" for="closing_cash_actual">Uang tunai aktual di laci</label>
                <MoneyInput id="closing_cash_actual" bind:value={closeForm.closing_cash_actual} />
            </div>
            {#if difference !== null}
                <p class="rounded-xl bg-stone-50 px-3 py-2 text-sm">
                    Selisih: <span class="font-bold tabular-nums {diffClass(difference)}">{difference > 0 ? '+' : ''}{rupiah(difference)}</span>
                    {#if difference === 0}<span class="text-emerald-700">· kas sesuai</span>{/if}
                </p>
            {/if}
            <div>
                <label class="label" for="closing_note">Catatan (opsional)</label>
                <input id="closing_note" class="input" maxlength="255" bind:value={closeForm.closing_note} placeholder="Mis. setoran ke owner" />
            </div>
            <button class="btn-primary w-full" type="submit" disabled={closeForm.processing || closeForm.closing_cash_actual === null}>Tutup shift</button>
        </form>
    </div>
{/if}

{#if recent.length > 0}
    <section class="card mt-5 overflow-x-auto">
        <h2 class="px-4 pt-4 font-semibold">Shift sebelumnya</h2>
        <table class="table mt-2">
            <thead><tr><th>Dibuka</th><th>Ditutup</th><th class="text-right">Kas awal</th><th class="text-right">Sistem</th><th class="text-right">Aktual</th><th class="text-right">Selisih</th></tr></thead>
            <tbody>
                {#each recent as item (item.id)}
                    <tr>
                        <td class="whitespace-nowrap">{dateTime(item.opened_at)}</td>
                        <td class="whitespace-nowrap">{dateTime(item.closed_at)}</td>
                        <td class="text-right tabular-nums">{rupiah(item.opening_cash)}</td>
                        <td class="text-right tabular-nums">{rupiah(item.closing_cash_system)}</td>
                        <td class="text-right tabular-nums">{rupiah(item.closing_cash_actual)}</td>
                        <td class="text-right font-semibold tabular-nums {diffClass(item.cash_difference)}">{rupiah(item.cash_difference)}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </section>
{/if}
