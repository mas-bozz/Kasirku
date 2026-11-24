<?php
/**
 * Dashboard Administrator KasirKu
 */
require_once __DIR__ . '/../includes/admin_auth.php';

// Ambil data metrik statistik untuk Dashboard Admin
try {
    // 1. Total Produk
    $stmtProduk = $pdo->query("SELECT COUNT(*) FROM produk");
    $totalProduk = (int)$stmtProduk->fetchColumn();

    // 2. Total Kategori
    $stmtKategori = $pdo->query("SELECT COUNT(*) FROM kategori");
    $totalKategori = (int)$stmtKategori->fetchColumn();

    // 3. Total Seluruh Transaksi
    $stmtTrx = $pdo->query("SELECT COUNT(*) FROM penjualans");
    $totalTransaksi = (int)$stmtTrx->fetchColumn();

    // 4. Total Pendapatan Hari Ini
    $stmtHariIni = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM penjualans WHERE DATE(tanggal) = CURDATE()");
    $pendapatanHariIni = (float)$stmtHariIni->fetchColumn();

    // 5. Total Pendapatan Bulan Ini
    $stmtBulanIni = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM penjualans WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())");
    $pendapatanBulanIni = (float)$stmtBulanIni->fetchColumn();

    // 6. Produk dengan Stok Menipis (stok <= stok_minimum)
    $stmtStokMenipis = $pdo->query("
        SELECT p.*, k.nama_kategori 
        FROM produk p 
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
        WHERE p.stok <= p.stok_minimum 
        ORDER BY p.stok ASC 
        LIMIT 6
    ");
    $stokMenipisList = $stmtStokMenipis->fetchAll();

    // 7. Transaksi Penjualan Terbaru (5 transaksi terakhir)
    $stmtTrxTerbaru = $pdo->query("
        SELECT p.*, u.nama AS nama_kasir 
        FROM penjualans p 
        LEFT JOIN users u ON p.id_user = u.id_user 
        ORDER BY p.tanggal DESC 
        LIMIT 5
    ");
    $transaksiTerbaru = $stmtTrxTerbaru->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan memuat dashboard: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Dashboard Admin - KasirKu';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Header Dashboard -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Dashboard Administrator</h4>
            <p class="text-muted small mb-0">Ringkasan aktivitas operasional toko, penjualan, dan stok barang.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/produk/tambah.php'); ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Produk
            </a>
            <a href="<?= base_url('kasir/transaksi.php'); ?>" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-cart-plus me-1"></i> Buka Kasir POS
            </a>
        </div>
    </div>

    <!-- 5 Card Statistik Utama -->
    <div class="row g-3 mb-4">
        <!-- Pendapatan Hari Ini -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm border-start border-success border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Pendapatan Hari Ini</div>
                            <h4 class="fw-bold text-dark mt-2 mb-0"><?= format_rupiah($pendapatanHariIni); ?></h4>
                            <div class="small text-muted mt-1">Tanggal: <?= date('d M Y'); ?></div>
                        </div>
                        <div class="p-3 bg-success-subtle rounded-3 text-success">
                            <i class="bi bi-cash-stack fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pendapatan Bulan Ini -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Pendapatan Bulan Ini</div>
                            <h4 class="fw-bold text-dark mt-2 mb-0"><?= format_rupiah($pendapatanBulanIni); ?></h4>
                            <div class="small text-muted mt-1">Bulan: <?= date('F Y'); ?></div>
                        </div>
                        <div class="p-3 bg-primary-subtle rounded-3 text-primary">
                            <i class="bi bi-wallet2 fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card h-100 border-0 shadow-sm border-start border-info border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Transaksi</div>
                            <h4 class="fw-bold text-dark mt-2 mb-0"><?= number_format($totalTransaksi); ?></h4>
                            <div class="small text-muted mt-1">Penjualan</div>
                        </div>
                        <div class="p-3 bg-info-subtle rounded-3 text-info">
                            <i class="bi bi-receipt fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Produk -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card h-100 border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Produk</div>
                            <h4 class="fw-bold text-dark mt-2 mb-0"><?= number_format($totalProduk); ?></h4>
                            <div class="small text-muted mt-1">Item aktif</div>
                        </div>
                        <div class="p-3 bg-warning-subtle rounded-3 text-warning">
                            <i class="bi bi-box-seam fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Kategori -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card h-100 border-0 shadow-sm border-start border-secondary border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Kategori</div>
                            <h4 class="fw-bold text-dark mt-2 mb-0"><?= number_format($totalKategori); ?></h4>
                            <div class="small text-muted mt-1">Klasifikasi</div>
                        </div>
                        <div class="p-3 bg-secondary-subtle rounded-3 text-secondary">
                            <i class="bi bi-tags fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tabel Produk Stok Menipis -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <div class="fw-bold text-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                        <span>Produk Stok Menipis (<= Batas Minimum)</span>
                    </div>
                    <a href="<?= base_url('admin/stok/index.php'); ?>" class="btn btn-sm btn-outline-secondary">
                        Kelola Stok
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Sisa Stok</th>
                                    <th class="text-center">Min</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stokMenipisList)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                                            Semua stok produk dalam kondisi aman.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($stokMenipisList as $item): ?>
                                        <tr>
                                            <td><span class="badge bg-light text-dark border"><?= e($item['kode_produk']); ?></span></td>
                                            <td class="fw-semibold text-dark"><?= e($item['nama_produk']); ?></td>
                                            <td><span class="badge bg-secondary-subtle text-secondary"><?= e($item['nama_kategori'] ?? 'Tanpa Kategori'); ?></span></td>
                                            <td class="text-center">
                                                <span class="badge <?= $item['stok'] <= 0 ? 'bg-danger' : 'bg-warning text-dark'; ?> px-2 py-1">
                                                    <?= (int)$item['stok']; ?> <?= e($item['satuan']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center text-muted small"><?= (int)$item['stok_minimum']; ?></td>
                                            <td class="text-end">
                                                <a href="<?= base_url('admin/stok/index.php?id_produk=' . $item['id_produk']); ?>" class="btn btn-sm btn-primary py-0 px-2" title="Tambah Stok">
                                                    <i class="bi bi-plus"></i> Tambah
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Transaksi Terbaru -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <div class="fw-bold text-dark d-flex align-items-center">
                        <i class="bi bi-clock-history me-2 fs-5 text-primary"></i>
                        <span>Transaksi Penjualan Terbaru</span>
                    </div>
                    <a href="<?= base_url('admin/transaksi/index.php'); ?>" class="btn btn-sm btn-outline-secondary">
                        Lihat Semua
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Tanggal</th>
                                    <th>Kasir</th>
                                    <th>Metode</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transaksiTerbaru)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                            Belum ada transaksi penjualan yang tercatat.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transaksiTerbaru as $trx): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= base_url('kasir/struk.php?id=' . $trx['id_penjualan']); ?>" class="fw-semibold text-primary text-decoration-none">
                                                    <?= e($trx['nomor_transaksi']); ?>
                                                </a>
                                            </td>
                                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($trx['tanggal'])); ?></td>
                                            <td class="small"><?= e($trx['nama_kasir'] ?? 'Kasir'); ?></td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info border">
                                                    <?= e($trx['metode_pembayaran']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                <?= format_rupiah($trx['total']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
