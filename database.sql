
-- --------------------------------------------------------
-- 1. Tabel users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id_user` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Tabel kategori
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kategori` (
    `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_kategori_nama` (`nama_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabel produk
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `produk` (
    `id_produk` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_produk` VARCHAR(50) NOT NULL UNIQUE,
    `nama_produk` VARCHAR(150) NOT NULL,
    `id_kategori` INT NOT NULL,
    `harga_beli` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `harga_jual` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `stok` INT NOT NULL DEFAULT 0,
    `stok_minimum` INT NOT NULL DEFAULT 5,
    `satuan` VARCHAR(30) NOT NULL DEFAULT 'pcs',
    `gambar` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_produk_kategori` (`id_kategori`),
    INDEX `idx_produk_nama` (`nama_produk`),
    INDEX `idx_produk_stok` (`stok`),
    CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`id_kategori`)
        REFERENCES `kategori` (`id_kategori`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Tabel stock_movements (Riwayat Perubahan Stok)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_movements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_produk` INT NOT NULL,
    `jenis` ENUM('masuk', 'keluar', 'penyesuaian') NOT NULL,
    `jumlah` INT NOT NULL,
    `stok_sebelum` INT NOT NULL,
    `stok_sesudah` INT NOT NULL,
    `keterangan` TEXT NULL,
    `id_user` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_stock_produk` (`id_produk`),
    INDEX `idx_stock_user` (`id_user`),
    INDEX `idx_stock_created` (`created_at`),
    CONSTRAINT `fk_stock_produk` FOREIGN KEY (`id_produk`)
        REFERENCES `produk` (`id_produk`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_stock_user` FOREIGN KEY (`id_user`)
        REFERENCES `users` (`id_user`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Tabel penjualans (Header Transaksi Penjualan)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `penjualans` (
    `id_penjualan` INT AUTO_INCREMENT PRIMARY KEY,
    `nomor_transaksi` VARCHAR(50) NOT NULL UNIQUE,
    `id_user` INT NOT NULL,
    `tanggal` DATETIME NOT NULL,
    `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `diskon` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `pembayaran` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `kembalian` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `metode_pembayaran` ENUM('Tunai', 'Transfer', 'QRIS') NOT NULL DEFAULT 'Tunai',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_penjualan_user` (`id_user`),
    INDEX `idx_penjualan_tanggal` (`tanggal`),
    INDEX `idx_penjualan_metode` (`metode_pembayaran`),
    CONSTRAINT `fk_penjualan_user` FOREIGN KEY (`id_user`)
        REFERENCES `users` (`id_user`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Tabel detail_penjualan (Detail Item Transaksi)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detail_penjualan` (
    `id_detail` INT AUTO_INCREMENT PRIMARY KEY,
    `id_penjualan` INT NOT NULL,
    `id_produk` INT NOT NULL,
    `harga` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `jumlah` INT NOT NULL,
    `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    INDEX `idx_detail_penjualan` (`id_penjualan`),
    INDEX `idx_detail_produk` (`id_produk`),
    CONSTRAINT `fk_detail_penjualan` FOREIGN KEY (`id_penjualan`)
        REFERENCES `penjualans` (`id_penjualan`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`)
        REFERENCES `produk` (`id_produk`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SEED DATA DEFAULT
-- Password admin default : admin123
-- Password kasir default : kasir123
-- --------------------------------------------------------

INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `role`, `status`) VALUES
(1, 'Administrator Toko', 'admin', '$2y$12$m0jJYDrdsECb1txkjTmgB.7koPAP/o7sViCZ52Zl7q5dq/lPBSJDi', 'admin', 'aktif'),
(2, 'Kasir 1', 'kasir', '$2y$12$2V/aNuHSRxtu/3VLgXRd5OIWgd.z9oMmXeHlWWZn8y.idv8juMlPa', 'kasir', 'aktif')
ON DUPLICATE KEY UPDATE `id_user`=`id_user`;

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Sembako'),
(2, 'Makanan Ringan'),
(3, 'Minuman'),
(4, 'Alat Tulis Kantor')
ON DUPLICATE KEY UPDATE `id_kategori`=`id_kategori`;

INSERT INTO `produk` (`id_produk`, `kode_produk`, `nama_produk`, `id_kategori`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `satuan`, `gambar`) VALUES
(1, 'PRD001', 'Minyak Goreng 1 Liter', 1, 14000.00, 16500.00, 35, 5, 'Pouch', NULL),
(2, 'PRD002', 'Gula Pasir Kristal 1 Kg', 1, 13500.00, 16000.00, 20, 5, 'Kg', NULL),
(3, 'PRD003', 'Beras Ramos Super 5 Kg', 1, 62000.00, 72000.00, 15, 3, 'Karung', NULL),
(4, 'PRD004', 'Keripik Singkong Balado 150g', 2, 7500.00, 11000.00, 25, 5, 'Bungkus', NULL),
(5, 'PRD005', 'Biskuit Cokelat Roma 200g', 2, 6500.00, 9000.00, 3, 5, 'Bungkus', NULL),
(6, 'PRD006', 'Teh Botol Sosro 450ml', 3, 3800.00, 5500.00, 40, 10, 'Botol', NULL),
(7, 'PRD007', 'Air Mineral 600ml', 3, 2000.00, 3500.00, 50, 10, 'Botol', NULL),
(8, 'PRD008', 'Kopi Susu Instant Box', 3, 12000.00, 15000.00, 4, 5, 'Kotak', NULL),
(9, 'PRD009', 'Buku Tulis KIKY 38 Lembar', 4, 3500.00, 5500.00, 30, 5, 'Buku', NULL),
(10, 'PRD010', 'Pulpen Gel Hitam 0.5mm', 4, 2500.00, 4000.00, 45, 10, 'Pcs', NULL)
ON DUPLICATE KEY UPDATE `id_produk`=`id_produk`;

