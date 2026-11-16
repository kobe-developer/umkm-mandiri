# PRD — Ayam Penyet Jomblo

Nov 16, 2026 · @Andri Wijaya

## Ringkasan

Ayam Penyet Jomblo membutuhkan satu aplikasi web untuk mencatat semua penjualan (dine in, take away, online) di satu tempat, plus halaman order online yang menggantikan pemesanan manual lewat WhatsApp.

**Masalah saat ini**

- Order online masuk lewat chat WhatsApp, sehingga tidak tercatat rapi dan sulit direkap menjadi laporan penjualan.
- Penjualan dine in dan take away dicatat terpisah atau manual, sehingga owner tidak punya gambaran omzet harian dan produk terlaris.
- Stok bahan tidak terpantau, sehingga menu bisa habis tanpa diketahui pelanggan online.

**Tujuan**

1. Semua order dari 3 kanal tercatat di satu sistem dengan nomor order unik.
2. Pelanggan bisa memesan sendiri lewat link menu online tanpa chat manual.
3. Owner dan kasir bisa melihat omzet dan tren penjualan kapan saja.
4. Data nama dan nomor WA pelanggan tersimpan, siap untuk integrasi pengiriman struk via WA.

**Metrik sukses (3 bulan setelah rilis)**

| Metrik | Target |
| --- | --- |
| Order online yang masuk lewat menu online (bukan chat) | ≥ 70% |
| Transaksi dine in & take away yang dicatat di aplikasi | 100% |
| Waktu input satu transaksi oleh kasir | < 1 menit |
| Order online dikonfirmasi kasir/owner | < 5 menit sejak masuk |

## Ruang lingkup & fase rilis

Fase 1 (MVP) mencakup back-office Owner & Kasir, POS untuk dine in/take away, dan menu order online; integrasi WA dikerjakan di Fase 2.

| Fase | Isi | Keterangan |
| --- | --- | --- |
| Fase 1 — MVP | Login & role, master data (produk, kategori, stok, metode pembayaran, user, store), POS kasir, menu order online + checkout, konfirmasi order, dashboard & laporan penjualan | Konfirmasi ke pelanggan masih manual: kasir klik tombol "Chat WA" yang membuka wa.me dengan pesan otomatis |
| Fase 2 — Integrasi WA | Kirim struk & notifikasi status order otomatis via WhatsApp Business API / gateway | Butuh nomor WA aktif yang di tautkan ke WAHA (Whatsapp Gateway) |
| Fase 3 — Opsional | Payment gateway (QRIS online), promo/voucher, ongkir & zona delivery, poin pelanggan, multi-cabang | Dipertimbangkan setelah data penjualan 3 bulan |

**Di luar lingkup Fase 1**

- Pembayaran online otomatis di halaman order (pelanggan membayar saat konfirmasi/pengambilan).
- Akun/login untuk pelanggan.
- Manajemen resep/bahan baku per porsi (stok dihitung per produk, bukan per bahan).
- Integrasi ojek online (GoFood, GrabFood, ShopeeFood).
- Printer struk thermal (bisa ditambahkan; struk tampil di layar & bisa dicetak dari browser).

## Role & hak akses

Sistem punya 2 role yang login (Owner, Kasir) dan 1 pengguna publik tanpa login (Pelanggan online).

| Pengguna | Siapa | Kebutuhan utama | Perangkat |
| --- | --- | --- | --- |
| Owner | Pemilik usaha | Mengatur menu, harga, stok, user, buka/tutup toko; memantau omzet & tren | HP / laptop |
| Kasir | Karyawan di counter | Input order cepat, konfirmasi order online, terima pembayaran | HP / tablet |
| Pelanggan online | Mahasiswa sekitar kampus | Lihat menu, pesan, isi nama & WA tanpa ribet | HP |

**Matriks hak akses** (✓ = penuh, L = lihat saja, — = tidak ada akses)

| Fitur | Owner | Kasir |
| --- | --- | --- |
| Manage produk (tambah, ubah, hapus, harga, foto) | ✓ | L |
| Tandai produk habis / tersedia | ✓ | ✓ |
| Manage stok (stok masuk, penyesuaian, riwayat) | ✓ | L |
| Manage kategori | ✓ | — |
| Manage metode pembayaran | ✓ | — |
| Manage user (tambah kasir, reset password, nonaktifkan) | ✓ | — |
| Manage store (buka/tutup, jam operasional, profil toko) | ✓ | — |
| Penjualan / POS (dine in, take away) | ✓ | ✓ |
| Konfirmasi, proses & batalkan order online | ✓ | ✓ |
| Void / refund transaksi yang sudah dibayar | ✓ | — (minta PIN owner) |
| Buka & tutup shift (kas awal, setoran) | ✓ | ✓ |
| Dashboard & tren penjualan | ✓ semua periode | ✓ hari ini & 7 hari terakhir |
| Laporan detail & export (Excel/PDF) | ✓ | — |
| Log aktivitas (siapa ubah apa) | ✓ | — |

Tambahan yang saya usulkan di luar daftar awal: tandai produk habis (agar kasir bisa langsung menyembunyikan menu yang habis dari order online), shift kasir, void dengan PIN owner, export laporan, dan log aktivitas.

## Kebutuhan fungsional — Owner

Owner mengelola semua master data dan melihat seluruh laporan; setiap perubahan tercatat di log aktivitas.

**O1. Manage produk**

- CRUD produk: nama, kategori, harga jual (Rp), foto (opsional), deskripsi singkat, status aktif/nonaktif.
- Flag "Tampil di menu online" per produk (misal "Aneka minum" hanya dijual di tempat).
- Flag "Lacak stok" per produk; jika aktif, produk otomatis tampil "Habis" saat stok = 0.
- Varian/add-on sederhana (opsional): misal Extra Sambel sebagai add-on untuk produk ayam.
- Perubahan harga tidak mengubah harga di transaksi lama (harga disimpan per item transaksi).

**O2. Manage stok**

- Stok per produk (porsi/pcs), bukan per bahan baku.
- Stok masuk (tambah), penyesuaian (koreksi/rusak/basi) dengan alasan wajib diisi.
- Stok otomatis berkurang saat order berstatus Dibayar/Selesai, dan kembali saat order dibatalkan atau di-void.
- Batas stok minimum per produk → notifikasi "stok menipis" di dashboard.
- Riwayat pergerakan stok: tanggal, jenis, jumlah, user, referensi order.

**O3. Manage metode pembayaran**

- CRUD metode: Tunai, QRIS, Transfer Bank (nama bank, no. rekening, atas nama), e-wallet.
- Aktif/nonaktif per metode dan per kanal (misal Tunai hanya untuk dine in/take away dan COD).
- Untuk QRIS: upload gambar QR statis agar bisa ditampilkan ke pelanggan.

**O4. Manage user**

- Tambah, ubah, nonaktifkan akun kasir; reset password.
- Atur PIN owner untuk persetujuan void/refund.
- Akun owner minimal 1 dan tidak bisa dinonaktifkan oleh dirinya sendiri.

**O5. Manage store**

- Tombol Buka/Tutup toko manual; saat Tutup, menu online tetap bisa dilihat tetapi tombol checkout nonaktif dengan pesan "Toko sedang tutup".
- Jadwal jam operasional per hari (buka/tutup otomatis), bisa di-override manual.
- Profil toko: nama, alamat, nomor WA toko, area free delivery ("sekitaran kampus"), teks catatan di struk.
- Pengaturan order online: aktif/nonaktif, minimal order, opsi Ambil Sendiri / Diantar.

**O6. Manage kategori**

- CRUD kategori dengan urutan tampil; default: Makanan, Minuman, Snack.
- Kategori yang masih punya produk tidak bisa dihapus (hanya dinonaktifkan).

**O7. Penjualan & tren**

- Akses POS yang sama dengan kasir (lihat bagian Kasir).
- Dashboard: omzet, jumlah transaksi, rata-rata nilai order, perbandingan dengan periode sebelumnya.
- Filter periode: hari ini, 7 hari, 30 hari, bulan ini, rentang custom.
- Grafik omzet harian, omzet per kanal (dine in / take away / online), per metode pembayaran, per kategori.
- Produk terlaris (qty & omzet), jam ramai (omzet per jam), performa per kasir.
- Daftar transaksi dengan filter & detail; export ke Excel/PDF.
- Laporan tutup shift: kas awal, penjualan tunai, non-tunai, selisih kas.

## Kebutuhan fungsional — Kasir

Kasir fokus pada input order secepat mungkin dan memproses order online yang masuk; kasir tidak bisa mengubah master data kecuali menandai produk habis.

**K1. Shift**

- Buka shift dengan mengisi kas awal; tutup shift dengan mengisi uang tunai aktual, sistem menghitung selisih.
- Transaksi hanya bisa dibuat saat shift terbuka.

**K2. POS (dine in & take away)**

1. Pilih tipe order: Dine In (isi nomor meja, opsional) atau Take Away.
2. Pilih produk dari grid per kategori, dengan pencarian; produk habis tampil abu-abu dan tidak bisa dipilih.
3. Atur qty, catatan per item (misal "sambal dipisah", "level pedas 3").
4. Isi nama pelanggan (opsional) dan nomor WA (opsional, untuk struk via WA di Fase 2).
5. Pilih metode pembayaran; untuk Tunai isi uang diterima → sistem hitung kembalian.
6. Simpan → nomor order terbit, struk tampil di layar dan bisa dicetak.

- Opsi "Simpan dulu, bayar nanti" untuk dine in (bayar setelah makan); order tampil di daftar "Belum dibayar".
- Diskon manual per transaksi (nominal/persen) — opsional, bisa dibatasi hanya owner.

**K3. Order online**

- Notifikasi (suara + badge) saat ada order online baru.
- Daftar order online per status: Baru, Dikonfirmasi, Diproses, Siap/Diantar, Selesai, Dibatalkan.
- Detail order: nama, nomor WA, tipe pengambilan, alamat/patokan (jika diantar), item, catatan, total.
- Tombol "Chat WA" membuka wa.me/{nomor} dengan pesan konfirmasi berisi ringkasan order dan total.
- Kasir bisa mengubah item/qty sebelum konfirmasi (misal ada yang habis), lalu konfirmasi atau tolak dengan alasan.
- Tandai pembayaran (metode + status lunas) saat pelanggan membayar.

**K4. Tren penjualan**

- Dashboard ringkas hari ini dan 7 hari terakhir: jumlah transaksi, omzet, produk terlaris, order per kanal.
- Riwayat transaksi shift berjalan beserta detailnya; cetak ulang struk.

**K5. Ketersediaan produk**

- Toggle Habis/Tersedia per produk, langsung berlaku di menu online.

## Menu order online (pelanggan)

Pelanggan membuka satu link publik (misal dari bio IG atau status WA), memilih menu, lalu checkout dengan nama dan nomor WA — tanpa login.

**Alur**

1. **Halaman menu** — header toko (nama, status Buka/Tutup, info "Free delivery sekitaran kampus"), tab kategori Makanan / Minuman / Snack, kartu produk berisi foto, nama, harga, tombol "+".
2. **Pilih produk & qty** — tap "+" menambah ke cart; stepper "− qty +" muncul di kartu; catatan per item opsional. Produk habis tampil "Habis" dan tidak bisa ditambah.
3. **Cart** — bar melayang di bawah layar menampilkan jumlah item dan subtotal; halaman cart untuk ubah qty, hapus item, dan lihat total.
4. **Checkout** — form berisi:
   - Nama (wajib, 2–50 karakter)
   - Nomor WhatsApp (wajib, format 08xx / 62xx / +62xx, dinormalisasi ke 62xxxxxxxxxx)
   - Tipe pengambilan: Ambil Sendiri atau Diantar (free delivery sekitar kampus)
   - Alamat/patokan (wajib jika Diantar, misal "Kos Melati kamar 5, depan gerbang belakang")
   - Rencana metode bayar (pilihan dari metode yang aktif untuk online, misal COD/Tunai, QRIS, Transfer)
   - Catatan untuk toko (opsional)
5. **Kirim pesanan** — sistem menyimpan order berstatus Baru dan menampilkan halaman sukses: nomor order, ringkasan, dan teks "Pesananmu akan dikonfirmasi via WhatsApp oleh kasir".
6. **Cek status** — halaman status order lewat link unik (nomor order + token) yang menampilkan status terkini.

**Aturan bisnis**

- Checkout ditolak bila toko Tutup, order online dinonaktifkan, atau ada item yang habis saat dikirim (pelanggan diminta memperbarui cart).
- Harga dan total dihitung ulang di server, bukan dari data browser.
- Cart tersimpan di perangkat pelanggan agar tidak hilang saat halaman di-refresh.
- Anti-spam: maksimal 3 order Baru yang belum dikonfirmasi per nomor WA, plus rate limit per IP.
- Nama + nomor WA disimpan sebagai data pelanggan; order berikutnya dengan nomor yang sama otomatis terhubung ke pelanggan tersebut.
- Saat checkout ditampilkan persetujuan singkat bahwa nomor WA dipakai untuk konfirmasi dan pengiriman struk.

## Status & alur order

Order online harus dikonfirmasi kasir/owner sebelum diproses, sedangkan order dine in & take away yang diinput kasir langsung berstatus Diproses.

&#91;embedded content: alur status order · 6 status, 3 kanal\]

Status pembayaran dicatat terpisah dari status order: Belum Bayar → Lunas, atau Void (dengan PIN owner). Order dine in boleh Diproses dengan status Belum Bayar dan dilunasi setelah makan. Stok berkurang saat order Lunas dan kembali saat order Dibatalkan atau di-Void. Hanya order Selesai + Lunas yang dihitung sebagai omzet di laporan.

## Arsitektur & tech stack

Aplikasi dibangun sebagai satu monolith Laravel 13 dengan frontend Svelte lewat Inertia.js, berjalan di Docker Compose; skala ini cukup untuk traffic UMKM tanpa microservice atau API terpisah.

| Lapisan | Pilihan | Catatan |
| --- | --- | --- |
| Backend | Laravel 13, PHP ≥ 8.3 | Laravel 13 wajib PHP 8.3+; bug fix sampai Q3 2027, security fix sampai Q1 2028 ([sumber](https://laravel-news.com/laravel-13-released)) |
| Database | MySQL 8 | Index di `orders` (created\_at, status, channel) untuk laporan |
| Frontend | Svelte 5 via Inertia.js | Routing, auth, validasi tetap di Laravel; pastikan versi adapter Inertia cocok dengan Svelte 5 |
| Styling | Tailwind CSS | Di-build dengan Vite |
| Real-time | Laravel Reverb (websocket) | Alternatif lebih sederhana: polling tiap 10 detik |
| Background job | Queue driver `database` | Redis belum wajib |
| Infrastruktur | Docker Compose | Satu VPS kecil (perkiraan 1–2 vCPU, RAM 2 GB) |

**Pemetaan kebutuhan PRD ke Laravel**

| Kebutuhan | Implementasi |
| --- | --- |
| Login Owner & Kasir, hak akses | Auth bawaan (Fortify/starter kit) + kolom `role` + Gate/Policy & middleware `role:owner` |
| Void dengan PIN owner | Endpoint verifikasi PIN (`Hash::check`) sebelum void diproses |
| Notifikasi order online baru | Event `OrderCreated` di-broadcast lewat Reverb ke halaman kasir |
| Stok berkurang saat lunas | `DB::transaction` + `lockForUpdate()` agar stok tidak minus saat order bersamaan |
| Harga dihitung ulang di server | Form Request + service `OrderService` yang membaca harga dari tabel `products` |
| Anti-spam checkout | `RateLimiter` per IP dan per nomor WA |
| Buka/tutup otomatis, backup harian | Task Scheduler (`schedule:run`) |
| Struk & notifikasi WA (Fase 2) | Queue job + HTTP Client ke gateway WA, status dicatat di `wa_messages` |
| Export Excel/PDF | maatwebsite/excel, barryvdh/laravel-dompdf |
| Log aktivitas | spatie/laravel-activitylog atau tabel `activity_logs` sendiri |
| Grafik tren | Query agregat MySQL, ditampilkan dengan Chart.js di Svelte |
| Foto produk | Laravel Storage + kompresi gambar saat upload |

**Struktur modul (grup route)**

| Area | Prefix | Akses | Isi |
| --- | --- | --- | --- |
| Menu online | `/menu` | Publik | Menu, cart, checkout, status order (`/order/{no}?t={token}`) |
| POS | `/pos` | Kasir, Owner | Shift, input order, order online, riwayat, dashboard ringkas |
| Back-office | `/admin` | Owner | Produk, stok, kategori, pembayaran, user, store, laporan, log |

**Servis Docker Compose**

&#91;embedded content: servis Docker Compose · 6 servis, 1 layanan eksternal\]

Semua servis memakai image `app` yang sama kecuali nginx dan mysql; queue, scheduler, dan reverb hanya berbeda command. Servis `node` (Vite) dipakai saat development saja; di production aset di-build sekali ke `public/build`. Backup harian berupa `mysqldump` dari scheduler, disalin ke penyimpanan di luar VPS.

## Model data

Satu tabel `orders` menampung ketiga kanal agar laporan penjualan cukup membaca satu sumber.

| Entitas | Field utama | Catatan |
| --- | --- | --- |
| users | id, nama, username/email, password\_hash, role (owner/kasir), pin\_hash, is\_active | PIN hanya untuk owner |
| store\_settings | nama\_toko, alamat, no\_wa, is\_open, online\_order\_enabled, min\_order, delivery\_note, jam\_operasional (per hari) | Satu baris (single store) |
| categories | id, nama, urutan, is\_active | Makanan, Minuman, Snack |
| products | id, category\_id, nama, harga, foto\_url, deskripsi, is\_active, show\_online, track\_stock, stok, stok\_minimum, is\_available | is\_available = toggle habis manual |
| stock\_movements | id, product\_id, tipe (masuk/keluar/penyesuaian/retur), qty, alasan, order\_id, user\_id, created\_at | Riwayat stok |
| payment\_methods | id, nama, tipe (tunai/qris/transfer/ewallet), detail (rekening, gambar QR), channels, is\_active |  |
| customers | id, nama, no\_wa (unik, format 62…), total\_order, last\_order\_at | Dasar integrasi WA |
| orders | id, no\_order (misal AGJ-20261116-0012), channel (dine\_in/take\_away/online), status, payment\_status, customer\_id, nama\_pelanggan, no\_wa, no\_meja, fulfillment (pickup/delivery), alamat, catatan, subtotal, diskon, total, payment\_method\_id, uang\_diterima, kembalian, cashier\_id, shift\_id, public\_token, created\_at, confirmed\_at, paid\_at, completed\_at, cancel\_reason | Satu tabel untuk 3 kanal |
| order\_items | id, order\_id, product\_id, nama\_produk, harga\_satuan, qty, catatan, subtotal | Nama & harga disalin saat order dibuat |
| shifts | id, user\_id, opened\_at, closed\_at, kas\_awal, kas\_akhir\_aktual, kas\_akhir\_sistem, selisih |  |
| activity\_logs | id, user\_id, aksi, entitas, entitas\_id, data\_sebelum, data\_sesudah, created\_at | Audit owner |
| wa\_messages (Fase 2) | id, order\_id, no\_wa, tipe (konfirmasi/struk/status), status\_kirim, sent\_at | Disiapkan untuk integrasi WA |

## Data awal produk

Data seed diambil dari foto daftar menu: 30 produk di 3 kategori; harga dalam Rupiah.

| Kategori | Produk | Harga (Rp) |
| --- | --- | --- |
| Makanan | Nasi + Ayam Penyet | 13.000 |
| Makanan | Nasi + Telor | 10.000 |
| Makanan | Nasi + Indomie | 10.000 |
| Makanan | Indomie + Telor | 10.000 |
| Makanan | Ayam Penyet | 10.000 |
| Makanan | Tempe | 2.000 |
| Makanan | Tahu | 2.000 |
| Makanan | Usus | 2.000 |
| Makanan | Ati/Ampela | 2.000 |
| Makanan | Otak-otak | 2.000 |
| Makanan | Nasi Putih | 5.000 |
| Makanan | Extra Sambel | 3.000 |
| Minuman | Es Teh Jumbo | 4.000 |
| Minuman | Teh Hangat | 3.000 |
| Minuman | Es Teajus | 3.000 |
| Minuman | Es Goodday | 5.000 |
| Minuman | Es Jasjus | 3.000 |
| Minuman | Es Nutrisari | 5.000 |
| Minuman | Es Milo | 5.000 |
| Minuman | Mineral Botol | 3.000 |
| Minuman | Aneka Minum | — (harga belum ada) |
| Snack | Keju Aroma Coklat | 10.000 |
| Snack | Keju Aroma Strawberry | 10.000 |
| Snack | Keju Aroma Matcha | 10.000 |
| Snack | Keju Aroma Tiramisu | 12.000 |
| Snack | Keju Aroma C. Crunch | 12.000 |
| Snack | Pisang Crispy Coklat | 10.000 |
| Snack | Pisang Crispy Strawberry | 10.000 |
| Snack | Pisang Crispy Matcha | 10.000 |
| Snack | Pisang Crispy Tiramisu | 12.000 |

Catatan data: foto terpotong di bagian bawah daftar Snack, jadi mungkin ada item lain (misal Pisang Crispy C. Crunch) yang belum tercatat. "Aneka Minum" belum punya harga; usulannya dipecah menjadi produk terpisah atau disembunyikan dari menu online. Tempe, Tahu, Usus, Ati/Ampela, Otak-otak, Nasi Putih, dan Extra Sambel bisa juga ditandai sebagai add-on.

## Non-fungsional, integrasi WA & pertanyaan terbuka

**Kebutuhan non-fungsional**

- Web app responsif, mobile-first; POS nyaman dipakai di HP 6" dan tablet.
- Halaman menu online terbuka < 3 detik di jaringan 4G; foto produk dikompres.
- Notifikasi order baru real-time (WebSocket/polling ≤ 10 detik).
- Keamanan: password di-hash, sesi login kedaluwarsa, akses API dicek per role di server, HTTPS.
- Backup database harian; data transaksi tidak bisa dihapus permanen (hanya void/batal dengan alasan).
- Zona waktu WIB, format Rupiah tanpa desimal (Rp13.000).

**Persiapan integrasi WhatsApp (Fase 2)**

- Nomor WA pelanggan sudah dinormalisasi ke format 62… sejak Fase 1.
- Event yang akan memicu pesan: order online dikonfirmasi, order siap/diantar, order dibayar (kirim struk), order dibatalkan.
- Template struk: nama toko, no. order, tanggal, item × qty × harga, total, metode bayar, link status order.
- Pilihan penyedia: WhatsApp Business Platform (resmi, berbayar per percakapan) atau gateway pihak ketiga; tabel wa\_messages mencatat status kirim.

**Risiko**

- Order palsu/iseng dari menu online → batas order per nomor WA dan wajib konfirmasi kasir sebelum diproses.
- Kasir lupa mencatat transaksi tunai → laporan tutup shift dengan selisih kas.
- Stok tidak akurat karena dicatat per produk → stok opname mingguan lewat penyesuaian stok.

**Pertanyaan terbuka**

- [ ] Nama brand yang dipakai: "Ayam Geprek Jomblo" atau "Ayam Penyet Jomblo" (sesuai menu)?
- [ ] Apakah kasir boleh melihat angka omzet, atau hanya jumlah transaksi & produk terlaris?
- [ ] Daftar lengkap Snack dan harga "Aneka Minum".
- [ ] Apakah ada minimal order untuk delivery dan batas jarak "sekitaran kampus"?
- [ ] Pembayaran order online: bayar di tempat/COD saja, atau juga transfer/QRIS sebelum diproses?
- [ ] Perlu printer struk thermal di Fase 1?
