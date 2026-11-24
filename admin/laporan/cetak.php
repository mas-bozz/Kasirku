<?php
/**
 * Cetak Laporan Penjualan (Print View) - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$tglMulai   = trim($_GET['tgl_mulai'] ?? date('Y-m-01'));
$tglSelesai = trim($_GET['tgl_selesai'] ?? date('Y-m-d'));

try {
    // 1. Ringkasan
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

    $pendapatanNet  = (float)$summary['pendapatan_bersih'];
    $totalHPP       = (float)$summary['total_hpp_modal'];
    $estimasiProfit = $pendapatanNet - $totalHPP;

    // 2. Daftar Transaksi
    $sqlTrx = "
        SELECT p.*, u.nama AS nama_kasir, 
               COUNT(d.id_detail) AS macam_item, 
               SUM(d.jumlah) AS qty_terjual 
        FROM penjualans p 
        LEFT JOIN users u ON p.id_user = u.id_user 
        LEFT JOIN detail_penjualan d ON p.id_penjualan = d.id_penjualan 
        WHERE DATE(p.tanggal) BETWEEN :tgl_mulai AND :tgl_selesai 
        GROUP BY p.id_penjualan 
        ORDER BY p.tanggal ASC
    ";
    $stmtTrx = $pdo->prepare($sqlTrx);
    $stmtTrx->execute(['tgl_mulai' => $tglMulai, 'tgl_selesai' => $tglSelesai]);
    $laporanList = $stmtTrx->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Penjualan - KasirKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; background: #fff; }
        .table th, .table td { padding: 6px 8px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body class="p-4">

    <!-- Tombol Navigasi Cetak -->
    <div class="no-print mb-4 d-flex justify-content-between align-items-center">
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm px-3">
                Cetak Sekarang
            </button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm px-3">
                Tutup Jendela
            </button>
        </div>
        <span class="text-muted small">Disarankan menggunakan orientasi Landscape jika kolom rapat.</span>
    </div>

    <!-- Kop / Header Laporan -->
    <div class="text-center mb-4 pb-2 border-bottom">
        <h3 class="fw-bold mb-1">KASIRKU - SISTEM INFORMASI TOKO</h3>
        <p class="mb-1 text-muted small">Jl. Raya Toko KasirKu No. 88, Telp/WA: 0812-3456-7890</p>
        <h5 class="fw-bold mt-3 mb-1">LAPORAN PENJUALAN & PENDAPATAN</h5>
        <p class="text-secondary small mb-0">
            Periode: <strong><?= date('d/m/Y', strtotime($tglMulai)); ?></strong> s/d <strong><?= date('d/m/Y', strtotime($tglSelesai)); ?></strong>
        </p>
    </div>

    <!-- Ringkasan Finansial -->
    <div class="row g-2 mb-3">
        <div class="col-3">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block">Total Transaksi</small>
                <strong><?= number_format((int)$summary['total_transaksi']); ?> Transaksi</strong>
            </div>
        </div>
        <div class="col-3">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block">Barang Terjual</small>
                <strong><?= number_format((int)$summary['total_barang_terjual']); ?> Item</strong>
            </div>
        </div>
        <div class="col-3">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block">Total Pendapatan Bersih</small>
                <strong class="text-success"><?= format_rupiah($pendapatanNet); ?></strong>
            </div>
        </div>
        <div class="col-3">
            <div class="p-2 border rounded bg-light">
                <small class="text-muted d-block">Estimasi Laba Kotor</small>
                <strong class="text-primary"><?= format_rupiah($estimasiProfit); ?></strong>
            </div>
        </div>
    </div>

    <!-- Tabel Data Transaksi -->
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th style="width: 40px;" class="text-center">No</th>
                <th>No. Transaksi</th>
                <th>Tanggal & Jam</th>
                <th>Kasir</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">Diskon</th>
                <th class="text-end">Total Akhir</th>
                <th class="text-center">Metode</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($laporanList)): ?>
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">Tidak ada data transaksi pada periode ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($laporanList as $r): ?>
                    <tr>
                        <td class="text-center"><?= $no++; ?></td>
                        <td class="fw-semibold"><?= e($r['nomor_transaksi']); ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['tanggal'])); ?></td>
                        <td><?= e($r['nama_kasir'] ?? 'Kasir'); ?></td>
                        <td class="text-center"><?= (int)$r['qty_terjual']; ?></td>
                        <td class="text-end"><?= number_format($r['subtotal'], 0, ',', '.'); ?></td>
                        <td class="text-end text-danger"><?= $r['diskon'] > 0 ? '-' . number_format($r['diskon'], 0, ',', '.') : '-'; ?></td>
                        <td class="text-end fw-bold"><?= number_format($r['total'], 0, ',', '.'); ?></td>
                        <td class="text-center"><?= e($r['metode_pembayaran']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="4" class="text-end">TOTAL:</td>
                <td class="text-center"><?= number_format((int)$summary['total_barang_terjual']); ?></td>
                <td class="text-end"><?= number_format((float)$summary['total_penjualan_kotor'], 0, ',', '.'); ?></td>
                <td class="text-end text-danger"><?= number_format((float)$summary['total_diskon'], 0, ',', '.'); ?></td>
                <td class="text-end text-success"><?= number_format($pendapatanNet, 0, ',', '.'); ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Tanda Tangan Penanggung Jawab -->
    <div class="row mt-5 pt-3">
        <div class="col-8"></div>
        <div class="col-4 text-center">
            <p class="mb-1">Dicetak pada: <?= date('d/m/Y H:i'); ?> WIB</p>
            <p class="mb-5">Penanggung Jawab Toko,</p>
            <br><br>
            <strong class="border-bottom pb-1 px-3"><?= e($_SESSION['nama']); ?></strong>
            <div class="small text-muted mt-1">Administrator KasirKu</div>
        </div>
    </div>

</body>
</html>

