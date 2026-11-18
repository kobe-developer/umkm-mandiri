<script>
    import PageTitle from '../components/PageTitle.svelte';
    let { status } = $props();

    const MESSAGES = {
        403: ['Akses ditolak', 'Anda tidak punya akses ke halaman ini.'],
        404: ['Halaman tidak ditemukan', 'Link yang Anda buka tidak ada atau sudah tidak berlaku.'],
        419: ['Sesi kedaluwarsa', 'Silakan muat ulang halaman.'],
        429: ['Terlalu banyak permintaan', 'Tunggu sebentar lalu coba lagi.'],
        500: ['Terjadi kesalahan', 'Maaf, ada masalah di server. Coba lagi beberapa saat.'],
        503: ['Sedang perbaikan', 'Aplikasi sedang dalam perbaikan. Coba lagi nanti.'],
    };

    const message = $derived(MESSAGES[status] ?? MESSAGES[500]);
</script>

<PageTitle title={message[0]} />

<div class="flex min-h-dvh flex-col items-center justify-center bg-paper px-6 text-center">
    <p class="display text-[5.5rem] leading-none text-brand-600">{status}</p>
    <h1 class="display mt-4 text-2xl text-ink">{message[0]}</h1>
    <p class="mt-2 max-w-xs text-muted">{message[1]}</p>
    <div class="mt-8 flex w-full max-w-xs flex-col gap-2 sm:max-w-none sm:flex-row sm:justify-center">
        <a href="/menu" class="btn-secondary">Menu online</a>
        <a href="/login" class="btn-primary">Ke aplikasi</a>
    </div>
</div>
