<?php
/**
 * Dashboard Kasir KasirKu
 */
require_once __DIR__ . '/../includes/kasir_auth.php';

$userId = (int)$_SESSION['user_id'];

// Ambil metrik penjualan untuk kasir yang sedang bertugas
try {
    // 1. Total Transaksi Hari Ini oleh kasir ini
    $stmtHariIni = $pdo->prepare("SELECT COUNT(*) FROM penjualans WHERE id_user = :uid AND DATE(tanggal) = CURDATE()");
    $stmtHariIni->execute(['uid' => $userId]);
    $totalTrxHariIni = (int)$stmtHariIni->fetchColumn();

    // 2. Total Penjualan Hari Ini oleh kasir ini
    $stmtPenjualanHariIni = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM penjualans WHERE id_user = :uid AND DATE(tanggal) = CURDATE()");
    $stmtPenjualanHariIni->execute(['uid' => $userId]);
    $totalPenjualanHariIni = (float)$stmtPenjualanHariIni->fetchColumn();

    // 3. Riwayat Transaksi Terbaru oleh kasir ini (5 terakhir)
    $stmtTrxTerbaru = $pdo->prepare("
        SELECT * FROM penjualans 
        WHERE id_user = :uid 
        ORDER BY tanggal DESC 
        LIMIT 5
    ");
    $stmtTrxTerbaru->execute(['uid' => $userId]);
    $transaksiTerbaru = $stmtTrxTerbaru->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan memuat dashboard kasir: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Dashboard Kasir - KasirKu';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Header Dashboard Kasir -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Dashboard Kasir</h4>
            <p class="text-muted small mb-0">Selamat bertugas, <strong><?= e($_SESSION['nama']); ?></strong>. Layani transaksi dengan cepat dan teliti.</p>
        </div>
        <div>
            <a href="<?= base_url('kasir/transaksi.php'); ?>" class="btn btn-primary btn-lg shadow-sm">
                <i class="bi bi-cart-plus me-1"></i> Mulai Transaksi Baru (POS)
            </a>
        </div>
    </div>

    <!-- Card Statistik Kasir -->
    <div class="row g-3 mb-4">
        <!-- Transaksi Hari Ini -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Transaksi Anda Hari Ini</div>
                            <h3 class="fw-bold text-dark mt-2 mb-0"><?= number_format($totalTrxHariIni); ?> <span class="fs-6 text-muted fw-normal">struk</span></h3>
                            <div class="small text-muted mt-1">Hari: <?= date('d M Y'); ?></div>
                        </div>
                        <div class="p-3 bg-primary-subtle rounded-3 text-primary">
                            <i class="bi bi-receipt-cutoff fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Penjualan Hari Ini -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm border-start border-success border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Penjualan Anda Hari Ini</div>
                            <h3 class="fw-bold text-success mt-2 mb-0"><?= format_rupiah($totalPenjualanHariIni); ?></h3>
                            <div class="small text-muted mt-1">Akumulasi sesi bertugas</div>
                        </div>
                        <div class="p-3 bg-success-subtle rounded-3 text-success">
                            <i class="bi bi-cash-coin fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="col-12 col-md-12 col-lg-4">
            <div class="card h-100 border-0 shadow-sm border-start border-warning border-4 bg-white">
                <div class="card-body p-3 d-flex flex-column justify-content-center">
                    <div class="text-muted small fw-semibold text-uppercase mb-2">Aksi Cepat</div>
                    <div class="d-grid gap-2">
                        <a href="<?= base_url('kasir/transaksi.php'); ?>" class="btn btn-outline-primary btn-sm text-start">
                            <i class="bi bi-cart-check me-2"></i> Buka Terminal POS
                        </a>
                        <a href="<?= base_url('kasir/riwayat.php'); ?>" class="btn btn-outline-secondary btn-sm text-start">
                            <i class="bi bi-clock-history me-2"></i> Buka Riwayat Transaksi Saya
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Transaksi Terbaru Milik Kasir Ini -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <div class="fw-bold text-dark d-flex align-items-center">
                <i class="bi bi-clock-history me-2 fs-5 text-primary"></i>
                <span>Transaksi Terakhir yang Anda Proses</span>
            </div>
            <a href="<?= base_url('kasir/riwayat.php'); ?>" class="btn btn-sm btn-outline-secondary">
                Lihat Semua Riwayat
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Waktu</th>
                            <th>Metode</th>
                            <th>Subtotal</th>
                            <th>Diskon</th>
                            <th>Total Bayar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksiTerbaru)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                    Anda belum memproses transaksi penjualan hari ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transaksiTerbaru as $trx): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= e($trx['nomor_transaksi']); ?></td>
                                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($trx['tanggal'])); ?></td>
                                    <td><span class="badge bg-info-subtle text-info border"><?= e($trx['metode_pembayaran']); ?></span></td>
                                    <td><?= format_rupiah($trx['subtotal']); ?></td>
                                    <td class="text-danger">-<?= format_rupiah($trx['diskon']); ?></td>
                                    <td class="fw-bold text-success"><?= format_rupiah($trx['total']); ?></td>
                                    <td class="text-end">
                                        <a href="<?= base_url('kasir/struk.php?id=' . $trx['id_penjualan']); ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Cetak Struk">
                                            <i class="bi bi-printer me-1"></i> Struk
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
