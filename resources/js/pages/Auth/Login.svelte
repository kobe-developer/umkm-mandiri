<script>
    import { useForm, page } from '@inertiajs/svelte';

    const form = useForm({ username: '', password: '', remember: false });

    function submit(event) {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    }
</script>

<svelte:head><title>Masuk · UMKM Mandiri</title></svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-brand-600 to-orange-500 px-4">
    <div class="w-full max-w-sm">
        <div class="mb-6 text-center text-white">
            <div class="mx-auto mb-3 flex size-16 items-center justify-center rounded-2xl bg-white text-xl font-black text-brand-600 shadow-lg">UM</div>
            <h1 class="text-2xl font-bold">{page.props.store?.name ?? 'UMKM Mandiri'}</h1>
            <p class="text-sm text-white/80">Masuk untuk Owner & Kasir</p>
        </div>

        <form class="card space-y-4 p-6" onsubmit={submit}>
            <div>
                <label class="label" for="username">Username</label>
                <!-- svelte-ignore a11y_autofocus -->
                <input id="username" class="input" bind:value={form.username} autocomplete="username" autocapitalize="none" autofocus required />
                {#if form.errors.username}<p class="error">{form.errors.username}</p>{/if}
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input id="password" type="password" class="input" bind:value={form.password} autocomplete="current-password" required />
                {#if form.errors.password}<p class="error">{form.errors.password}</p>{/if}
            </div>
            <label class="flex items-center gap-2 text-sm text-stone-600">
                <input type="checkbox" class="checkbox" bind:checked={form.remember} /> Ingat saya
            </label>
            <button class="btn-primary w-full" type="submit" disabled={form.processing}>
                {form.processing ? 'Memproses…' : 'Masuk'}
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-white/90">
            Mau pesan? <a href="/menu" class="font-semibold underline">Lihat menu online</a>
        </p>
    </div>
</div>
