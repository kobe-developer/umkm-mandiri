<script>
    import Copy from '@lucide/svelte/icons/copy';
    import Check from '@lucide/svelte/icons/check';

    /** Instruksi pembayaran: detail rekening (transfer/e-wallet) atau gambar QRIS. */
    let { method } = $props();

    let copied = $state(false);

    async function copyAccount() {
        try {
            await navigator.clipboard.writeText(method.account_number);
            copied = true;
            setTimeout(() => (copied = false), 1800);
        } catch {
            copied = false;
        }
    }
</script>

{#if method}
    {#if method.type === 'transfer' || method.type === 'ewallet'}
        {#if method.account_number}
            <div class="rounded-2xl bg-paper p-4 text-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        {#if method.bank_name}<p class="eyebrow">{method.bank_name}</p>{/if}
                        <p class="num mt-0.5 font-mono text-lg font-semibold tracking-wide text-ink">{method.account_number}</p>
                        {#if method.account_holder}<p class="text-muted">a.n. {method.account_holder}</p>{/if}
                    </div>
                    <button type="button" class="btn-secondary btn-sm shrink-0" onclick={copyAccount}>
                        {#if copied}<Check size={14} />Tersalin{:else}<Copy size={14} />Salin{/if}
                    </button>
                </div>
                <p class="mt-3 border-t border-line pt-3 text-xs text-muted">Transfer setelah pesanan dikonfirmasi kasir, lalu kirim bukti via WhatsApp.</p>
            </div>
        {/if}
    {:else if method.type === 'qris'}
        <div class="rounded-2xl bg-paper p-4 text-center text-sm">
            {#if method.qris_image_url}
                <img src={method.qris_image_url} alt="Kode QRIS {method.name}" class="mx-auto max-h-64 rounded-xl bg-white p-2" loading="lazy" />
            {/if}
            <p class="mt-3 text-xs text-muted">Scan QRIS setelah pesanan dikonfirmasi kasir, lalu kirim bukti via WhatsApp.</p>
        </div>
    {:else if method.type === 'tunai'}
        <p class="rounded-2xl bg-paper px-4 py-3 text-xs text-muted">Bayar tunai saat pesanan diambil / diantar.</p>
    {/if}
{/if}
