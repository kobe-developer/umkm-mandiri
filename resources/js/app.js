import '../css/app.css';
import { createInertiaApp } from '@inertiajs/svelte';
import AppLayout from './layouts/AppLayout.svelte';

// Halaman tanpa layout back-office: menu publik, login, struk, halaman error
const STANDALONE = [/^Menu\//, /^Auth\//, /^Error$/, /\/Receipt$/];

createInertiaApp({
    title: (title) => (title ? `${title} · UMKM Mandiri` : 'UMKM Mandiri'),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.svelte');
        const page = pages[`./pages/${name}.svelte`];
        if (!page) {
            throw new Error(`Halaman tidak ditemukan: ${name}`);
        }
        return page();
    },
    layout: (name) => (STANDALONE.some((pattern) => pattern.test(name)) ? undefined : AppLayout),
    progress: { color: '#dc2626' },
});
