<?php
/**
 * Export Laporan Penjualan ke Format Excel (.xls) - KasirKu
 * PHP Native tanpa dependency eksternal
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$tglMulai   = trim($_GET['tgl_mulai'] ?? date('Y-m-01'));
$tglSelesai = trim($_GET['tgl_selesai'] ?? date('Y-m-d'));

try {
    // Ambil data transaksi
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
    die("Gagal mengekspor data: " . htmlspecialchars($e->getMessage()));
}

$filename = "Laporan_Penjualan_" . $tglMulai . "_sd_" . $tglSelesai . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
</head>
<body>
    <table border="1">
        <tr>
            <th colspan="9" style="font-size: 16px; font-weight: bold; text-align: center; height: 35px;">
                LAPORAN PENJUALAN TOKO KASIRKU
            </th>
        </tr>
        <tr>
            <th colspan="9" style="text-align: center;">
                Periode: <?= $tglMulai; ?> s/d <?= $tglSelesai; ?>
            </th>
        </tr>
        <tr></tr>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <th>No</th>
            <th>No. Transaksi</th>
            <th>Tanggal</th>
            <th>Kasir</th>
            <th>Qty Barang</th>
            <th>Subtotal (Rp)</th>
            <th>Diskon (Rp)</th>
            <th>Total Bayar (Rp)</th>
            <th>Metode Pembayaran</th>
        </tr>
        <?php 
        $no = 1;
        $totalQty = 0;
        $totalSubtotal = 0;
        $totalDiskon = 0;
        $grandTotal = 0;

        foreach ($laporanList as $row): 
            $totalQty += (int)$row['qty_terjual'];
            $totalSubtotal += (float)$row['subtotal'];
            $totalDiskon += (float)$row['diskon'];
            $grandTotal += (float)$row['total'];
        ?>
            <tr>
                <td style="text-align: center;"><?= $no++; ?></td>
                <td>'<?= e($row['nomor_transaksi']); ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                <td><?= e($row['nama_kasir'] ?? 'Kasir'); ?></td>
                <td style="text-align: center;"><?= (int)$row['qty_terjual']; ?></td>
                <td style="text-align: right;"><?= (float)$row['subtotal']; ?></td>
                <td style="text-align: right;"><?= (float)$row['diskon']; ?></td>
                <td style="text-align: right;"><?= (float)$row['total']; ?></td>
                <td style="text-align: center;"><?= e($row['metode_pembayaran']); ?></td>
            </tr>
        <?php endforeach; ?>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <td colspan="4" style="text-align: right;">TOTAL:</td>
            <td style="text-align: center;"><?= $totalQty; ?></td>
            <td style="text-align: right;"><?= $totalSubtotal; ?></td>
            <td style="text-align: right;"><?= $totalDiskon; ?></td>
            <td style="text-align: right;"><?= $grandTotal; ?></td>
            <td></td>
        </tr>
    </table>
</body>
</html>

