# UMKM Mandiri — POS & Order Online

Aplikasi web untuk mencatat semua penjualan (dine in, take away, online) di satu tempat, plus menu order online yang menggantikan pemesanan via chat WhatsApp. Implementasi **Fase 1 (MVP)** dari [PRD.md](PRD.md).

**Stack:** Laravel 13 (PHP 8.4) · Inertia.js v3 + Svelte 5 · Tailwind CSS 4 · MySQL 8.4 · Laravel Reverb · Docker Compose

## Menjalankan (development)

Butuh Docker saja (PHP/Node lokal tidak diperlukan).

```bash
cp .env.example .env                      # isi REVERB_APP_KEY/SECRET (acak), lalu:
docker compose build
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
docker compose run --rm node sh -c "npm ci && npm run build"   # atau: docker compose --profile dev up node (hot reload)
```

Buka:

| URL | Untuk |
| --- | --- |
| http://localhost:8080/menu | Menu order online (publik) |
| http://localhost:8080/login | Login Owner / Kasir |
| http://localhost:8080/pos | POS kasir |
| http://localhost:8080/admin | Back-office owner |

**Akun awal (ganti segera setelah login):**

| Username | Password | Role | PIN owner |
| --- | --- | --- | --- |
| `owner` | `password` | Owner | `123456` |
| `kasir` | `password` | Kasir | – |

Password awal bisa diatur lewat `SEED_OWNER_PASSWORD`, `SEED_CASHIER_PASSWORD`, `OWNER_DEFAULT_PIN` sebelum `db:seed`. PIN owner diganti di menu **Akun saya**.

## Servis Docker Compose

| Servis | Fungsi |
| --- | --- |
| `app` | PHP-FPM 8.4 (Laravel) |
| `nginx` | Web server + proxy WebSocket `/app` → Reverb |
| `mysql` | MySQL 8.4 (di-tuning untuk VPS RAM 2 GB) |
| `queue` | `queue:work` (broadcast event order) |
| `scheduler` | `schedule:work` — buka/tutup toko otomatis tiap menit, backup DB harian 03:00 |
| `reverb` | WebSocket notifikasi order online baru |
| `node` (profil `dev`) | Vite dev server, hanya untuk development |

Production: `APP_BUILD_TARGET=prod docker compose build` membuat image berisi kode + `vendor` + aset ter-build (`public/build`). Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS di depan nginx, dan `VITE_REVERB_*` sesuai domain publik sebelum build aset. Backup ada di `storage/app/backups` — salin ke penyimpanan di luar VPS.

## Testing

```bash
docker compose exec app php artisan test     # memakai database terpisah: umkm_mandiri_testing
```

Test mencakup: normalisasi nomor WA, harga dihitung ulang di server, anti-spam & rate limit checkout, toko tutup, produk habis, stok (lockForUpdate, rollback, retur saat batal/void), shift & selisih kas, void dengan PIN owner (+ rate limit PIN), omzet hanya Selesai + Lunas, hak akses role, jadwal buka/tutup otomatis, upload & kompresi foto, export Excel/PDF.

## Struktur kode

```
app/Enums/                 Status order, pembayaran, kanal, role, tipe stok/metode bayar
app/Services/              Logika bisnis
  OrderCreationService       buat order POS & online
  OrderLifecycleService      bayar, ubah status, selesai, batal, void, ubah item
  OrderPricer                harga & diskon dihitung ulang dari tabel products
  StockService               stok keluar/retur/penyesuaian (lockForUpdate)
  ShiftService               buka/tutup shift, kas sistem vs aktual
  StoreStatusService         jadwal buka/tutup otomatis + override manual
  ReportService              agregat dashboard & laporan
  OwnerPinVerifier           verifikasi PIN owner untuk void kasir
app/Http/Controllers/{Menu,Pos,Admin}/   3 area route: /menu (publik), /pos (kasir+owner), /admin (owner)
resources/js/pages/{Menu,Pos,Admin}/     Halaman Svelte (Inertia)
resources/js/components/                 Komponen bersama
```

## Aturan bisnis utama

- Order online masuk berstatus **Baru** dan harus dikonfirmasi kasir; order dine in / take away langsung **Diproses**.
- Status pembayaran terpisah: **Belum Bayar → Lunas**, atau **Void** (kasir wajib PIN owner).
- Stok berkurang saat order **Lunas**, kembali saat **Dibatalkan** / **Void**. Order lunas tidak bisa dibatalkan, harus di-void.
- Omzet laporan = order **Selesai + Lunas** saja. Omzet per kategori/produk dihitung dari subtotal item (sebelum diskon order).
- Transaksi (buat order, terima pembayaran) hanya bisa saat shift kasir terbuka.
- Data transaksi tidak pernah dihapus permanen; produk dihapus secara soft delete.
- Notifikasi order baru: WebSocket (Reverb) + polling cadangan tiap 10 detik.

## Keputusan atas pertanyaan terbuka PRD

| Pertanyaan | Keputusan sementara (bisa diubah) |
| --- | --- |
| Nama aplikasi | **UMKM Mandiri**. Prefix nomor order `ORD`, misal `ORD-20261116-0001` (`ORDER_PREFIX` di `.env`). |
| Kasir boleh lihat omzet? | Diatur owner di **Toko → Izin kasir** (default: boleh). |
| Harga "Aneka Minum" | Di-seed nonaktif & disembunyikan dari menu online sampai owner mengisi harga. |
| Minimal order delivery | Pengaturan **Minimal order** di halaman Toko (default Rp0). Batas jarak belum dimodelkan. |
| Pembayaran order online | Pelanggan memilih *rencana* metode bayar (Tunai/COD, QRIS, Transfer yang aktif untuk kanal online); kasir menandai lunas saat dibayar. |
| Printer thermal | Belum; struk tampil di layar dan bisa dicetak dari browser (layout 80 mm). |
| Diskon manual | Default hanya owner; bisa diizinkan untuk kasir di halaman Toko. |
| Add-on/varian | Tidak dibuat (opsional di PRD). Lauk tambahan (Tempe, Extra Sambel, dst.) dijual sebagai produk biasa. |

## Fase 2 (disiapkan)

Nomor WA pelanggan sudah dinormalisasi ke `62…`, tabel `wa_messages` sudah ada, dan event `OrderCreated`/`OrderUpdated` sudah di-broadcast — tinggal menambah listener + queue job ke gateway WA (WAHA).
