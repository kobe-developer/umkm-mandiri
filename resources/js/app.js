import '../css/app.css';
import { createInertiaApp } from '@inertiajs/svelte';
import AppLayout from './layouts/AppLayout.svelte';
import { registerServiceWorker } from './lib/push.js';

// Halaman tanpa layout back-office: menu publik, login, struk, halaman error
const STANDALONE = [/^Menu\//, /^Auth\//, /^Error$/, /\/Receipt$/];

createInertiaApp({
    // Judul lengkap ("{halaman} · {nama toko}") disusun oleh components/PageTitle.svelte
    title: (title) => title,
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.svelte');
        const page = pages[`./pages/${name}.svelte`];
        if (!page) {
            throw new Error(`Halaman tidak ditemukan: ${name}`);
        }
        return page();
    },
    layout: (name) => (STANDALONE.some((pattern) => pattern.test(name)) ? undefined : AppLayout),
    progress: { color: '#b93a1a' },
});

// Service worker untuk web push & agar situs bisa dipasang (PWA)
window.addEventListener('load', () => registerServiceWorker());
