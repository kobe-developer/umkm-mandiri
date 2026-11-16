<script>
    /** Instruksi pembayaran: detail rekening (transfer/e-wallet) atau gambar QRIS. */
    let { method } = $props();
</script>

{#if method}
    {#if method.type === 'transfer' || method.type === 'ewallet'}
        {#if method.account_number}
            <div class="rounded-xl bg-stone-50 p-3 text-sm">
                {#if method.bank_name}<p class="text-stone-500">{method.bank_name}</p>{/if}
                <p class="font-mono text-base font-semibold tracking-wide">{method.account_number}</p>
                {#if method.account_holder}<p class="text-stone-600">a.n. {method.account_holder}</p>{/if}
                <p class="mt-1 text-xs text-stone-500">Transfer setelah pesanan dikonfirmasi kasir, lalu kirim bukti via WhatsApp.</p>
            </div>
        {/if}
    {:else if method.type === 'qris'}
        <div class="rounded-xl bg-stone-50 p-3 text-center text-sm">
            {#if method.qris_image_url}
                <img src={method.qris_image_url} alt="Kode QRIS {method.name}" class="mx-auto max-h-64 rounded-lg" loading="lazy" />
            {/if}
            <p class="mt-1 text-xs text-stone-500">Scan QRIS setelah pesanan dikonfirmasi kasir, lalu kirim bukti via WhatsApp.</p>
        </div>
    {:else if method.type === 'tunai'}
        <p class="rounded-xl bg-stone-50 p-3 text-xs text-stone-600">Bayar tunai saat pesanan diambil / diantar.</p>
    {/if}
{/if}
