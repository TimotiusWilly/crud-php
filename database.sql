-- ======================================================
-- DATABASE: db_stok_game (VERSI SIMPLE & PRAKTIS)
-- Sistem Manajemen Modal, Stok Akun & Rekapitulasi Laba
-- 100% PHP Native & Vanilla CSS (Admin Only)
-- ======================================================

CREATE DATABASE IF NOT EXISTS `db_stok_game` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_stok_game`;

-- Drop tabel versi lama jika ada
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `transaksi_penjualan`;
DROP TABLE IF EXISTS `akun_game`;
DROP TABLE IF EXISTS `kategori_game`;
DROP TABLE IF EXISTS `penjualan`;
DROP TABLE IF EXISTS `stok_akun`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabel Users (Login Admin)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabel Stok Akun (Pencatatan Modal & Jumlah Akun)
CREATE TABLE IF NOT EXISTS `stok_akun` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tanggal_beli` DATE NOT NULL,
    `nama_akun` VARCHAR(150) NOT NULL,
    `modal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `jumlah_total` INT NOT NULL DEFAULT 1,
    `sisa_stok` INT NOT NULL DEFAULT 1,
    `catatan_login` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Tabel Penjualan (Pencatatan Hasil Jual & Kalkulasi Laba)
CREATE TABLE IF NOT EXISTS `penjualan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stok_id` INT NOT NULL,
    `tanggal_jual` DATE NOT NULL,
    `jumlah_terjual` INT NOT NULL DEFAULT 1,
    `modal_terjual` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `harga_jual` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `laba_bersih` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `catatan` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_penjualan_stok` FOREIGN KEY (`stok_id`) REFERENCES `stok_akun` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ======================================================
-- DATA AWAL (SEED DATA CONTOH)
-- ======================================================

-- Default Admin User (username: admin, password: password123)
INSERT INTO `users` (`username`, `password`, `nama_lengkap`) 
VALUES ('admin', '$2y$10$hBIL.Gz0M/Fe8YoOoPSmIu7PtZPW05bdX2qQuVblHXDjnes//252O', 'Administrator Store')
ON DUPLICATE KEY UPDATE `password`='$2y$10$hBIL.Gz0M/Fe8YoOoPSmIu7PtZPW05bdX2qQuVblHXDjnes//252O';

-- Contoh Stok 1: Beli 1 Akun MLBB modal Rp 350.000 (Masih ada di stok)
INSERT INTO `stok_akun` (`id`, `tanggal_beli`, `nama_akun`, `modal`, `jumlah_total`, `sisa_stok`, `catatan_login`)
VALUES (1, CURDATE() - INTERVAL 4 DAY, 'Akun MLBB Mythic', 350000.00, 1, 1, 'email: ml01@gmail.com | pass: pass123');

-- Contoh Stok 2: Beli 3 Akun Valorant Borongan total modal Rp 600.000 (modal per akun Rp 200.000). Sisa 2 karena laku 1
INSERT INTO `stok_akun` (`id`, `tanggal_beli`, `nama_akun`, `modal`, `jumlah_total`, `sisa_stok`, `catatan_login`)
VALUES (2, CURDATE() - INTERVAL 2 DAY, 'Stok Akun Valorant', 600000.00, 3, 2, 'Akun 1, 2, 3 riot id');

-- Contoh Stok 3: Beli 1 Akun Genshin modal Rp 250.000 (Sudah laku terjual)
INSERT INTO `stok_akun` (`id`, `tanggal_beli`, `nama_akun`, `modal`, `jumlah_total`, `sisa_stok`, `catatan_login`)
VALUES (3, CURDATE() - INTERVAL 6 DAY, 'Akun Genshin AR 55', 250000.00, 1, 0, 'email: genshin@test.com');

-- Catatan Penjualan Akun 2 (laku 1 akun seharga Rp 280.000 -> Modal 200.000 -> Laba = 80.000)
INSERT INTO `penjualan` (`stok_id`, `tanggal_jual`, `jumlah_terjual`, `modal_terjual`, `harga_jual`, `laba_bersih`, `catatan`)
VALUES (2, CURDATE() - INTERVAL 1 DAY, 1, 200000.00, 280000.00, 80000.00, 'Laku ke Dimas via WA');

-- Catatan Penjualan Akun 3 (laku seharga Rp 350.000 -> Modal 250.000 -> Laba = 100.000)
INSERT INTO `penjualan` (`stok_id`, `tanggal_jual`, `jumlah_terjual`, `modal_terjual`, `harga_jual`, `laba_bersih`, `catatan`)
VALUES (3, CURDATE() - INTERVAL 3 DAY, 1, 250000.00, 350000.00, 100000.00, 'Laku transfer BCA');
