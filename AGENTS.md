# Panduan Pengembangan Repo

Dokumen ini mendeskripsikan aturan kerja untuk pengembang yang memelihara repositori Waypoint.

## Prinsip Umum

- Ikuti struktur dan pola Laravel yang sudah ada.
- Gunakan Eloquent untuk query model; hindari SQL mentah kecuali agregasi atau integritas data memang membutuhkan.
- Jangan membuat tabel baru atau kolom baru pada modul E-PO tanpa persetujuan kebutuhan fungsi.
- Pertahankan satuan **karton** pada Forecast dan E-PO.
- Gunakan named route Laravel untuk navigasi.
- Hindari menyimpan URL peran dalam query parameter.

## Modul E-PO

Komponen utama:

- `app/Http/Controllers/EPO/DistributorEPOController.php`
- `app/Http/Controllers/EPO/LogistikEPOController.php`
- `app/Http/Controllers/Api/V1/ForecastController.php`
- `app/Http/Controllers/Api/V1/PurchaseOrderController.php`
- `resources/views/pages/e-po/distributor/`
- `resources/views/pages/e-po/logistik/`

Alur domain:

1. Forecast disimpan pada `forecast` dan `detail_forecast`.
2. Logistik mengubah `jumlah_disetujui` pada detail forecast.
3. E-PO disimpan pada `purchase_order` dan `detail_po`.
4. Monitoring pengiriman menggunakan `pengiriman`, `detail_pengiriman`, dan `penerimaan_barang`.

Konstanta aturan domain sebaiknya dideklarasikan pada Controller terkait:

- Cutoff pengajuan
- Horizon perencanaan
- Lead time minimum dan maksimum
- Minimum order
- Karton per kontainer
- Kapasitas bulanan

## Validasi dan Invarian

- Forecast unik per distributor dan periode.
- Detail Forecast unik per Forecast dan produk.
- Detail PO unik per PO dan produk.
- Jumlah karton tidak boleh negatif atau melebihi sisa kuota approved.
- Draft tidak mengurangi sisa kuota sampai status PO berubah menjadi Diajukan atau seterusnya.
- Pembatalan dan penghapusan hanya boleh dilakukan ketika status masih memungkinkan.
- Semua perubahan multi-tabel harus memakai transaksi database.

## Tampilan

- Layout utama harus merender script stack dari Blade.
- Gunakan Alpine untuk interaksi client-side pada formulir keranjang dan katalog.
- Gunakan data dari Controller untuk mengisi katalog dan keranjang.
- Jangan menjalankan query database langsung dari Blade.
- Pesan sukses, error, dan over-quota harus jelas di halaman yang terkait.

## Database

Database utama memakai ERD MySQL dan tidak boleh diubah dengan `migrate:fresh`.

Perintah:

```powershell
php artisan yoke:database-install
php artisan yoke:database-check
```

Jika menambah skema historis atau integritas, tambahkan perubahan pada:

- `database/schema/yoke_ppsi.sql`
- Metadata pemeriksaan database
- Installer database
- Pengujian terkait

## Pengujian

Gunakan pengujian PHPUnit/Laravel untuk perilaku HTTP:

```powershell
php artisan test --compact
php artisan test --compact tests/Feature/EpoViewTest.php
npm run build
```

Tambahkan pengujian untuk:

- Forecast lama atau di luar horizon yang ditolak
- Sisa kuota
- Minimum order
- Produk asing
- Draft dan pengajuan
- Perbedaan status
- Cutoff dan estimasi jadwal
- Hak pemilik distributor pada PO

## Gaya Perubahan

- Buat perubahan lengkap tetapi terbatas pada kebutuhan.
- Jangan mengubah modul anggota lain kecuali ada integrasi yang diperlukan.
- Jalankan Pint setelah mengubah file PHP.
- Jalankan build Vite setelah mengubah Blade atau aset frontend.
