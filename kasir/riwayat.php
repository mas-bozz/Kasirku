<?php
/**
 * Riwayat Transaksi Kasir - KasirKu
 * Menampilkan seluruh transaksi yang diproses oleh kasir yang sedang login
 */
require_once __DIR__ . '/../includes/kasir_auth.php';

$userId = (int)$_SESSION['user_id'];
$search = trim($_GET['q'] ?? '');
$tglMulai = trim($_GET['tgl_mulai'] ?? date('Y-m-01')); // Default awal bulan ini
$tglSelesai = trim($_GET['tgl_selesai'] ?? date('Y-m-d')); // Default hari ini
$metode = trim($_GET['metode'] ?? '');

try {
    $sql = "
        SELECT p.*, COUNT(d.id_detail) AS total_item, SUM(d.jumlah) AS total_qty 
        FROM penjualans p 
        LEFT JOIN detail_penjualan d ON p.id_penjualan = d.id_penjualan 
        WHERE p.id_user = :uid 
    ";
    $params = ['uid' => $userId];

    if (!empty($search)) {
        $sql .= " AND p.nomor_transaksi LIKE :search";
        $params['search'] = "%$search%";
    }

    if (!empty($tglMulai)) {
        $sql .= " AND DATE(p.tanggal) >= :tgl_mulai";
        $params['tgl_mulai'] = $tglMulai;
    }

    if (!empty($tglSelesai)) {
        $sql .= " AND DATE(p.tanggal) <= :tgl_selesai";
        $params['tgl_selesai'] = $tglSelesai;
    }

    if (!empty($metode) && in_array($metode, ['Tunai', 'Transfer', 'QRIS'])) {
        $sql .= " AND p.metode_pembayaran = :metode";
        $params['metode'] = $metode;
    }

    $sql .= " GROUP BY p.id_penjualan ORDER BY p.tanggal DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transaksiList = $stmt->fetchAll();

    // Hitung ringkasan statistik dari hasil filter
    $summaryTrx = count($transaksiList);
    $summaryTotal = array_sum(array_column($transaksiList, 'total'));
    $summaryDiskon = array_sum(array_column($transaksiList, 'diskon'));
    $summaryQty = array_sum(array_column($transaksiList, 'total_qty'));

} catch (PDOException $e) {
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Riwayat Transaksi Saya - KasirKu';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-1">
                    <li class="breadcrumb-item"><a href="<?= base_url('kasir/index.php'); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Riwayat Transaksi</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">Riwayat Transaksi Penjualan Saya</h4>
            <p class="text-muted small mb-0">Daftar transaksi yang diproses oleh kasir: <strong><?= e($_SESSION['nama']); ?></strong></p>
        </div>
        <div>
            <a href="<?= base_url('kasir/transaksi.php'); ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-cart-plus me-1"></i> Transaksi Baru (POS)
            </a>
        </div>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Jumlah Transaksi</div>
                <h4 class="fw-bold text-dark mt-2 mb-0"><?= number_format($summaryTrx); ?> <span class="fs-6 text-muted fw-normal">struk</span></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-success border-4 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Total Penjualan</div>
                <h4 class="fw-bold text-success mt-2 mb-0"><?= format_rupiah($summaryTotal); ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-danger border-4 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Total Diskon Diberikan</div>
                <h4 class="fw-bold text-danger mt-2 mb-0"><?= format_rupiah($summaryDiskon); ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-info border-4 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Barang Terjual</div>
                <h4 class="fw-bold text-info mt-2 mb-0"><?= number_format($summaryQty); ?> <span class="fs-6 text-muted fw-normal">item</span></h4>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <!-- Search Nomor Transaksi -->
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cari nomor transaksi..." value="<?= e($search); ?>">
                    </div>
                </div>

                <!-- Dari Tanggal -->
                <div class="col-md-2">
                    <input type="date" name="tgl_mulai" class="form-control" value="<?= e($tglMulai); ?>" title="Dari Tanggal">
                </div>

                <!-- Sampai Tanggal -->
                <div class="col-md-2">
                    <input type="date" name="tgl_selesai" class="form-control" value="<?= e($tglSelesai); ?>" title="Sampai Tanggal">
                </div>

                <!-- Filter Metode Pembayaran -->
                <div class="col-md-2">
                    <select name="metode" class="form-select">
                        <option value="">-- Semua Metode --</option>
                        <option value="Tunai" <?= $metode === 'Tunai' ? 'selected' : ''; ?>>Tunai</option>
                        <option value="Transfer" <?= $metode === 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
                        <option value="QRIS" <?= $metode === 'QRIS' ? 'selected' : ''; ?>>QRIS</option>
                    </select>
                </div>

                <!-- Tombol Submit & Reset -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="<?= base_url('kasir/riwayat.php'); ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data Transaksi -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>No. Transaksi</th>
                            <th>Tanggal & Waktu</th>
                            <th class="text-center">Jumlah Barang</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end">Total Bayar</th>
                            <th class="text-center">Metode</th>
                            <th class="text-end" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksiList)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Tidak ada data transaksi yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($transaksiList as $row): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $no++; ?></td>
                                    <td>
                                        <a href="<?= base_url('kasir/struk.php?id=' . $row['id_penjualan']); ?>" class="fw-bold text-primary text-decoration-none">
                                            <?= e($row['nomor_transaksi']); ?>
                                        </a>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('d/m/Y H:i', strtotime($row['tanggal'])); ?> WIB
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <?= (int)$row['total_qty']; ?> item (<?= (int)$row['total_item']; ?> jenis)
                                        </span>
                                    </td>
                                    <td class="text-end text-muted small"><?= format_rupiah($row['subtotal']); ?></td>
                                    <td class="text-end text-danger small">
                                        <?= $row['diskon'] > 0 ? '-' . format_rupiah($row['diskon']) : '-'; ?>
                                    </td>
                                    <td class="text-end fw-bold text-success"><?= format_rupiah($row['total']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info border">
                                            <?= e($row['metode_pembayaran']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('kasir/struk.php?id=' . $row['id_penjualan']); ?>" class="btn btn-outline-primary" target="_blank" title="Lihat & Cetak Struk">
                                                <i class="bi bi-printer"></i> Struk
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 text-muted small">
            Menampilkan <strong><?= count($transaksiList); ?></strong> transaksi
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

