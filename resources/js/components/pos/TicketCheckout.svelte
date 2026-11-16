<script>
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

<div class="space-y-3">
    <button type="button" class="text-sm font-medium text-brand-700" onclick={() => (showCustomer = !showCustomer)}>
        {showCustomer ? '− Sembunyikan' : '+ Nama / WA pelanggan & catatan'} <span class="text-stone-400">(opsional)</span>
    </button>
    {#if showCustomer || form.customer_name || form.whatsapp_number}
        <div class="grid grid-cols-2 gap-2">
            <input class="input" placeholder="Nama pelanggan" maxlength="50" bind:value={form.customer_name} aria-label="Nama pelanggan" />
            <input class="input" placeholder="No. WA (08…)" inputmode="tel" bind:value={form.whatsapp_number} aria-label="Nomor WhatsApp" />
            <input class="input col-span-2" placeholder="Catatan order" maxlength="255" bind:value={form.notes} aria-label="Catatan order" />
        </div>
        {#if form.errors.whatsapp_number}<p class="error">{form.errors.whatsapp_number}</p>{/if}
    {/if}

    {#if canDiscount}
        <div class="flex items-center gap-2">
            <select class="input w-32" bind:value={form.discount_type} aria-label="Jenis diskon">
                <option value="">Tanpa diskon</option>
                <option value="nominal">Diskon Rp</option>
                <option value="percent">Diskon %</option>
            </select>
            {#if form.discount_type === 'nominal'}
                <div class="flex-1"><MoneyInput bind:value={form.discount_value} aria-label="Nilai diskon" /></div>
            {:else if form.discount_type === 'percent'}
                <input class="input flex-1" type="number" min="0" max="100" bind:value={form.discount_value} aria-label="Persen diskon" />
            {/if}
        </div>
        {#if form.errors.discount_value}<p class="error">{form.errors.discount_value}</p>{/if}
    {/if}

    <dl class="space-y-1 border-t border-stone-200 pt-2 text-sm">
        <div class="flex justify-between"><dt class="text-stone-500">Subtotal ({itemCount} item)</dt><dd class="tabular-nums">{rupiah(subtotal)}</dd></div>
        {#if discount > 0}
            <div class="flex justify-between text-emerald-700"><dt>Diskon</dt><dd class="tabular-nums">−{rupiah(discount)}</dd></div>
        {/if}
        <div class="flex justify-between text-lg font-bold"><dt>Total</dt><dd class="tabular-nums">{rupiah(total)}</dd></div>
    </dl>

    {#if form.channel === 'dine_in'}
        <label class="flex items-center gap-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">
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
                        class="rounded-xl border px-2 py-2 text-sm font-semibold {form.payment_method_id === method.id ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-stone-200 bg-white text-stone-700'}"
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
            <img src={selectedMethod.qris_image_url} alt="QRIS" class="mx-auto max-h-48 rounded-xl border border-stone-200" />
        {/if}
    {/if}

    <button type="button" class="btn-primary w-full py-3 text-base" disabled={!canSubmit} onclick={onsubmit}>
        {#if form.processing}Menyimpan…{:else if form.pay_later}Simpan order (belum bayar){:else}Simpan & bayar {rupiah(total)}{/if}
    </button>
</div>
