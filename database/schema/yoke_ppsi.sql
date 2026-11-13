-- Yoke PPSI | ERD final 2026-11-12 | MySQL 8.4+

-- 23 tabel, 31 foreign keys. Tidak menghapus tabel atau data lama.

SET NAMES utf8mb4;



CREATE TABLE `peran` (
  `id_peran` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_peran` VARCHAR(150) NOT NULL,
  `deskripsi` TEXT NULL,
  PRIMARY KEY (`id_peran`)
) ENGINE=InnoDB;

CREATE TABLE `wilayah` (
  `id_wilayah` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_wilayah` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id_wilayah`)
) ENGINE=InnoDB;

CREATE TABLE `produk` (
  `id_produk` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_sku` VARCHAR(60) NOT NULL,
  `nama_produk` VARCHAR(150) NOT NULL,
  `harga` DECIMAL(15,2) NOT NULL,
  `karton_per_kontainer` INT NOT NULL,
  `kategori` VARCHAR(40) NULL,
  PRIMARY KEY (`id_produk`),
  CONSTRAINT `uq_produk_kode_sku` UNIQUE (`kode_sku`),
  CONSTRAINT `ck_produk_1` CHECK (harga >= 0),
  CONSTRAINT `ck_produk_2` CHECK (karton_per_kontainer > 0)
) ENGINE=InnoDB;

CREATE TABLE `tahun_fiskal` (
  `id_tahun_fiskal` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `status` VARCHAR(40) NULL,
  PRIMARY KEY (`id_tahun_fiskal`),
  CONSTRAINT `ck_tahun_fiskal_1` CHECK (tanggal_selesai >= tanggal_mulai)
) ENGINE=InnoDB;

CREATE TABLE `distributor` (
  `id_distributor` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(150) NOT NULL,
  `id_wilayah` BIGINT UNSIGNED NOT NULL,
  `kontak` VARCHAR(60) NOT NULL,
  `alamat_gudang` TEXT NULL,
  `nomor_mou` VARCHAR(60) NULL,
  `status_aktif` BOOLEAN NULL,
  PRIMARY KEY (`id_distributor`),
  CONSTRAINT `fk_distributor_id_wilayah` FOREIGN KEY (`id_wilayah`) REFERENCES `wilayah` (`id_wilayah`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `pengguna` (
  `id_pengguna` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(150) NOT NULL,
  `email` VARCHAR(254) NOT NULL,
  `kata_sandi` VARCHAR(255) NOT NULL,
  `id_peran` BIGINT UNSIGNED NOT NULL,
  `status_aktif` BOOLEAN NULL,
  `id_distributor` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id_pengguna`),
  CONSTRAINT `uq_pengguna_email` UNIQUE (`email`),
  CONSTRAINT `uq_pengguna_id_distributor` UNIQUE (`id_distributor`),
  CONSTRAINT `fk_pengguna_id_peran` FOREIGN KEY (`id_peran`) REFERENCES `peran` (`id_peran`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_pengguna_id_distributor` FOREIGN KEY (`id_distributor`) REFERENCES `distributor` (`id_distributor`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `sell_out` (
  `id_sell_out` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_distributor` BIGINT UNSIGNED NOT NULL,
  `id_produk` BIGINT UNSIGNED NOT NULL,
  `kanal` VARCHAR(40) NOT NULL,
  `jumlah` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  PRIMARY KEY (`id_sell_out`),
  CONSTRAINT `fk_sell_out_id_distributor` FOREIGN KEY (`id_distributor`) REFERENCES `distributor` (`id_distributor`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_sell_out_id_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_sell_out_1` CHECK (jumlah > 0)
) ENGINE=InnoDB;

CREATE TABLE `stok_gudang` (
  `id_stok` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_distributor` BIGINT UNSIGNED NOT NULL,
  `id_produk` BIGINT UNSIGNED NOT NULL,
  `periode` DATE NOT NULL,
  `stok_fisik` INT NOT NULL,
  `stok_layak_jual` INT NOT NULL,
  PRIMARY KEY (`id_stok`),
  CONSTRAINT `uq_stok_gudang_kombinasi` UNIQUE (`id_distributor`, `id_produk`, `periode`),
  CONSTRAINT `fk_stok_gudang_id_distributor` FOREIGN KEY (`id_distributor`) REFERENCES `distributor` (`id_distributor`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_stok_gudang_id_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_stok_gudang_1` CHECK (stok_fisik >= 0),
  CONSTRAINT `ck_stok_gudang_2` CHECK (stok_layak_jual >= 0 AND stok_layak_jual <= stok_fisik)
) ENGINE=InnoDB;

CREATE TABLE `log_aktivitas` (
  `id_log` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pengguna` BIGINT UNSIGNED NOT NULL,
  `modul` VARCHAR(60) NOT NULL,
  `aksi` VARCHAR(60) NOT NULL,
  `waktu` DATETIME NOT NULL,
  PRIMARY KEY (`id_log`),
  CONSTRAINT `fk_log_aktivitas_id_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `forecast` (
  `id_forecast` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_distributor` BIGINT UNSIGNED NOT NULL,
  `id_tahun_fiskal` BIGINT UNSIGNED NOT NULL,
  `periode` DATE NOT NULL,
  `tanggal_pengajuan` DATETIME NOT NULL,
  `status` VARCHAR(40) NOT NULL,
  `tanggal_disetujui` DATETIME NULL,
  `id_penyetuju` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id_forecast`),
  CONSTRAINT `uq_forecast_kombinasi` UNIQUE (`id_distributor`, `periode`),
  CONSTRAINT `fk_forecast_id_distributor` FOREIGN KEY (`id_distributor`) REFERENCES `distributor` (`id_distributor`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_forecast_id_tahun_fiskal` FOREIGN KEY (`id_tahun_fiskal`) REFERENCES `tahun_fiskal` (`id_tahun_fiskal`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_forecast_id_penyetuju` FOREIGN KEY (`id_penyetuju`) REFERENCES `pengguna` (`id_pengguna`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_forecast_1` CHECK (DAYOFMONTH(periode) = 1),
  CONSTRAINT `ck_forecast_2` CHECK ((tanggal_disetujui IS NULL) = (id_penyetuju IS NULL))
) ENGINE=InnoDB;

CREATE TABLE `detail_forecast` (
  `id_detail_forecast` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_forecast` BIGINT UNSIGNED NOT NULL,
  `id_produk` BIGINT UNSIGNED NOT NULL,
  `jumlah_kontainer` INT NOT NULL,
  `jumlah_disetujui` INT NULL,
  PRIMARY KEY (`id_detail_forecast`),
  CONSTRAINT `uq_detail_forecast_kombinasi` UNIQUE (`id_forecast`, `id_produk`),
  CONSTRAINT `fk_detail_forecast_id_forecast` FOREIGN KEY (`id_forecast`) REFERENCES `forecast` (`id_forecast`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_detail_forecast_id_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_detail_forecast_1` CHECK (jumlah_kontainer > 0),
  CONSTRAINT `ck_detail_forecast_2` CHECK (jumlah_disetujui >= 0)
) ENGINE=InnoDB;

CREATE TABLE `purchase_order` (
  `id_po` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nomor_po` VARCHAR(60) NOT NULL,
  `id_forecast` BIGINT UNSIGNED NOT NULL,
  `tanggal_pengajuan` DATETIME NOT NULL,
  `status` VARCHAR(40) NOT NULL,
  `tanggal_disetujui` DATETIME NULL,
  `id_penyetuju` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id_po`),
  CONSTRAINT `uq_purchase_order_nomor_po` UNIQUE (`nomor_po`),
  CONSTRAINT `fk_purchase_order_id_forecast` FOREIGN KEY (`id_forecast`) REFERENCES `forecast` (`id_forecast`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_purchase_order_id_penyetuju` FOREIGN KEY (`id_penyetuju`) REFERENCES `pengguna` (`id_pengguna`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_purchase_order_1` CHECK ((tanggal_disetujui IS NULL) = (id_penyetuju IS NULL))
) ENGINE=InnoDB;

CREATE TABLE `detail_po` (
  `id_detail_po` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_po` BIGINT UNSIGNED NOT NULL,
  `id_produk` BIGINT UNSIGNED NOT NULL,
  `jumlah_kontainer` INT NOT NULL,
  PRIMARY KEY (`id_detail_po`),
  CONSTRAINT `uq_detail_po_kombinasi` UNIQUE (`id_po`, `id_produk`),
  CONSTRAINT `fk_detail_po_id_po` FOREIGN KEY (`id_po`) REFERENCES `purchase_order` (`id_po`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_detail_po_id_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_detail_po_1` CHECK (jumlah_kontainer > 0)
) ENGINE=InnoDB;

CREATE TABLE `pengiriman` (
  `id_pengiriman` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_po` BIGINT UNSIGNED NOT NULL,
  `nomor_kontainer` VARCHAR(60) NOT NULL,
  `nama_vessel` VARCHAR(150) NULL,
  `eta_awal` DATE NOT NULL,
  `eta_terkini` DATE NOT NULL,
  `status` VARCHAR(40) NOT NULL,
  PRIMARY KEY (`id_pengiriman`),
  CONSTRAINT `fk_pengiriman_id_po` FOREIGN KEY (`id_po`) REFERENCES `purchase_order` (`id_po`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `milestone_pengiriman` (
  `id_milestone` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pengiriman` BIGINT UNSIGNED NOT NULL,
  `urutan` INT NOT NULL,
  `nama_tahap` VARCHAR(150) NOT NULL,
  `tanggal_pembaruan` DATETIME NOT NULL,
  `keterangan` TEXT NULL,
  PRIMARY KEY (`id_milestone`),
  CONSTRAINT `fk_milestone_pengiriman_id_pengiriman` FOREIGN KEY (`id_pengiriman`) REFERENCES `pengiriman` (`id_pengiriman`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_milestone_pengiriman_1` CHECK (urutan > 0)
) ENGINE=InnoDB;

CREATE TABLE `penerimaan_barang` (
  `id_penerimaan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pengiriman` BIGINT UNSIGNED NOT NULL,
  `tanggal_terima` DATETIME NOT NULL,
  PRIMARY KEY (`id_penerimaan`),
  CONSTRAINT `uq_penerimaan_barang_id_pengiriman` UNIQUE (`id_pengiriman`),
  CONSTRAINT `fk_penerimaan_barang_id_pengiriman` FOREIGN KEY (`id_pengiriman`) REFERENCES `pengiriman` (`id_pengiriman`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `detail_pengiriman` (
  `id_detail_pengiriman` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pengiriman` BIGINT UNSIGNED NOT NULL,
  `id_detail_po` BIGINT UNSIGNED NOT NULL,
  `jumlah_dikirim` INT NOT NULL,
  PRIMARY KEY (`id_detail_pengiriman`),
  CONSTRAINT `uq_detail_pengiriman_kombinasi` UNIQUE (`id_pengiriman`, `id_detail_po`),
  CONSTRAINT `fk_detail_pengiriman_id_pengiriman` FOREIGN KEY (`id_pengiriman`) REFERENCES `pengiriman` (`id_pengiriman`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_detail_pengiriman_id_detail_po` FOREIGN KEY (`id_detail_po`) REFERENCES `detail_po` (`id_detail_po`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_detail_pengiriman_1` CHECK (jumlah_dikirim > 0)
) ENGINE=InnoDB;

CREATE TABLE `klaim` (
  `id_klaim` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_penerimaan` BIGINT UNSIGNED NOT NULL,
  `tanggal_pengajuan` DATETIME NOT NULL,
  `status` VARCHAR(40) NOT NULL,
  `tanggal_penyelesaian` DATETIME NULL,
  PRIMARY KEY (`id_klaim`),
  CONSTRAINT `uq_klaim_id_penerimaan` UNIQUE (`id_penerimaan`),
  CONSTRAINT `fk_klaim_id_penerimaan` FOREIGN KEY (`id_penerimaan`) REFERENCES `penerimaan_barang` (`id_penerimaan`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `sell_in` (
  `id_sell_in` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_detail_pengiriman` BIGINT UNSIGNED NOT NULL,
  `nomor_invoice` VARCHAR(60) NULL,
  `nomor_do` VARCHAR(60) NULL,
  `jumlah` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  PRIMARY KEY (`id_sell_in`),
  CONSTRAINT `fk_sell_in_id_detail_pengiriman` FOREIGN KEY (`id_detail_pengiriman`) REFERENCES `detail_pengiriman` (`id_detail_pengiriman`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_sell_in_1` CHECK (jumlah > 0)
) ENGINE=InnoDB;

CREATE TABLE `detail_penerimaan` (
  `id_detail_penerimaan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_penerimaan` BIGINT UNSIGNED NOT NULL,
  `id_detail_pengiriman` BIGINT UNSIGNED NOT NULL,
  `jumlah_diterima` INT NOT NULL,
  `jumlah_baik` INT NOT NULL,
  `kondisi` VARCHAR(40) NOT NULL,
  PRIMARY KEY (`id_detail_penerimaan`),
  CONSTRAINT `uq_detail_penerimaan_kombinasi` UNIQUE (`id_penerimaan`, `id_detail_pengiriman`),
  CONSTRAINT `fk_detail_penerimaan_id_penerimaan` FOREIGN KEY (`id_penerimaan`) REFERENCES `penerimaan_barang` (`id_penerimaan`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_detail_penerimaan_id_detail_pengiriman` FOREIGN KEY (`id_detail_pengiriman`) REFERENCES `detail_pengiriman` (`id_detail_pengiriman`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_detail_penerimaan_1` CHECK (jumlah_diterima >= 0),
  CONSTRAINT `ck_detail_penerimaan_2` CHECK (jumlah_baik >= 0 AND jumlah_baik <= jumlah_diterima)
) ENGINE=InnoDB;

CREATE TABLE `berita_acara_kerusakan` (
  `id_bak` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_klaim` BIGINT UNSIGNED NOT NULL,
  `nomor_bak` VARCHAR(60) NOT NULL,
  `id_pengesah` BIGINT UNSIGNED NOT NULL,
  `tanggal_pengesahan` DATETIME NOT NULL,
  `tanda_tangan_digital` VARCHAR(255) NOT NULL,
  `status` VARCHAR(40) NOT NULL,
  PRIMARY KEY (`id_bak`),
  CONSTRAINT `uq_berita_acara_kerusakan_id_klaim` UNIQUE (`id_klaim`),
  CONSTRAINT `uq_berita_acara_kerusakan_nomor_bak` UNIQUE (`nomor_bak`),
  CONSTRAINT `fk_berita_acara_kerusakan_id_klaim` FOREIGN KEY (`id_klaim`) REFERENCES `klaim` (`id_klaim`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_berita_acara_kerusakan_id_pengesah` FOREIGN KEY (`id_pengesah`) REFERENCES `pengguna` (`id_pengguna`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `detail_klaim` (
  `id_detail_klaim` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_klaim` BIGINT UNSIGNED NOT NULL,
  `id_detail_penerimaan` BIGINT UNSIGNED NOT NULL,
  `jenis_kerusakan` VARCHAR(40) NOT NULL,
  `jumlah` INT NOT NULL,
  `penanggung_jawab` VARCHAR(40) NOT NULL,
  `status_verifikasi` VARCHAR(40) NOT NULL,
  PRIMARY KEY (`id_detail_klaim`),
  CONSTRAINT `fk_detail_klaim_id_klaim` FOREIGN KEY (`id_klaim`) REFERENCES `klaim` (`id_klaim`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `fk_detail_klaim_id_detail_penerimaan` FOREIGN KEY (`id_detail_penerimaan`) REFERENCES `detail_penerimaan` (`id_detail_penerimaan`) ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT `ck_detail_klaim_1` CHECK (jumlah > 0)
) ENGINE=InnoDB;

CREATE TABLE `foto_bukti` (
  `id_foto` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_detail_klaim` BIGINT UNSIGNED NOT NULL,
  `path_berkas` VARCHAR(255) NOT NULL,
  `tanggal_unggah` DATETIME NOT NULL,
  PRIMARY KEY (`id_foto`),
  CONSTRAINT `fk_foto_bukti_id_detail_klaim` FOREIGN KEY (`id_detail_klaim`) REFERENCES `detail_klaim` (`id_detail_klaim`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

DELIMITER $$

CREATE TRIGGER `trg_detail_po_insert` BEFORE INSERT ON `detail_po`
FOR EACH ROW BEGIN
  IF NOT EXISTS (SELECT 1 FROM purchase_order p JOIN detail_forecast f ON f.id_forecast=p.id_forecast WHERE p.id_po=NEW.id_po AND f.id_produk=NEW.id_produk) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Produk PO harus tercantum pada forecast PO';
  END IF;
END$$

CREATE TRIGGER `trg_detail_po_update` BEFORE UPDATE ON `detail_po`
FOR EACH ROW BEGIN
  IF NOT EXISTS (SELECT 1 FROM purchase_order p JOIN detail_forecast f ON f.id_forecast=p.id_forecast WHERE p.id_po=NEW.id_po AND f.id_produk=NEW.id_produk) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Produk PO harus tercantum pada forecast PO';
  END IF;
END$$

CREATE TRIGGER `trg_detail_pengiriman_insert` BEFORE INSERT ON `detail_pengiriman`
FOR EACH ROW BEGIN
  IF NOT EXISTS (SELECT 1 FROM pengiriman g JOIN detail_po d ON d.id_po=g.id_po WHERE g.id_pengiriman=NEW.id_pengiriman AND d.id_detail_po=NEW.id_detail_po) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Item pengiriman harus berasal dari PO induk';
  END IF;
END$$

CREATE TRIGGER `trg_detail_pengiriman_update` BEFORE UPDATE ON `detail_pengiriman`
FOR EACH ROW BEGIN
  IF NOT EXISTS (SELECT 1 FROM pengiriman g JOIN detail_po d ON d.id_po=g.id_po WHERE g.id_pengiriman=NEW.id_pengiriman AND d.id_detail_po=NEW.id_detail_po) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Item pengiriman harus berasal dari PO induk';
  END IF;
END$$

CREATE TRIGGER `trg_detail_penerimaan_insert` BEFORE INSERT ON `detail_penerimaan`
FOR EACH ROW BEGIN
  IF NOT EXISTS (SELECT 1 FROM penerimaan_barang p JOIN detail_pengiriman d ON d.id_pengiriman=p.id_pengiriman WHERE p.id_penerimaan=NEW.id_penerimaan AND d.id_detail_pengiriman=NEW.id_detail_pengiriman) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Item penerimaan harus berasal dari pengiriman induk';
  END IF;
END$$

CREATE TRIGGER `trg_detail_penerimaan_update` BEFORE UPDATE ON `detail_penerimaan`
FOR EACH ROW BEGIN
  IF NOT EXISTS (SELECT 1 FROM penerimaan_barang p JOIN detail_pengiriman d ON d.id_pengiriman=p.id_pengiriman WHERE p.id_penerimaan=NEW.id_penerimaan AND d.id_detail_pengiriman=NEW.id_detail_pengiriman) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Item penerimaan harus berasal dari pengiriman induk';
  END IF;
END$$

CREATE TRIGGER `trg_detail_klaim_insert` BEFORE INSERT ON `detail_klaim`
FOR EACH ROW BEGIN
  IF NOT EXISTS (SELECT 1 FROM klaim k JOIN detail_penerimaan d ON d.id_penerimaan=k.id_penerimaan WHERE k.id_klaim=NEW.id_klaim AND d.id_detail_penerimaan=NEW.id_detail_penerimaan) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Item klaim harus berasal dari penerimaan induk';
  END IF;
END$$

CREATE TRIGGER `trg_detail_klaim_update` BEFORE UPDATE ON `detail_klaim`
FOR EACH ROW BEGIN
  IF NOT EXISTS (SELECT 1 FROM klaim k JOIN detail_penerimaan d ON d.id_penerimaan=k.id_penerimaan WHERE k.id_klaim=NEW.id_klaim AND d.id_detail_penerimaan=NEW.id_detail_penerimaan) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Item klaim harus berasal dari penerimaan induk';
  END IF;
END$$

CREATE TRIGGER `trg_purchase_order_jaga_induk` BEFORE UPDATE ON `purchase_order`
FOR EACH ROW BEGIN
 IF (NOT (NEW.`id_forecast` <=> OLD.`id_forecast`)) AND EXISTS (SELECT 1 FROM detail_po WHERE id_po=OLD.id_po) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Induk tidak boleh diganti setelah dipakai rincian';
 END IF;
END$$

CREATE TRIGGER `trg_detail_po_jaga_induk` BEFORE UPDATE ON `detail_po`
FOR EACH ROW BEGIN
 IF (NOT (NEW.`id_po` <=> OLD.`id_po`) OR NOT (NEW.`id_produk` <=> OLD.`id_produk`)) AND EXISTS (SELECT 1 FROM detail_pengiriman WHERE id_detail_po=OLD.id_detail_po) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Induk tidak boleh diganti setelah dipakai rincian';
 END IF;
END$$

CREATE TRIGGER `trg_pengiriman_jaga_induk` BEFORE UPDATE ON `pengiriman`
FOR EACH ROW BEGIN
 IF (NOT (NEW.`id_po` <=> OLD.`id_po`)) AND EXISTS (SELECT 1 FROM detail_pengiriman WHERE id_pengiriman=OLD.id_pengiriman) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Induk tidak boleh diganti setelah dipakai rincian';
 END IF;
END$$

CREATE TRIGGER `trg_detail_pengiriman_jaga_induk` BEFORE UPDATE ON `detail_pengiriman`
FOR EACH ROW BEGIN
 IF (NOT (NEW.`id_pengiriman` <=> OLD.`id_pengiriman`) OR NOT (NEW.`id_detail_po` <=> OLD.`id_detail_po`)) AND EXISTS (SELECT 1 FROM detail_penerimaan WHERE id_detail_pengiriman=OLD.id_detail_pengiriman UNION ALL SELECT 1 FROM sell_in WHERE id_detail_pengiriman=OLD.id_detail_pengiriman) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Induk tidak boleh diganti setelah dipakai rincian';
 END IF;
END$$

CREATE TRIGGER `trg_penerimaan_barang_jaga_induk` BEFORE UPDATE ON `penerimaan_barang`
FOR EACH ROW BEGIN
 IF (NOT (NEW.`id_pengiriman` <=> OLD.`id_pengiriman`)) AND EXISTS (SELECT 1 FROM detail_penerimaan WHERE id_penerimaan=OLD.id_penerimaan) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Induk tidak boleh diganti setelah dipakai rincian';
 END IF;
END$$

CREATE TRIGGER `trg_detail_penerimaan_jaga_induk` BEFORE UPDATE ON `detail_penerimaan`
FOR EACH ROW BEGIN
 IF (NOT (NEW.`id_penerimaan` <=> OLD.`id_penerimaan`) OR NOT (NEW.`id_detail_pengiriman` <=> OLD.`id_detail_pengiriman`)) AND EXISTS (SELECT 1 FROM detail_klaim WHERE id_detail_penerimaan=OLD.id_detail_penerimaan) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Induk tidak boleh diganti setelah dipakai rincian';
 END IF;
END$$

CREATE TRIGGER `trg_klaim_jaga_induk` BEFORE UPDATE ON `klaim`
FOR EACH ROW BEGIN
 IF (NOT (NEW.`id_penerimaan` <=> OLD.`id_penerimaan`)) AND EXISTS (SELECT 1 FROM detail_klaim WHERE id_klaim=OLD.id_klaim) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Induk tidak boleh diganti setelah dipakai rincian';
 END IF;
END$$

CREATE TRIGGER `trg_forecast_jaga_induk` BEFORE UPDATE ON `forecast`
FOR EACH ROW BEGIN
 IF (NOT (NEW.`id_distributor` <=> OLD.`id_distributor`) OR NOT (NEW.`id_tahun_fiskal` <=> OLD.`id_tahun_fiskal`) OR NOT (NEW.`periode` <=> OLD.`periode`)) AND EXISTS (SELECT 1 FROM purchase_order WHERE id_forecast=OLD.id_forecast) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Induk tidak boleh diganti setelah dipakai rincian';
 END IF;
END$$

CREATE TRIGGER `trg_detail_forecast_jaga_delete` BEFORE DELETE ON detail_forecast
FOR EACH ROW BEGIN
 IF EXISTS (SELECT 1 FROM detail_po d JOIN purchase_order p ON p.id_po=d.id_po WHERE p.id_forecast=OLD.id_forecast AND d.id_produk=OLD.id_produk) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Produk forecast sudah digunakan PO';
 END IF;
END$$

CREATE TRIGGER `trg_detail_forecast_jaga_update` BEFORE UPDATE ON detail_forecast
FOR EACH ROW BEGIN
 IF (NEW.id_forecast <> OLD.id_forecast OR NEW.id_produk <> OLD.id_produk) AND EXISTS (SELECT 1 FROM detail_po d JOIN purchase_order p ON p.id_po=d.id_po WHERE p.id_forecast=OLD.id_forecast AND d.id_produk=OLD.id_produk) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Produk forecast sudah digunakan PO';
 END IF;
END$$

DELIMITER ;
