-- =====================================================================
-- PATCH SKEMA MODUL KLAIM & RETUR  |  Database: yoke_ppsi  |  MySQL 8.x
-- Untuk database yang dipasang sebelum kolom klaim berikut ada di yoke_ppsi.sql.
-- Hanya menambah kolom NULL; tidak menghapus atau mengubah data yang ada.
-- Jalankan sekali:  mysql -u root yoke_ppsi < database/schema/patch_2026_11_klaim.sql
-- Periksa hasilnya:  php artisan yoke:database-check
-- =====================================================================
USE yoke_ppsi;

ALTER TABLE `klaim`
  ADD COLUMN `jadwal_kunjungan` DATE NULL AFTER `tanggal_penyelesaian`,
  ADD COLUMN `catatan_pengembalian` TEXT NULL AFTER `jadwal_kunjungan`,
  ADD COLUMN `bentuk_penyelesaian` VARCHAR(40) NULL AFTER `catatan_pengembalian`,
  ADD COLUMN `catatan_penyelesaian` TEXT NULL AFTER `bentuk_penyelesaian`;

ALTER TABLE `detail_klaim`
  ADD COLUMN `jumlah_terverifikasi` INT NULL AFTER `status_verifikasi`,
  ADD COLUMN `catatan_verifikasi` VARCHAR(255) NULL AFTER `jumlah_terverifikasi`,
  ADD CONSTRAINT `ck_detail_klaim_2` CHECK (jumlah_terverifikasi IS NULL OR (jumlah_terverifikasi >= 0 AND jumlah_terverifikasi <= jumlah));

ALTER TABLE `foto_bukti`
  ADD COLUMN `sumber` VARCHAR(20) NULL AFTER `tanggal_unggah`;
