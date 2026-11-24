<?php
/**
 * Laporan Penjualan - KasirKu (Administrator)
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

// Tentukan periode laporan
$periode = trim($_GET['periode'] ?? 'bulan_ini');
$tglMulai = trim($_GET['tgl_mulai'] ?? '');
$tglSelesai = trim($_GET['tgl_selesai'] ?? '');

$today = date('Y-m-d');

if ($periode === 'hari_ini') {
    $tglMulai = $today;
    $tglSelesai = $today;
} elseif ($periode === 'kemarin') {
    $kemarin = date('Y-m-d', strtotime('-1 day'));
    $tglMulai = $kemarin;
    $tglSelesai = $kemarin;
} elseif ($periode === 'minggu_ini') {
    $tglMulai = date('Y-m-d', strtotime('monday this week'));
    $tglSelesai = date('Y-m-d', strtotime('sunday this week'));
} elseif ($periode === 'bulan_ini') {
    $tglMulai = date('Y-m-01');
    $tglSelesai = date('Y-m-t');
} elseif ($periode === 'custom') {
    if (empty($tglMulai)) $tglMulai = date('Y-m-01');
    if (empty($tglSelesai)) $tglSelesai = $today;
} else {
    $periode = 'bulan_ini';
    $tglMulai = date('Y-m-01');
    $tglSelesai = date('Y-m-t');
}

try {
    // 1. Ringkasan Metrik Laporan Penjualan & Keuntungan
    $sqlSummary = "
        SELECT 
            COUNT(DISTINCT p.id_penjualan) AS total_transaksi,
            COALESCE(SUM(d.jumlah), 0) AS total_barang_terjual,
            COALESCE(SUM(p.subtotal), 0) AS total_penjualan_kotor,
            COALESCE(SUM(p.diskon), 0) AS total_diskon,
            COALESCE(SUM(p.total), 0) AS pendapatan_bersih,
            COALESCE(SUM(d.jumlah * pr.harga_beli), 0) AS total_hpp_modal
        FROM penjualans p
        LEFT JOIN detail_penjualan d ON p.id_penjualan = d.id_penjualan
        LEFT JOIN produk pr ON d.id_produk = pr.id_produk
        WHERE DATE(p.tanggal) BETWEEN :tgl_mulai AND :tgl_selesai
    ";
    $stmtSum = $pdo->prepare($sqlSummary);
    $stmtSum->execute(['tgl_mulai' => $tglMulai, 'tgl_selesai' => $tglSelesai]);
    $summary = $stmtSum->fetch();

    $totalTransaksi = (int)$summary['total_transaksi'];
    $totalBarang    = (int)$summary['total_barang_terjual'];
    $penjualanKotor = (float)$summary['total_penjualan_kotor'];
    $totalDiskon    = (float)$summary['total_diskon'];
    $pendapatanNet  = (float)$summary['pendapatan_bersih'];
    $totalHPP       = (float)$summary['total_hpp_modal'];
    $estimasiProfit = $pendapatanNet - $totalHPP;

    // 2. Daftar Transaksi dalam Rentang Periode
    $sqlTrx = "
        SELECT p.*, u.nama AS nama_kasir, 
               COUNT(d.id_detail) AS macam_item, 
               SUM(d.jumlah) AS qty_terjual 
        FROM penjualans p 
        LEFT JOIN users u ON p.id_user = u.id_user 
        LEFT JOIN detail_penjualan d ON p.id_penjualan = d.id_penjualan 
        WHERE DATE(p.tanggal) BETWEEN :tgl_mulai AND :tgl_selesai 
        GROUP BY p.id_penjualan 
        ORDER BY p.tanggal DESC
    ";
    $stmtTrx = $pdo->prepare($sqlTrx);
    $stmtTrx->execute(['tgl_mulai' => $tglMulai, 'tgl_selesai' => $tglSelesai]);
    $laporanList = $stmtTrx->fetchAll();

} catch (PDOException $e) {
    die("Gagal memuat laporan: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Laporan Penjualan - KasirKu';
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
                    <li class="breadcrumb-item active" aria-current="page">Laporan Penjualan</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">Laporan Penjualan & Pendapatan</h4>
            <p class="text-muted small mb-0">
                Periode: <strong><?= date('d M Y', strtotime($tglMulai)); ?></strong> s/d <strong><?= date('d M Y', strtotime($tglSelesai)); ?></strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/laporan/produk.php'); ?>" class="btn btn-outline-info">
                <i class="bi bi-pie-chart me-1"></i> Laporan Produk & Nilai Stok
            </a>
            <!-- Tombol Cetak Print View -->
            <a href="<?= base_url('admin/laporan/cetak.php?tgl_mulai=' . $tglMulai . '&tgl_selesai=' . $tglSelesai); ?>" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> Cetak Laporan
            </a>
            <!-- Tombol Export Excel -->
            <a href="<?= base_url('admin/laporan/export_excel.php?tgl_mulai=' . $tglMulai . '&tgl_selesai=' . $tglSelesai); ?>" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </a>
        </div>
    </div>

    <!-- Tombol Filter Preset Periode -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= base_url('admin/laporan/index.php?periode=hari_ini'); ?>" class="btn <?= $periode === 'hari_ini' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        Hari Ini
                    </a>
                    <a href="<?= base_url('admin/laporan/index.php?periode=kemarin'); ?>" class="btn <?= $periode === 'kemarin' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        Kemarin
                    </a>
                    <a href="<?= base_url('admin/laporan/index.php?periode=minggu_ini'); ?>" class="btn <?= $periode === 'minggu_ini' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        Minggu Ini
                    </a>
                    <a href="<?= base_url('admin/laporan/index.php?periode=bulan_ini'); ?>" class="btn <?= $periode === 'bulan_ini' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        Bulan Ini
                    </a>
                </div>

                <!-- Form Custom Tanggal -->
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="periode" value="custom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted">Dari:</span>
                        <input type="date" name="tgl_mulai" class="form-control" value="<?= e($tglMulai); ?>" required>
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted">Sampai:</span>
                        <input type="date" name="tgl_selesai" class="form-control" value="<?= e($tglSelesai); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- 5 Card Statistik Laporan -->
    <div class="row g-3 mb-4">
        <!-- Pendapatan Bersih (Net Sales) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Pendapatan Bersih (Omset)</div>
                <h3 class="fw-bold text-success mt-2 mb-0"><?= format_rupiah($pendapatanNet); ?></h3>
                <div class="small text-muted mt-1">Total pembayaran setelah diskon</div>
            </div>
        </div>

        <!-- Estimasi Laba Kotor -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Estimasi Keuntungan Kotor</div>
                <h3 class="fw-bold text-primary mt-2 mb-0"><?= format_rupiah($estimasiProfit); ?></h3>
                <div class="small text-muted mt-1">Omset dikurangi modal HPP barang</div>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Total Transaksi</div>
                <h3 class="fw-bold text-dark mt-2 mb-0"><?= number_format($totalTransaksi); ?></h3>
                <div class="small text-muted mt-1">Struk penjualan</div>
            </div>
        </div>

        <!-- Total Barang Terjual -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Barang Terjual</div>
                <h3 class="fw-bold text-dark mt-2 mb-0"><?= number_format($totalBarang); ?></h3>
                <div class="small text-muted mt-1">Pcs / Item fisik</div>
            </div>
        </div>

        <!-- Total Diskon Diberikan -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100 p-3">
                <div class="text-muted small fw-semibold text-uppercase">Total Diskon</div>
                <h3 class="fw-bold text-danger mt-2 mb-0"><?= format_rupiah($totalDiskon); ?></h3>
                <div class="small text-muted mt-1">Potongan harga</div>
            </div>
        </div>
    </div>

    <!-- Tabel Data Laporan Penjualan -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark"><i class="bi bi-table text-primary me-2"></i> Rincian Seluruh Transaksi Periode Ini</span>
            <span class="badge bg-secondary-subtle text-secondary"><?= count($laporanList); ?> Transaksi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>No. Transaksi</th>
                            <th>Tanggal & Jam</th>
                            <th>Petugas Kasir</th>
                            <th class="text-center">Jumlah Barang</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end">Total Akhir</th>
                            <th class="text-center">Metode</th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($laporanList)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                    Tidak ada transaksi penjualan pada rentang tanggal ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($laporanList as $row): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $no++; ?></td>
                                    <td>
                                        <a href="<?= base_url('admin/transaksi/detail.php?id=' . $row['id_penjualan']); ?>" class="fw-bold text-primary text-decoration-none">
                                            <?= e($row['nomor_transaksi']); ?>
                                        </a>
                                    </td>
                                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    <td><?= e($row['nama_kasir'] ?? 'Kasir'); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= (int)$row['qty_terjual']; ?> item</span>
                                    </td>
                                    <td class="text-end text-muted small"><?= format_rupiah($row['subtotal']); ?></td>
                                    <td class="text-end text-danger small"><?= $row['diskon'] > 0 ? '-' . format_rupiah($row['diskon']) : '-'; ?></td>
                                    <td class="text-end fw-bold text-success"><?= format_rupiah($row['total']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info border"><?= e($row['metode_pembayaran']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= base_url('kasir/struk.php?id=' . $row['id_penjualan']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak Struk">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="<?= base_url('admin/transaksi/detail.php?id=' . $row['id_penjualan']); ?>" class="btn btn-sm btn-outline-secondary" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">TOTAL KESELURUHAN:</td>
                            <td class="text-end text-muted"><?= format_rupiah($penjualanKotor); ?></td>
                            <td class="text-end text-danger"><?= format_rupiah($totalDiskon); ?></td>
                            <td class="text-end text-success fs-6"><?= format_rupiah($pendapatanNet); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

