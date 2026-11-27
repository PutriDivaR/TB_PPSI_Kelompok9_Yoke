# Waypoint

Waypoint adalah aplikasi berbasis Laravel untuk mendukung proses penjualan dan distribusi PT Yoke. Aplikasi ini menggabungkan pengelolaan master data, perencanaan kebutuhan produk, pemesanan, klaim, monitoring distribusi, serta pelaporan dalam satu sistem.

## Modul dan Peran Pengguna

Tabel berikut merangkum modul, pengguna yang terlibat, dan proses yang didukung. Akses ke halaman maupun tindakan di dalam modul mengikuti role pengguna.

| Modul | Pengguna | Fungsi dan alur |
| --- | --- | --- |
| Autentikasi dan Profil | Semua pengguna | Pengguna masuk dan keluar melalui autentikasi session. Sistem membatasi akses berdasarkan role dan status akun; pengguna juga dapat memperbarui profil. |
| Portal Admin | Admin | Melihat ringkasan, mengelola pengguna, distributor, produk, dan tahun fiskal, serta meninjau log aktivitas. |
| Forecast dan E-PO | Distributor, Logistik Pusat | Distributor menyusun dan mengajukan forecast dalam karton. Logistik Pusat memproses forecast dan menetapkan kuota. Distributor mengajukan E-PO dari forecast yang disetujui, lalu Logistik Pusat memverifikasinya dan menyetujui PO. |
| Monitoring Pengiriman | Logistik Pusat, Distributor; akses baca juga tersedia bagi role internal | Logistik Pusat membuat dan memperbarui data pengiriman, ETA, serta milestone. Penerimaan barang dapat dicatat oleh Distributor atau Logistik Pusat. Halaman Monitoring tersedia di web; operasi data pengiriman disediakan melalui API. |
| Klaim | Distributor, SAM, Logistik Pusat | Distributor mengajukan klaim beserta dokumen pendukung. SAM menjadwalkan kunjungan, memverifikasi bukti, dan mengesahkan klaim. Logistik Pusat menindaklanjuti penyelesaian atau pengembalian klaim. |
| Pelaporan | Distributor, Admin, Logistik Pusat, SAM | Distributor mencatat sell-out dan snapshot stok. Pengguna yang berwenang melihat dashboard, ringkasan, tren dan rekonsiliasi stok, menelusuri riwayat, serta mengekspor laporan CSV. |
| Data Referensi | Admin; beberapa referensi dapat dibaca role lain sesuai izin | Menyediakan data pendukung seperti produk, distributor, role, wilayah, dan tahun fiskal untuk digunakan modul-modul aplikasi. |

## Alur Kerja Aplikasi

### Perencanaan hingga penerimaan barang

1. **Distributor mengajukan forecast** kebutuhan produk untuk periode perencanaan. Jumlah forecast menggunakan satuan karton.
2. **Logistik Pusat memproses forecast** dan menetapkan jumlah yang disetujui per produk sebagai kuota.
3. **Distributor membuat E-PO** berdasarkan forecast yang telah disetujui dan kuota yang masih tersedia.
4. **Logistik Pusat memverifikasi dan menyetujui E-PO.** Pesanan yang disetujui menjadi PO untuk proses pengiriman.
5. **Logistik Pusat mengelola pengiriman** yang terkait PO, termasuk informasi ETA dan milestone perjalanan.
6. **Distributor atau Logistik Pusat mencatat penerimaan** barang sebagai bagian dari monitoring pengiriman.

### Penanganan klaim

1. Distributor mengajukan klaim dan melampirkan dokumen atau bukti pendukung.
2. SAM menjadwalkan kunjungan, memeriksa bukti, lalu memverifikasi klaim. Klaim dapat dikembalikan untuk tindak lanjut bila diperlukan.
3. SAM mengesahkan klaim yang lolos verifikasi.
4. Logistik Pusat menindaklanjuti penyelesaian klaim atau mengembalikannya sesuai hasil proses.

### Pencatatan dan pelaporan

Distributor mencatat transaksi sell-out dan snapshot stok. Data tersebut digunakan dalam dashboard dan ringkasan, analisis tren, rekonsiliasi stok, riwayat per periode, serta ekspor CSV. Rekomendasi forecast juga menggunakan data penjualan dan stok.

## Teknologi

- PHP 8.3 atau lebih baru
- Laravel 13
- MySQL 8.4 atau kompatibel
- Node.js dan npm untuk aset frontend (Vite dan Tailwind CSS)

## Instalasi Lokal

Pastikan PHP, Composer, Node.js, npm, dan MySQL tersedia. Jalankan dari root proyek:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
```

Buat database MySQL kosong, misalnya `yoke_ppsi`, lalu sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`. Pasang skema database dan jalankan aplikasi:

```powershell
php artisan yoke:database-install
php artisan serve --host=127.0.0.1 --port=8001
```

Installer memeriksa dan memasang skema ERD tanpa menghapus data dari database yang sudah berisi tabel. Jangan gunakan `migrate:fresh` pada database yang berisi data.

## Halaman Aplikasi

Setelah aplikasi berjalan, buka halaman login lalu masuk menggunakan akun yang tersedia di database:

| Halaman | URL lokal |
| --- | --- |
| Login | `http://127.0.0.1:8001/login` |
| Admin | `http://127.0.0.1:8001/admin/ringkasan` |
| Forecast distributor | `http://127.0.0.1:8001/e-po/distributor/hitung-forecast` |
| Antrean E-PO Logistik | `http://127.0.0.1:8001/e-po/logistik/antrean` |
| Klaim distributor | `http://127.0.0.1:8001/klaim/distributor/beranda` |
| Antrean klaim SAM | `http://127.0.0.1:8001/klaim/sam/antrean` |
| Proses klaim Logistik | `http://127.0.0.1:8001/klaim/logistik/proses` |
| Monitoring pengiriman | `http://127.0.0.1:8001/monitoring` |
| Pelaporan | `http://127.0.0.1:8001/pelaporan` |
| Status database | `http://127.0.0.1:8001/integrasi-database/status` |

Halaman dan tindakan tertentu hanya tersedia untuk role yang berwenang.

## API

API versi 1 berada di bawah prefix `/api/v1` dan memakai session serta CSRF pada origin aplikasi yang sama. Endpoint terproteksi memerlukan autentikasi dan sebagian endpoint membatasi akses berdasarkan role.

| Kelompok endpoint | Cakupan |
| --- | --- |
| `/api/v1/auth` | CSRF, login, informasi sesi, dan logout |
| `/api/v1/reference` dan `/api/v1/admin` | Data referensi dan administrasi |
| `/api/v1/forecasts` dan `/api/v1/purchase-orders` | Forecast dan E-PO |
| `/api/v1/shipments` | Pengiriman, milestone, dan penerimaan |
| `/api/v1/claims` | Pengajuan dan pemrosesan klaim |
| `/api/v1/reports` | Dashboard, ringkasan, riwayat, dan ekspor laporan |

## Struktur Proyek

```text
app/
  Http/Controllers/       Controller web dan API
  Http/Middleware/        Autentikasi dan pemeriksaan role/status pengguna
  Models/                 Model Eloquent
  Services/               Layanan domain dan pelaporan
resources/views/          Layout dan halaman modul
routes/
  web.php                 Route halaman web
  api.php                 Route API versi 1
database/
  schema/                 Skema ERD dan metadata pemeriksaan database
  factories/              Factory pengujian
tests/
  Feature/                Pengujian fitur aplikasi
```

## Pengujian dan Build

```powershell
php artisan test --compact
npm run build
```

Pengujian fitur menggunakan database terisolasi. Untuk memeriksa skema database lokal:

```powershell
php artisan yoke:database-check
```

## Catatan Domain

- Forecast dan E-PO menggunakan satuan **karton**; nama field lama `jumlah_kontainer` pada payload API tertentu juga menyatakan jumlah karton.
- Forecast unik untuk kombinasi distributor dan periode, dan detailnya unik untuk setiap produk.
- Draft E-PO tidak mengurangi kuota sampai statusnya diajukan atau diproses sesuai alur.
- Perubahan yang melibatkan beberapa tabel harus menjaga konsistensi transaksi database.
- Sebelum penggunaan operasional, tinjau konfigurasi deployment, akun, dan kebijakan akses produksi.
