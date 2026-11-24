<?php
/**
 * Detail Transaksi Penjualan - KasirKu (Administrator)
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID transaksi tidak valid.');
    header('Location: ' . base_url('admin/transaksi/index.php'));
    exit;
}

try {
    // 1. Ambil data penjualan beserta nama user
    $stmtTrx = $pdo->prepare("
        SELECT p.*, u.nama AS nama_kasir, u.username AS username_kasir, u.role AS role_kasir 
        FROM penjualans p 
        LEFT JOIN users u ON p.id_user = u.id_user 
        WHERE p.id_penjualan = :id 
        LIMIT 1
    ");
    $stmtTrx->execute(['id' => $id]);
    $trx = $stmtTrx->fetch();

    if (!$trx) {
        set_flash('danger', 'Data transaksi tidak ditemukan.');
        header('Location: ' . base_url('admin/transaksi/index.php'));
        exit;
    }

    // 2. Ambil detail item produk dalam transaksi
    $stmtDetail = $pdo->prepare("
        SELECT d.*, pr.kode_produk, pr.nama_produk, pr.satuan, k.nama_kategori 
        FROM detail_penjualan d 
        JOIN produk pr ON d.id_produk = pr.id_produk 
        LEFT JOIN kategori k ON pr.id_kategori = k.id_kategori 
        WHERE d.id_penjualan = :id 
        ORDER BY d.id_detail ASC
    ");
    $stmtDetail->execute(['id' => $id]);
    $items = $stmtDetail->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Detail Transaksi ' . $trx['nomor_transaksi'] . ' - KasirKu';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-1">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php'); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/transaksi/index.php'); ?>">Transaksi</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($trx['nomor_transaksi']); ?></li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">Rincian Transaksi: <?= e($trx['nomor_transaksi']); ?></h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/transaksi/index.php'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <a href="<?= base_url('kasir/struk.php?id=' . $trx['id_penjualan']); ?>" class="btn btn-primary" target="_blank">
                <i class="bi bi-printer me-1"></i> Cetak Struk
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Rincian Metadata Transaksi -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom fw-bold text-dark">
                    <i class="bi bi-info-circle text-primary me-2"></i> Informasi Penjualan
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Nomor Transaksi:</span>
                            <span class="fw-bold text-primary"><?= e($trx['nomor_transaksi']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Tanggal & Jam:</span>
                            <span class="fw-semibold text-dark"><?= date('d/m/Y H:i:s', strtotime($trx['tanggal'])); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Kasir Bertugas:</span>
                            <span class="fw-semibold text-dark"><?= e($trx['nama_kasir'] ?? 'Kasir'); ?> (<?= e($trx['role_kasir'] ?? 'kasir'); ?>)</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Metode Pembayaran:</span>
                            <span class="badge bg-info-subtle text-info border"><?= e($trx['metode_pembayaran']); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Ringkasan Finansial -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom fw-bold text-dark">
                    <i class="bi bi-cash-stack text-success me-2"></i> Rincian Pembayaran
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Subtotal Belanja:</span>
                            <span class="fw-semibold text-dark"><?= format_rupiah($trx['subtotal']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Diskon:</span>
                            <span class="fw-semibold text-danger"><?= $trx['diskon'] > 0 ? '-' . format_rupiah($trx['diskon']) : 'Rp 0'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 fs-6 py-2 border-top border-bottom my-1">
                            <span class="fw-bold text-dark">Total Tagihan:</span>
                            <span class="fw-bold text-primary"><?= format_rupiah($trx['total']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Uang Diterima:</span>
                            <span class="fw-semibold text-dark"><?= format_rupiah($trx['pembayaran']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Kembalian:</span>
                            <span class="fw-bold text-success"><?= format_rupiah($trx['kembalian']); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tabel Barang yang Dibeli -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom fw-bold text-dark">
                    <i class="bi bi-bag-check text-primary me-2"></i> Daftar Barang yang Dibeli
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">No</th>
                                    <th>Kode</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($items as $it): ?>
                                    <tr>
                                        <td class="text-center text-muted"><?= $no++; ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e($it['kode_produk']); ?></span>
                                        </td>
                                        <td class="fw-semibold text-dark"><?= e($it['nama_produk']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= e($it['nama_kategori'] ?? '-'); ?></span>
                                        </td>
                                        <td class="text-end text-muted small"><?= format_rupiah($it['harga']); ?></td>
                                        <td class="text-center fw-bold">
                                            <?= (int)$it['jumlah']; ?> <span class="small text-muted fw-normal"><?= e($it['satuan']); ?></span>
                                        </td>
                                        <td class="text-end fw-bold text-primary"><?= format_rupiah($it['subtotal']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 text-muted small text-end">
                    Total Item: <strong><?= count($items); ?></strong> macam barang
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

