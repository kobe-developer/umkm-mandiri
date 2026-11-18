<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import { router, useForm } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Trash from '@lucide/svelte/icons/trash';
    import BadgePercent from '@lucide/svelte/icons/badge-percent';
    import CalendarRange from '@lucide/svelte/icons/calendar-range';
    import PageHeader from '../../components/PageHeader.svelte';
    import Modal from '../../components/Modal.svelte';
    import EmptyState from '../../components/EmptyState.svelte';
    import Pagination from '../../components/Pagination.svelte';
    import PromotionForm from '../../components/admin/PromotionForm.svelte';
    import { rupiah, date } from '../../lib/format.js';

    let { promotions, products = [], channels = [], filters = {}, today } = $props();

    const STATUS_TABS = [
        { value: 'berlaku', label: 'Berlaku' },
        { value: 'terjadwal', label: 'Terjadwal' },
        { value: 'berakhir', label: 'Berakhir' },
        { value: 'semua', label: 'Semua' },
    ];
    const STATUS_STYLE = {
        berlaku: 'bg-leaf-50 text-leaf-700',
        terjadwal: 'bg-amber-soft text-amber-ink',
        berakhir: 'bg-paper-deep text-muted',
    };
    const STATUS_LABEL = { berlaku: 'Berlaku', terjadwal: 'Terjadwal', berakhir: 'Berakhir' };

    let showForm = $state(false);
    let editing = $state(null);
    let formKey = $state(0);
    let showDelete = $state(false);
    let deleting = $state(null);
    const deleteForm = useForm({});

    const channelLabel = $derived(Object.fromEntries(channels.map((c) => [c.value, c.label])));
    const activeStatus = $derived(filters?.status ?? 'berlaku');

    function setStatus(status) {
        router.get('/admin/promo', { status }, { preserveState: true, preserveScroll: true });
    }

    function open(promotion = null) {
        editing = promotion;
        formKey += 1;
        showForm = true;
    }

    function askDelete(promotion) {
        deleting = promotion;
        showDelete = true;
    }

    function destroy() {
        deleteForm.delete(`/admin/promo/${deleting.id}`, { preserveScroll: true, onFinish: () => (showDelete = false) });
    }

    function discountPercent(promotion) {
        if (!promotion.normal_price || promotion.price >= promotion.normal_price) return null;
        return Math.round(((promotion.normal_price - promotion.price) / promotion.normal_price) * 100);
    }
</script>

<PageTitle title="Promo" />

<PageHeader title="Promo" subtitle="Harga promo per produk untuk event atau produk baru">
    {#snippet actions()}
        <button type="button" class="btn-primary" onclick={() => open()}><Plus size={18} />Tambah promo</button>
    {/snippet}
</PageHeader>

<div class="scroll-x mb-5 gap-2">
    {#each STATUS_TABS as tab (tab.value)}
        <button type="button" class="chip {activeStatus === tab.value ? 'chip-active' : ''}" onclick={() => setStatus(tab.value)}>{tab.label}</button>
    {/each}
</div>

{#if promotions.data.length === 0}
    <EmptyState icon={BadgePercent} title="Belum ada promo" description={activeStatus === 'berlaku' ? 'Tidak ada promo yang berlaku hari ini.' : 'Tidak ada promo pada filter ini.'} />
{:else}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        {#each promotions.data as promotion (promotion.id)}
            {@const percent = discountPercent(promotion)}
            <article class="card flex flex-col p-4 sm:p-5">
                <div class="flex items-start gap-3 {promotion.status === 'berakhir' ? 'opacity-60' : ''}">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-ink">{promotion.product_name ?? 'Produk dihapus'}</p>
                        <p class="mt-1 flex flex-wrap items-baseline gap-x-2">
                            <span class="num text-lg font-semibold text-brand-700">{rupiah(promotion.price)}</span>
                            {#if promotion.normal_price}<span class="num text-sm text-muted line-through">{rupiah(promotion.normal_price)}</span>{/if}
                            {#if percent}<span class="num text-xs font-semibold text-brand-600">−{percent}%</span>{/if}
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {STATUS_STYLE[promotion.status]}">{STATUS_LABEL[promotion.status]}</span>
                </div>

                <div class="flex-1 {promotion.status === 'berakhir' ? 'opacity-60' : ''}">
                    <p class="mt-3 flex items-center gap-1.5 text-sm text-ink-soft">
                        <CalendarRange size={15} class="shrink-0 text-muted" aria-hidden="true" />
                        <span class="num">{date(promotion.active_from)} – {date(promotion.active_until)}</span>
                    </p>
                    {#if promotion.description}<p class="mt-1.5 line-clamp-2 text-sm text-muted">{promotion.description}</p>{/if}
                    {#if promotion.product_deleted}<p class="mt-1.5 text-xs font-semibold text-brand-600">Produk sudah dihapus — promo tidak tampil.</p>{/if}
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        {#each promotion.channels ?? [] as channel (channel)}
                            <span class="rounded-full border border-line px-2.5 py-0.5 text-xs font-medium text-ink-soft">{channelLabel[channel] ?? channel}</span>
                        {/each}
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2 border-t border-line pt-3">
                    <button type="button" class="btn-secondary btn-sm" onclick={() => open(promotion)}><Pencil size={14} />Ubah</button>
                    <button type="button" class="btn-icon -my-1 -mr-2 ml-auto hover:bg-brand-50 hover:text-brand-700" onclick={() => askDelete(promotion)} aria-label="Hapus promo {promotion.product_name}" title="Hapus">
                        <Trash size={16} />
                    </button>
                </div>
            </article>
        {/each}
    </div>

    <div class="mt-6"><Pagination paginator={promotions} /></div>
{/if}

<Modal bind:open={showForm} title={editing ? `Ubah promo ${editing.product_name ?? ''}` : 'Tambah promo'}>
    {#key formKey}
        <PromotionForm promotion={editing} {products} {channels} {today} formId="promotion-form" onsaved={() => (showForm = false)} />
    {/key}
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showForm = false)}>Batal</button>
        <button type="submit" form="promotion-form" class="btn-primary">Simpan</button>
    {/snippet}
</Modal>

<Modal bind:open={showDelete} title="Hapus promo?" size="sm">
    <p class="text-sm text-ink-soft">Promo <strong>{deleting?.product_name}</strong> akan dihapus. Transaksi yang sudah memakai promo ini tetap menyimpan harga promo & harga normalnya. Untuk mengakhiri lebih awal tanpa menghapus, ubah tanggal berakhirnya.</p>
    {#snippet footer()}
        <button type="button" class="btn-secondary" onclick={() => (showDelete = false)}>Batal</button>
        <button type="button" class="btn-accent" disabled={deleteForm.processing} onclick={destroy}>Hapus</button>
    {/snippet}
</Modal>
