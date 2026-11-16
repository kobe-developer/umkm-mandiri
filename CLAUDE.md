# UMKM Mandiri — catatan untuk agen

- Spesifikasi produk: `PRD.md`. Ringkasan setup & keputusan: `README.md`.
- Semua tool berjalan di Docker (PHP lokal 8.2 < 8.3 yang dibutuhkan Laravel 13; Node tidak terpasang):
  - PHP/artisan/composer: `docker compose exec app <cmd>`
  - Test: `docker compose exec app php artisan test` (DB `umkm_mandiri_testing`, dipaksa lewat `force="true"` di phpunit.xml — jangan dihapus, atau test akan mengosongkan DB dev)
  - Build aset: `docker run --rm --dns 1.1.1.1 -v "$PWD":/app -w /app node:22-alpine npx vite build`
  - DNS Docker default gagal resolve registry npm / mirror Alpine → pakai `--dns 1.1.1.1` dan base image Debian.
- VM Docker hanya 2 GB: jangan jalankan beberapa build Vite bersamaan (MySQL bisa OOM).
- Logika bisnis ada di `app/Services`; pelanggaran aturan bisnis dilempar via `App\Support\BusinessRule` (tampil sebagai error validasi Inertia).
- Frontend: Svelte 5 runes + Inertia v3 (`useForm` mengembalikan objek reaktif: `form.field`, bukan store `$form`).
- Format: `vendor/bin/pint` untuk PHP. UI berbahasa Indonesia, Rupiah tanpa desimal, zona waktu WIB.
