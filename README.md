# UMKM Mandiri — POS & Order Online

Aplikasi web untuk mencatat semua penjualan (dine in, take away, online) di satu tempat, plus menu order online yang menggantikan pemesanan via chat WhatsApp. Implementasi **Fase 1 (MVP)** dari [PRD.md](PRD.md).

**Stack:** Laravel 13 (PHP 8.5) · Inertia.js v3 + Svelte 5 · Tailwind CSS 4 · MySQL 8.4 · Laravel Reverb · Docker Compose

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
docker compose exec app php artisan webpush:vapid                 # kunci VAPID untuk web push (sekali saja)
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
| `app` | PHP-FPM 8.5 (Laravel) |
| `nginx` | Web server + proxy WebSocket `/app` → Reverb |
| `mysql` | MySQL 8.4 (di-tuning untuk VPS RAM 2 GB) |
| `queue` | `queue:work` (broadcast event order, kirim web push) |
| `scheduler` | `schedule:work` — buka/tutup toko otomatis tiap menit, backup DB harian 03:00 |
| `reverb` | WebSocket notifikasi order online baru |
| `node` (profil `dev`) | Vite dev server, hanya untuk development |

Production: `APP_BUILD_TARGET=prod docker compose build` membuat image berisi kode + `vendor` + aset ter-build (`public/build`). Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS di depan nginx, dan `TRUSTED_PROXIES` ke IP proxy sebenarnya. `VITE_REVERB_*` boleh dikosongkan (WebSocket ikut origin halaman lewat proxy `/app`).

### CI & deploy otomatis (GitHub Actions)

`.github/workflows/ci.yml` menjalankan Pint, build Vite, dan `php artisan test` (PHP 8.5 + MySQL 8.4 WIB) di setiap PR dan push ke `main`. Setelah test lolos, push ke `main` men-deploy lewat SSH (`make deploy` di server). Bila `main` sudah maju ke commit lain saat giliran deploy tiba, deploy dilewati; commit terbaru di-deploy oleh run-nya sendiri.

Deploy nonaktif sampai disiapkan:

1. Di server, buat key khusus deploy: `ssh-keygen -t ed25519 -f ~/.ssh/gha_deploy -N ""`, tambahkan `gha_deploy.pub` ke `~/.ssh/authorized_keys` user yang menjalankan `make deploy` (user itu harus bisa `git pull` dan menjalankan `docker`).
2. GitHub → Settings → Environments → buat `production` (opsional: *Required reviewers* agar deploy menunggu persetujuan), lalu isi secret:
   - `SSH_HOST`, `SSH_USER`, `SSH_PORT` (opsional, default 22), `DEPLOY_PATH` (folder repo di server)
   - `SSH_PRIVATE_KEY` — isi `~/.ssh/gha_deploy`
   - `SSH_KNOWN_HOSTS` — hasil `ssh-keyscan -p <port> <host>` (dijalankan dari mesin tepercaya)
3. Settings → Secrets and variables → Actions → *Variables*: `DEPLOY_ENABLED` = `true`.

Agar commit yang gagal test tidak bisa masuk `main`, aktifkan branch protection di `main` dengan status check **Test** wajib lolos.

Akses via tunnel HTTPS (mis. `ngrok http 8080`): cukup `TRUSTED_PROXIES` berisi jaringan Docker (default `.env.example`) agar Laravel membaca `X-Forwarded-Proto` dan aset/URL memakai `https://`. Backup ada di `storage/app/backups` — salin ke penyimpanan di luar VPS.

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
  PromotionCatalog           harga promo yang berlaku hari ini per produk & kanal
  PromotionService           tambah/ubah promo (harga < normal, periode tidak bentrok)
  OrderPushNotifier          kapan web push dikirim (order baru → staf, status → pelanggan)
  PushSubscriptionService    langganan push perangkat staf (User) & pelanggan (Order)
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
- Notifikasi order baru: WebSocket (Reverb); polling tiap 10 detik hanya berjalan selama WebSocket tidak tersambung (lalu data dimuat ulang sekali saat tersambung kembali), plus **web push** ke perangkat owner/kasir yang mengaktifkannya (banner di POS atau menu **Akun saya**).
- **Promo** (`/admin/promo`, owner): harga promo per produk, berlaku dari 00:00 tanggal mulai sampai 23:59 tanggal berakhir (WIB), untuk kanal yang dipilih. Harga promo wajib lebih kecil dari harga normal; satu produk tidak boleh punya dua promo dengan periode beririsan (apa pun kanalnya). Harga promo dihitung ulang di server; `order_items` menyimpan `original_price` (harga normal) dan `promotion_id`. Di `/menu` ada tab **Promo** (harga normal dicoret), produk promo tetap tampil di kategorinya.
- **Web push pelanggan**: centang "Kabari saya lewat notifikasi" saat checkout → notifikasi saat Dikonfirmasi, Diproses, Siap/Diantar, Selesai, Dibatalkan, dan pembayaran Lunas. Langganan terikat ke order (tanpa login), maks. 3 perangkat per order, dihapus otomatis 2 hari setelah order final.

- **Branding dinamis**: judul tab ("Halaman · Nama toko"), favicon, ikon PWA, dan ikon notifikasi mengikuti **Toko → Profil toko**. Bila logo diunggah, ikon memakai logo; bila tidak, monogram inisial nama toko (2+ kata → huruf pertama dua kata pertama, mis. *Ayam Penyet Jomblo* → **AP**; 1 kata → huruf pertama + terakhir, mis. *Juciuecy* → **JY**). Ikon digambar server (`BrandIconService`, `/brand/{varian}.png?v=…`) dan di-cache di `storage/app/brand-icons`.

### Web push: syarat & catatan

- Wajib **HTTPS** (atau `http://localhost`). Kunci `VAPID_*` di `.env` tidak boleh diganti setelah dipakai.
- iPhone/iPad (iOS 16.4+): web push hanya jalan bila situs dipasang lewat **Bagikan → Tambahkan ke Layar Utama** lalu dibuka dari layar utama. Manifest PWA: `/manifest.webmanifest` (menu) dan `/manifest-pos.webmanifest` (POS).
- Endpoint langganan hanya diterima dari layanan push browser yang dikenal (`PUSH_ENDPOINT_HOSTS`) untuk mencegah SSRF.
- Pengiriman lewat antrean: servis `queue` harus berjalan.

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
