# Waypoint

Waypoint adalah aplikasi berbasis Laravel untuk pengelolaan data penjualan, distribusi, E-PO, forecast, klaim, monitoring pengiriman, dan pelaporan PT Yoke.

## Gambaran Sistem

Repositori ini merupakan prototipe modul operasional dengan simulasi aktor:

| Peran | Fungsionalitas utama |
| --- | --- |
| Distributor | Forecast, pengajuan E-PO, katalog SKU, status PO, dan dokumen selesai |
| Logistik Pusat | Antrean forecast, penetapan kuota, verifikasi E-PO, serta pratinjau PO |
| SAM | Verifikasi, pengesahan klaim, dan laporan area |
| Admin | Ringkasan, pengguna, distributor, produk, tahun fiskal, dan log aktivitas |

Autentikasi produksi belum diterapkan. Pemilihan aktor pada prototipe menggunakan session simulasi dan dibatasi ke lingkungan lokal/tes pada modul yang masih memakai middleware simulasi.

## Modul E-PO dan Forecast

### Alur bisnis

1. **Distributor membuat Forecast**
   - Sistem menampilkan seluruh SKU aktif beserta rekomendasi histori penjualan.
   - Forecast ditujukan sebagai **target kedatangan stok**, bukan sekadar kebutuhan bulan berjalan.
   - Periode digunakan untuk horizon perencanaan 5 bulan ke depan.
   - Untuk setiap bulan target, distributor mengisi jumlah usulan dalam karton; SKU yang tidak dibutuhkan bernilai 0.

2. **Logistik menetapkan kuota**
   - Forecast berstatus Diajukan masuk antrean Logistik.
   - Logistik dapat menyetujui seluruh usulan sekaligus.
   - Jika kuota dikurangi, Logistik mengisi catatan penyesuaian.
   - Kuota per SKU disimpan pada `detail_forecast.jumlah_disetujui`.

3. **Distributor mengajukan E-PO**
   - Hanya Forecast berstatus Disetujui dan masih memiliki sisa kuota yang dapat dipakai.
   - Distributor mencari SKU, menambahnya ke daftar pesanan, dan mengubah jumlah karton.
   - Katalog menampilkan kode SKU, nama, kategori, harga, kuota, dan sisa kuota.
   - Minimum pesanan adalah 2.000 karton atau 1 kontainer.
   - Pengajuan diblokir jika jumlah melebihi sisa kuota atau total kurang dari minimum order.

4. **Logistik memverifikasi E-PO**
   - Sistem memeriksa minimum order, produk pada Forecast, sisa kuota, status Forecast, serta identitas distributor.
   - Setelah lolos, Logistik menyetujui E-PO.
   - E-PO berubah menjadi PO resmi dan dapat dicetak atau disimpan sebagai PDF melalui fungsi cetak browser.

5. **Riwayat dan status**
   - Distributor dapat melihat riwayat Forecast dan riwayat E-PO pada dashboard.
   - Forecast lama hanya ditampilkan sebagai riwayat dan tidak dapat diubah.
   - E-PO lama tidak dapat dihapus setelah diproses atau disetujui.

### Lead time dan cutoff

- Sistem memakai cutoff pengajuan tanggal 15 setiap bulan.
- Forecast dapat direncanakan hingga 5 bulan ke depan.
- Estimasi awal pengiriman memakai lead time 60–75 hari.
- Setelah data pengiriman dibuat pada modul Monitoring, estimasi awal dapat digantikan oleh `eta_awal` dan `eta_terkini` yang dikelola modul pengiriman.
- Informasi gelombang, cutoff, dan ETA ditampilkan pada:
  - Formulir Input E-PO distributor
  - Verifikasi E-PO logistik
  - Dokumen PO distributor

Catatan: tanggal cutoff, horizon Forecast, minimum order, dan estimasi lead time saat ini berupa konstanta Controller untuk memudahkan penyesuaian. Belum ada kolom konfigurasi tambahan pada database.

### Struktur tabel E-PO

| Tabel | Fungsi |
| --- | --- |
| `forecast` | Induk Forecast dan target periode |
| `detail_forecast` | Usulan dan kuota per produk |
| `purchase_order` | Induk E-PO/PO |
| `detail_po` | Baris produk pada E-PO/PO |
| `pengiriman` | Nomor kontainer, vessel, dan ETA aktual |
| `detail_pengiriman` | Kuantitas produk yang dikirim |
| `penerimaan_barang` | Pencatatan kedatangan |
| `detail_penerimaan` | Kuantitas diterima dan kondisi barang |

E-PO tidak menambah kolom cutoff atau ETA pada tabel. Nilai tersebut dihitung dari tanggal pengajuan dan data pengiriman yang sudah tersedia.

## Struktur Direktori

```text
app/
  Http/Controllers/
    EPO/                 Controller Forecast dan E-PO
    Klaim/               Controller modul klaim
    Api/V1/              API Forecast, PO, Shipment, laporan, dan autentikasi
  Models/                Model Eloquent sesuai ERD
  Services/              Logika rekomendasi dan layanan pelaporan

resources/views/
  layouts/               Layout, header, sidebar, dan notifikasi
  pages/
    e-po/                Tampilan Distributor dan Logistik
    klaim/
    pelaporan/
    admin/

routes/
  web.php                Rute simulasi dan halaman web
  api.php                Rute API

database/
  schema/                ERD SQL dan metadata skema
  factories/             Factory pengujian

tests/
  Feature/               Pengujian fitur HTTP dan database
```

## Persyaratan

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm
- MySQL 8.4 atau kompatibel
- Laravel 13

## Instalasi

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
```

Buat database MySQL kosong, misalnya `yoke_ppsi`, lalu sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` pada `.env`.

```powershell
php artisan yoke:database-install
php artisan serve --host=127.0.0.1 --port=8001
```

Perintah installer membuat dan memeriksa skema ERD tanpa menghapus data pada database yang sudah berisi tabel.

## Alamat Modul

- Dashboard Distributor: `http://127.0.0.1:8001/distributor`
- Antrean Logistik: `http://127.0.0.1:8001/logistik`
- Modul Pelaporan: `http://127.0.0.1:8001/pelaporan`
- Portal Admin: `http://127.0.0.1:8001/simulasi/admin`
- Status Database: `http://127.0.0.1:8001/integrasi-database/status`

## Route Utama E-PO

```text
GET    /e-po/distributor/hitung-forecast
GET    /e-po/distributor/ajukan-forecast
POST   /e-po/distributor/ajukan-forecast
GET    /e-po/distributor/input-epo
POST   /e-po/distributor/input-epo
GET    /e-po/distributor/epo-selesai/{po?}

GET    /e-po/logistik/antrean
GET    /e-po/logistik/forecast/{forecast}
POST   /e-po/logistik/forecast/{forecast}/tetapkan
GET    /e-po/logistik/epo/{po}/verifikasi
POST   /e-po/logistik/epo/{po}/setujui
```

Perpindahan antarperan menggunakan route Laravel, bukan parameter query `?role=...`.

## Pengujian

```powershell
php artisan test --compact
npm run build
```

Pengujian fitur menggunakan SQLite in-memory. Seluruh perubahan database pada pengujian memakai data terisolasi dan tidak menimpa database MySQL utama.

## Catatan Pengembangan

- Jangan menjalankan `migrate:fresh` pada database yang berisi data produksi atau simulasi.
- Gunakan `php artisan yoke:database-check` untuk memeriksa kesesuaian ERD.
- Pertahankan satuan karton pada modul Forecast dan E-PO meskipun nama kolom lama menggunakan `jumlah_kontainer`.
- Satu distributor hanya boleh memiliki satu Forecast aktif untuk kombinasi distributor dan periode.
- Hak akses produksi perlu ditambahkan sebelum aplikasi dipakai secara operasional.
