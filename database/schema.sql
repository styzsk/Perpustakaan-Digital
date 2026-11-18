-- ============================================================================
-- BukuHub — Skema database
-- MySQL 5.7+ / MariaDB 10.3+ (XAMPP, Laragon)
--
-- Cara pakai:
--   1. Buka phpMyAdmin → Import, pilih file ini; atau
--      mysql -u root -p < database/schema.sql
--   2. Setelah skema dibuat, impor database/seed.sql untuk data contoh.
--
-- File ini menghapus tabel BukuHub yang sudah ada agar impor ulang bersih.
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS `bukuhub`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `bukuhub`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `denda`;
DROP TABLE IF EXISTS `peminjaman`;
DROP TABLE IF EXISTS `buku`;
DROP TABLE IF EXISTS `anggota`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `pengaturan`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- users — akun pengelola aplikasi (pustakawan/admin)
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id_user`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`          VARCHAR(100) NOT NULL,
  `username`      VARCHAR(60)  NOT NULL,
  `email`         VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('admin','pustakawan') NOT NULL DEFAULT 'pustakawan',
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- anggota — pemustaka yang dapat meminjam buku
-- ----------------------------------------------------------------------------
CREATE TABLE `anggota` (
  `id_anggota` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(100) NOT NULL,
  `nim_nik`    VARCHAR(30)  NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `telepon`    VARCHAR(20)  NULL DEFAULT NULL,
  `alamat`     VARCHAR(255) NULL DEFAULT NULL,
  `maks_pinjam` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `status`     ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_anggota`),
  UNIQUE KEY `uq_anggota_nim` (`nim_nik`),
  UNIQUE KEY `uq_anggota_email` (`email`),
  KEY `idx_anggota_status` (`status`),
  KEY `idx_anggota_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- buku — katalog koleksi
-- jumlah_eksemplar = total kopi yang dimiliki
-- stok_tersedia    = kopi yang ada di rak (berubah hanya lewat transaksi)
-- ----------------------------------------------------------------------------
CREATE TABLE `buku` (
  `id_buku`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul`           VARCHAR(180) NOT NULL,
  `pengarang`       VARCHAR(120) NOT NULL,
  `kategori`        VARCHAR(40)  NOT NULL,
  `isbn`            VARCHAR(30)  NULL DEFAULT NULL,
  `penerbit`        VARCHAR(120) NULL DEFAULT NULL,
  `tahun_terbit`    SMALLINT UNSIGNED NULL DEFAULT NULL,
  `sinopsis`        TEXT NULL DEFAULT NULL,
  `jumlah_eksemplar` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `stok_tersedia`   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_buku`),
  KEY `idx_buku_judul` (`judul`),
  KEY `idx_buku_pengarang` (`pengarang`),
  KEY `idx_buku_kategori` (`kategori`),
  KEY `idx_buku_isbn` (`isbn`),
  KEY `idx_buku_stok` (`stok_tersedia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- peminjaman — transaksi sirkulasi
-- status: dipinjam | dikembalikan
-- "terlambat" adalah status turunan: status = dipinjam dan jatuh tempo < hari ini
-- denda_per_hari menyimpan tarif saat transaksi dibuat (snapshot)
-- ----------------------------------------------------------------------------
CREATE TABLE `peminjaman` (
  `id_peminjaman`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_anggota`       INT UNSIGNED NOT NULL,
  `id_buku`          INT UNSIGNED NOT NULL,
  `tgl_pinjam`       DATE NOT NULL,
  `tgl_kembali_rencana` DATE NOT NULL,
  `tgl_kembali_real` DATE NULL DEFAULT NULL,
  `status`           ENUM('dipinjam','dikembalikan') NOT NULL DEFAULT 'dipinjam',
  `denda_per_hari`   INT UNSIGNED NOT NULL DEFAULT 1000,
  `created_by`       INT UNSIGNED NULL DEFAULT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_peminjaman`),
  KEY `idx_peminjaman_anggota` (`id_anggota`),
  KEY `idx_peminjaman_buku` (`id_buku`),
  KEY `idx_peminjaman_status` (`status`),
  KEY `idx_peminjaman_tgl_pinjam` (`tgl_pinjam`),
  KEY `idx_peminjaman_jatuh_tempo` (`tgl_kembali_rencana`),
  KEY `idx_peminjaman_created_by` (`created_by`),
  CONSTRAINT `fk_peminjaman_anggota` FOREIGN KEY (`id_anggota`)
    REFERENCES `anggota` (`id_anggota`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_peminjaman_buku` FOREIGN KEY (`id_buku`)
    REFERENCES `buku` (`id_buku`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- denda — satu denda per peminjaman terlambat
-- UNIQUE(id_peminjaman) mencegah denda ganda dan pembayaran ganda
-- ----------------------------------------------------------------------------
CREATE TABLE `denda` (
  `id_denda`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_peminjaman` INT UNSIGNED NOT NULL,
  `jumlah_hari`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `tarif_per_hari` INT UNSIGNED NOT NULL DEFAULT 1000,
  `total_denda`   INT UNSIGNED NOT NULL DEFAULT 0,
  `status_bayar`  ENUM('belum_lunas','lunas') NOT NULL DEFAULT 'belum_lunas',
  `tgl_bayar`     DATE NULL DEFAULT NULL,
  `paid_by`       INT UNSIGNED NULL DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_denda`),
  UNIQUE KEY `uq_denda_peminjaman` (`id_peminjaman`),
  KEY `idx_denda_status` (`status_bayar`),
  KEY `idx_denda_tgl_bayar` (`tgl_bayar`),
  KEY `idx_denda_paid_by` (`paid_by`),
  CONSTRAINT `fk_denda_peminjaman` FOREIGN KEY (`id_peminjaman`)
    REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_denda_user` FOREIGN KEY (`paid_by`)
    REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- pengaturan — satu baris konfigurasi (id_pengaturan = 1)
-- ----------------------------------------------------------------------------
CREATE TABLE `pengaturan` (
  `id_pengaturan`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `nama_aplikasi`     VARCHAR(60)  NOT NULL DEFAULT 'BukuHub',
  `nama_instansi`     VARCHAR(120) NOT NULL DEFAULT 'Perpustakaan Digital BukuHub',
  `tarif_denda`       INT UNSIGNED NOT NULL DEFAULT 1000,
  `durasi_hari`       TINYINT UNSIGNED NOT NULL DEFAULT 7,
  `maks_pinjam_default` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `email_kontak`      VARCHAR(120) NULL DEFAULT NULL,
  `alamat`            VARCHAR(255) NULL DEFAULT NULL,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengaturan`),
  CONSTRAINT `chk_pengaturan_single` CHECK (`id_pengaturan` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- audit_log — jejak aktivitas penting pengguna
-- Tidak menyimpan password, token CSRF, atau data sensitif lain.
-- ----------------------------------------------------------------------------
CREATE TABLE `audit_log` (
  `id_log`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user`    INT UNSIGNED NULL DEFAULT NULL,
  `aksi`       VARCHAR(60) NOT NULL,
  `entitas`    VARCHAR(40) NOT NULL,
  `entitas_id` INT UNSIGNED NULL DEFAULT NULL,
  `detail`     VARCHAR(255) NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_audit_user` (`id_user`),
  KEY `idx_audit_aksi` (`aksi`),
  KEY `idx_audit_entitas` (`entitas`, `entitas_id`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`id_user`)
    REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
