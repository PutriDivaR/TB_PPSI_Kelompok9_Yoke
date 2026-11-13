# Waypoint — Integrasi Database Kelompok 9

Dasar kode: cabang `suci`, commit `2d4810f` (frontend Admin), yang sudah memuat kode `main`. Perubahan integrasi berada di cabang lokal `integrasi-database-hasbi` dan belum di-push ke GitHub.

## Menjalankan di Laragon

Gunakan PHP 8.5 sesuai dependensi terkunci repo. PHP 8.3 tidak kompatibel dengan sebagian paket Symfony dalam composer.lock. Nyalakan MySQL melalui Laragon.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm ci --ignore-scripts
npm run build
php artisan yoke:database-check
php artisan serve --host=127.0.0.1 --port=8001
```

Di laptop Hasbi, `.env` sudah disiapkan memakai MySQL `127.0.0.1:3306`, database `yoke_ppsi`, username `root`, password kosong. `.env` tidak masuk Git. Session dan cache memakai file, queue memakai sync, sehingga tidak membutuhkan tabel framework tambahan.

- Pelaporan terintegrasi: http://127.0.0.1:8001/pelaporan
- Status database: http://127.0.0.1:8001/integrasi-database/status
- E-PO kelompok: http://127.0.0.1:8001/distributor
- Portal Admin kelompok: http://127.0.0.1:8001/simulasi/admin

Di laptop ini, `jalankan-web.bat` menjalankan PHP 8.5 Laragon yang sudah terpasang.

## Menyiapkan database pada laptop anggota lain

Buat database MySQL kosong bernama `yoke_ppsi` melalui phpMyAdmin, sesuaikan `.env`, lalu:

```text
php artisan yoke:database-install
```

Installer memakai database yang dipilih dalam `.env` (tidak memaksa nama tertentu), membuat 23 tabel, 31 FK, 18 trigger integritas, dan empat peran. Jika database sudah berisi tabel, installer hanya memeriksa skema dan tidak menimpa data. Skema parsial ditolak agar dapat diperiksa manual. MySQL 8.4+ diperlukan.

Skema yang sama tersedia di `database/schema/yoke_ppsi.sql` untuk impor manual. Metadata ERD ada di `database/schema/schema_erd.json`. Jangan menjalankan `migrate:fresh` pada database yang berisi data. Migrasi bawaan Laravel masih merupakan berkas asli repo; setup ERD menggunakan perintah khusus di atas.

## Yang sudah terhubung

- 23 model Eloquent mengikuti nama tabel, PK, FK, tipe data, dan tanpa created_at/updated_at yang tidak ada pada ERD.
- Model `Pengguna` memakai `kata_sandi` ter-hash; alias `User` mengarah ke model itu untuk persiapan autentikasi. Ini belum menambahkan login aplikasi.
- Modul Pelaporan membaca transaksi MySQL, menyimpan Sell-Out, dan membuat/memperbarui snapshot stok berdasarkan distributor–produk–tanggal.
- Filter tanggal, distributor, tahun fiskal, daftar transaksi, dan ekspor CSV memakai data database.
- Sell-In dihitung dari transaksi pengiriman, bukan disamakan dengan barang diterima. Rekonsiliasi memakai stok layak jual awal + penerimaan baik − Sell-Out setelah snapshot awal. Tanpa saldo awal atau snapshot pada tanggal akhir yang sesuai, sistem menampilkan kebutuhan data, bukan mengasumsikan nol.
- Formulir memakai validasi, CSRF, dan transaksi database. Stok layak jual harus antara nol dan stok fisik.

## Batas integrasi saat ini

E-PO dan Admin tetap mempertahankan UI kelompok beserta data contoh/localStorage asalnya. Monitoring dan klaim belum diimplementasikan di repo ini. Model dan relasinya sudah siap, tetapi controller masing-masing modul masih harus dihubungkan ke model tersebut. Perbaikan rute yang ditemukan pada E-PO sudah disertakan.

Pemilih peran masih simulasi lokal dari repo, bukan autentikasi. Pada Pelaporan, sesi Distributor dibatasi pada perusahaan yang dipilih melalui konteks simulasi; Logistik/SAM/Admin membaca laporan, sedangkan penulisan dilakukan Distributor. Perusahaan simulasi dapat diganti secara eksplisit di halaman. Karena belum ada login, seluruh endpoint Pelaporan dan status database dibatasi ke APP_ENV local/testing; jangan menganggap ini otorisasi produksi.

Database utama tidak diisi transaksi contoh secara otomatis. Bila kosong, isi master distributor dan produk yang sebenarnya melalui SQL/phpMyAdmin atau lanjutkan backend Admin. Form Admin yang masih berupa localStorage belum mengisi master MySQL.

## Pengujian

```text
php artisan test --compact
```

Pengujian default memakai SQLite in-memory yang mengikuti kolom/FK metadata ERD. Untuk menguji jalur MySQL nyata, buat database khusus dengan nama berakhiran `_test`, misalnya `yoke_ppsi_integrasi_test`, lalu set environment proses:

```powershell
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='yoke_ppsi_integrasi_test'
php artisan test --compact tests/Feature/PelaporanDatabaseTest.php
Remove-Item Env:\DB_CONNECTION
Remove-Item Env:\DB_DATABASE
```

Data fixture tiap pengujian dibungkus transaksi dan di-rollback. Test menolak database MySQL yang namanya tidak berakhiran `_test`. Database utama `yoke_ppsi` tidak dipakai untuk pengujian tulis.
