<script>
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

<svelte:head><title>{message[0]}</title></svelte:head>

<div class="flex min-h-screen flex-col items-center justify-center px-4 text-center">
    <p class="text-6xl font-black text-brand-600">{status}</p>
    <h1 class="mt-2 text-xl font-bold">{message[0]}</h1>
    <p class="mt-1 text-stone-500">{message[1]}</p>
    <div class="mt-6 flex gap-2">
        <a href="/menu" class="btn-secondary">Menu online</a>
        <a href="/login" class="btn-primary">Ke aplikasi</a>
    </div>
</div>
