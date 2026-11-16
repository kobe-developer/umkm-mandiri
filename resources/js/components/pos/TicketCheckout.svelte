<script>
    import UserRound from '@lucide/svelte/icons/user-round';
    import ChevronDown from '@lucide/svelte/icons/chevron-down';
    import CashInput from './CashInput.svelte';
    import MoneyInput from '../MoneyInput.svelte';
    import { rupiah } from '../../lib/format.js';

    /** Bagian bawah tiket: pelanggan, diskon, pembayaran, total & tombol simpan. */
    let { form, methods, subtotal, discount, total, canDiscount, itemCount, onsubmit } = $props();

    let showCustomer = $state(false);

    const selectedMethod = $derived(methods.find((m) => m.id === form.payment_method_id));
    const isCash = $derived(selectedMethod?.type === 'tunai');
    const canSubmit = $derived(itemCount > 0 && !form.processing && (form.pay_later || (selectedMethod && (!isCash || (form.amount_received ?? 0) >= total))));
</script>

<div class="space-y-4">
    <div>
        <button type="button" class="flex min-h-10 w-full items-center gap-2 text-sm font-medium text-ink-soft" aria-expanded={showCustomer} onclick={() => (showCustomer = !showCustomer)}>
            <UserRound size={16} class="text-muted" />
            <span class="flex-1 text-left">Pelanggan & catatan <span class="font-normal text-faint">· opsional</span></span>
            <ChevronDown size={16} class="text-muted transition {showCustomer ? 'rotate-180' : ''}" />
        </button>
        {#if showCustomer || form.customer_name || form.whatsapp_number}
            <div class="mt-2 grid animate-fade-in grid-cols-2 gap-2">
                <input class="input" placeholder="Nama pelanggan" maxlength="50" bind:value={form.customer_name} aria-label="Nama pelanggan" />
                <input class="input" placeholder="No. WA (08…)" inputmode="tel" bind:value={form.whatsapp_number} aria-label="Nomor WhatsApp" />
                <input class="input col-span-2" placeholder="Catatan order" maxlength="255" bind:value={form.notes} aria-label="Catatan order" />
            </div>
            {#if form.errors.whatsapp_number}<p class="error">{form.errors.whatsapp_number}</p>{/if}
        {/if}
    </div>

    {#if canDiscount}
        <div class="flex items-center gap-2">
            <select class="input w-[10.5rem] shrink-0" bind:value={form.discount_type} aria-label="Jenis diskon">
                <option value="">Tanpa diskon</option>
                <option value="nominal">Diskon Rp</option>
                <option value="percent">Diskon %</option>
            </select>
            {#if form.discount_type === 'nominal'}
                <div class="flex-1"><MoneyInput bind:value={form.discount_value} aria-label="Nilai diskon" /></div>
            {:else if form.discount_type === 'percent'}
                <input class="input num flex-1" type="number" min="0" max="100" bind:value={form.discount_value} aria-label="Persen diskon" />
            {/if}
        </div>
        {#if form.errors.discount_value}<p class="error">{form.errors.discount_value}</p>{/if}
    {/if}

    <dl class="space-y-1.5 border-t border-dashed border-line-strong pt-4 text-sm">
        <div class="flex justify-between"><dt class="text-muted">Subtotal · {itemCount} item</dt><dd class="num text-ink-soft">{rupiah(subtotal)}</dd></div>
        {#if discount > 0}
            <div class="flex justify-between text-leaf-600"><dt>Diskon</dt><dd class="num">−{rupiah(discount)}</dd></div>
        {/if}
        <div class="flex items-baseline justify-between pt-1"><dt class="font-semibold text-ink">Total</dt><dd class="num text-2xl font-semibold tracking-tight text-ink">{rupiah(total)}</dd></div>
    </dl>

    {#if form.channel === 'dine_in'}
        <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-paper px-4 py-3 text-sm text-ink-soft">
            <input type="checkbox" class="checkbox" bind:checked={form.pay_later} />
            Simpan dulu, bayar nanti (setelah makan)
        </label>
    {/if}

    {#if !form.pay_later}
        <div>
            <p class="label">Metode pembayaran</p>
            <div class="grid grid-cols-3 gap-1.5">
                {#each methods as method (method.id)}
                    <button
                        type="button"
                        class="min-h-11 truncate rounded-xl border px-2 text-sm font-semibold transition {form.payment_method_id === method.id ? 'border-ink bg-ink text-paper' : 'border-line bg-surface text-ink-soft hover:border-line-strong'}"
                        onclick={() => (form.payment_method_id = method.id)}
                    >{method.name}</button>
                {/each}
            </div>
            {#if methods.length === 0}<p class="error">Belum ada metode pembayaran aktif untuk kanal ini.</p>{/if}
            {#if form.errors.payment_method_id}<p class="error">{form.errors.payment_method_id}</p>{/if}
        </div>

        {#if isCash}
            <CashInput bind:value={form.amount_received} {total} error={form.errors.amount_received} />
        {:else if selectedMethod?.type === 'qris' && selectedMethod.qris_image_url}
            <img src={selectedMethod.qris_image_url} alt="QRIS" class="mx-auto max-h-52 rounded-2xl border border-line bg-white p-2" />
        {/if}
    {/if}

    <button type="button" class="btn-accent min-h-13 w-full text-base" disabled={!canSubmit} onclick={onsubmit}>
        {#if form.processing}Menyimpan…{:else if form.pay_later}Simpan order (belum bayar){:else}Simpan & bayar <span class="num">{rupiah(total)}</span>{/if}
    </button>
</div>
