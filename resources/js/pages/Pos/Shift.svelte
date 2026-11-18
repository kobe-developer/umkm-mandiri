<script>
    import PageTitle from '../../components/PageTitle.svelte';
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
        if (value === null || value === undefined) return 'text-muted';
        return value === 0 ? 'text-leaf-600' : value > 0 ? 'text-ink' : 'text-brand-600';
    }
</script>

<PageTitle title="Shift" />

{#snippet line(label, value, emphasis = '')}
    <div class="flex justify-between gap-3 py-2.5 {emphasis}"><dt>{label}</dt><dd class="num">{value}</dd></div>
{/snippet}

<PageHeader title="Shift Kasir" subtitle="Buka shift dengan kas awal, tutup dengan menghitung uang tunai aktual." />

{#if !shift}
    <form class="card max-w-md space-y-5 p-5 sm:p-6" onsubmit={open}>
        <div>
            <h2 class="display text-xl text-ink">Buka shift baru</h2>
            <p class="mt-1 text-sm text-muted">Hitung uang di laci sebelum mulai melayani.</p>
        </div>
        <ErrorSummary errors={openForm.errors} />
        <div>
            <label class="label" for="opening_cash">Kas awal (uang di laci)</label>
            <!-- svelte-ignore a11y_autofocus -->
            <MoneyInput id="opening_cash" bind:value={openForm.opening_cash} autofocus />
        </div>
        <button class="btn-primary min-h-12 w-full" type="submit" disabled={openForm.processing || openForm.opening_cash === null}>Buka shift</button>
    </form>
{:else}
    <div class="grid gap-3 lg:grid-cols-2">
        <section class="rounded-card bg-ink p-5 text-paper sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold">Shift berjalan</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-xs"><span class="size-1.5 animate-pulse rounded-full bg-leaf-100"></span>Aktif</span>
            </div>
            <p class="mt-0.5 text-sm text-paper/60">Dibuka {dateTime(shift.opened_at)}</p>
            <p class="mt-6 text-[13px] text-paper/60">Uang tunai seharusnya</p>
            <p class="num text-4xl font-semibold tracking-tight">{rupiah(shift.expected_cash)}</p>
            <dl class="mt-5 divide-y divide-white/10 border-t border-white/10 text-sm text-paper/80">
                {@render line('Kas awal', rupiah(shift.opening_cash))}
                {@render line('Penjualan tunai', rupiah(shift.cash_sales))}
                {@render line('Penjualan non-tunai', rupiah(shift.non_cash_sales))}
                {@render line('Transaksi lunas', shift.paid_count)}
                {@render line('Order belum dibayar', shift.unpaid_count, shift.unpaid_count > 0 ? 'font-semibold text-amber-soft' : '')}
            </dl>
        </section>

        <form class="card space-y-5 p-5 sm:p-6" onsubmit={close}>
            <h2 class="display text-xl text-ink">Tutup shift</h2>
            <ErrorSummary errors={closeForm.errors} />
            <div>
                <label class="label" for="closing_cash_actual">Uang tunai aktual di laci</label>
                <MoneyInput id="closing_cash_actual" bind:value={closeForm.closing_cash_actual} />
            </div>
            {#if difference !== null}
                <div class="flex items-center justify-between rounded-xl px-4 py-3 text-sm {difference === 0 ? 'bg-leaf-50' : difference < 0 ? 'bg-brand-50' : 'bg-paper'}">
                    <span class="text-muted">Selisih{#if difference === 0}<span class="text-leaf-600"> · kas sesuai</span>{/if}</span>
                    <span class="num text-base font-semibold {diffClass(difference)}">{difference > 0 ? '+' : ''}{rupiah(difference)}</span>
                </div>
            {/if}
            <div>
                <label class="label" for="closing_note">Catatan <span class="font-normal text-faint">· opsional</span></label>
                <input id="closing_note" class="input" maxlength="255" bind:value={closeForm.closing_note} placeholder="Mis. setoran ke owner" />
            </div>
            <button class="btn-primary min-h-12 w-full" type="submit" disabled={closeForm.processing || closeForm.closing_cash_actual === null}>Tutup shift</button>
        </form>
    </div>
{/if}

{#if recent.length > 0}
    <section class="mt-8">
        <h2 class="display mb-3 text-xl text-ink">Shift sebelumnya</h2>
        <!-- Mobile: daftar -->
        <ul class="card divide-y divide-line md:hidden">
            {#each recent as item (item.id)}
                <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                    <div class="min-w-0">
                        <p class="font-medium text-ink">{dateTime(item.opened_at)}</p>
                        <p class="num text-xs text-muted">Aktual {rupiah(item.closing_cash_actual)} · Sistem {rupiah(item.closing_cash_system)}</p>
                    </div>
                    <span class="num shrink-0 font-semibold {diffClass(item.cash_difference)}">{rupiah(item.cash_difference)}</span>
                </li>
            {/each}
        </ul>
        <!-- Desktop: tabel -->
        <div class="card hidden overflow-x-auto md:block">
            <table class="table">
                <thead><tr><th>Dibuka</th><th>Ditutup</th><th class="text-right">Kas awal</th><th class="text-right">Sistem</th><th class="text-right">Aktual</th><th class="text-right">Selisih</th></tr></thead>
                <tbody>
                    {#each recent as item (item.id)}
                        <tr>
                            <td class="whitespace-nowrap">{dateTime(item.opened_at)}</td>
                            <td class="whitespace-nowrap text-muted">{dateTime(item.closed_at)}</td>
                            <td class="num text-right">{rupiah(item.opening_cash)}</td>
                            <td class="num text-right">{rupiah(item.closing_cash_system)}</td>
                            <td class="num text-right">{rupiah(item.closing_cash_actual)}</td>
                            <td class="num text-right font-semibold {diffClass(item.cash_difference)}">{rupiah(item.cash_difference)}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    </section>
{/if}
