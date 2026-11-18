<script>
    import PageTitle from '../../components/PageTitle.svelte';
    import { useForm, page } from '@inertiajs/svelte';
    import ArrowUpRight from '@lucide/svelte/icons/arrow-up-right';
    import Logo from '../../components/Logo.svelte';

    const form = useForm({ username: '', password: '', remember: false });

    function submit(event) {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    }
</script>

<PageTitle title="Masuk" />

<div class="flex min-h-dvh flex-col bg-paper px-5 pt-[max(2rem,env(safe-area-inset-top))] pb-[max(1.5rem,env(safe-area-inset-bottom))]">
    <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center">
        <div class="mb-10">
            <Logo size={52} />
            <h1 class="display mt-6 text-[2.25rem] leading-[1.1] text-ink">Selamat datang kembali.</h1>
            <p class="mt-2 text-[15px] text-muted">Masuk ke {page.props.store?.name ?? 'UMKM Mandiri'} sebagai owner atau kasir.</p>
        </div>

        <form class="space-y-5" onsubmit={submit}>
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
            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-soft">
                <input type="checkbox" class="checkbox" bind:checked={form.remember} /> Ingat saya di perangkat ini
            </label>
            <button class="btn-primary min-h-12 w-full text-base" type="submit" disabled={form.processing}>
                {form.processing ? 'Memproses…' : 'Masuk'}
            </button>
        </form>
    </div>

    <a href="/menu" class="mx-auto mt-10 inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-ink">
        Mau pesan? Lihat menu online <ArrowUpRight size={15} />
    </a>
</div>
