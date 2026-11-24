<?php
/**
 * Halaman Cetak Struk Penjualan - KasirKu
 * Kompatibel dengan Printer Thermal 58mm / 80mm & Printer Kertas Biasa
 */
require_once __DIR__ . '/../includes/kasir_auth.php';

$idPenjualan = (int)($_GET['id'] ?? 0);
if ($idPenjualan <= 0) {
    set_flash('danger', 'ID transaksi tidak valid.');
    header('Location: ' . base_url('kasir/transaksi.php'));
    exit;
}

try {
    // 1. Ambil data penjualan beserta nama kasir
    $stmtTrx = $pdo->prepare("
        SELECT p.*, u.nama AS nama_kasir, u.username AS username_kasir 
        FROM penjualans p 
        LEFT JOIN users u ON p.id_user = u.id_user 
        WHERE p.id_penjualan = :id 
        LIMIT 1
    ");
    $stmtTrx->execute(['id' => $idPenjualan]);
    $trx = $stmtTrx->fetch();

    if (!$trx) {
        set_flash('danger', 'Data transaksi penjualan tidak ditemukan.');
        header('Location: ' . base_url('kasir/transaksi.php'));
        exit;
    }

    // Jika role adalah kasir, pastikan hanya bisa melihat transaksinya sendiri
    if ($_SESSION['role'] === 'kasir' && $trx['id_user'] != $_SESSION['user_id']) {
        set_flash('danger', 'Akses ditolak! Anda hanya dapat mencetak struk transaksi milik Anda.');
        header('Location: ' . base_url('kasir/riwayat.php'));
        exit;
    }

    // 2. Ambil detail barang yang dibeli
    $stmtDetail = $pdo->prepare("
        SELECT d.*, pr.kode_produk, pr.nama_produk, pr.satuan 
        FROM detail_penjualan d 
        JOIN produk pr ON d.id_produk = pr.id_produk 
        WHERE d.id_penjualan = :id 
        ORDER BY d.id_detail ASC
    ");
    $stmtDetail->execute(['id' => $idPenjualan]);
    $items = $stmtDetail->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan memuat struk: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Struk Pembayaran: ' . $trx['nomor_transaksi'] . ' - KasirKu';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    
    <!-- Tombol Aksi Navigasi (Disembunyikan saat dicetak) -->
    <div class="row justify-content-center mb-4 no-print">
        <div class="col-md-6 col-lg-5 text-center">
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-primary px-3 shadow-sm" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Cetak Struk
                </button>
                <a href="<?= base_url('kasir/transaksi.php'); ?>" class="btn btn-success px-3 shadow-sm">
                    <i class="bi bi-cart-plus me-1"></i> Transaksi Baru
                </a>
                <a href="<?= base_url($_SESSION['role'] === 'admin' ? 'admin/transaksi/index.php' : 'kasir/riwayat.php'); ?>" class="btn btn-outline-secondary px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- AREA STRUK (Cocok untuk thermal 58mm/80mm) -->
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border shadow-sm p-4 bg-white" id="thermal-receipt" style="font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.4;">
                
                <!-- Header Toko -->
                <div class="text-center mb-3">
                    <h5 class="fw-bold mb-0 text-dark" style="font-family: inherit;">KASIRKU</h5>
                    <div class="small">Sistem Informasi Toko Kasir</div>
                    <div class="small text-muted">Jl. Raya Toko KasirKu No. 88</div>
                    <div class="small text-muted">Telp / WhatsApp: 0812-3456-7890</div>
                </div>

                <div class="border-top border-bottom border-dashed py-2 my-2 small">
                    <div class="d-flex justify-content-between">
                        <span>No. Transaksi:</span>
                        <span class="fw-bold"><?= e($trx['nomor_transaksi']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Tanggal:</span>
                        <span><?= date('d/m/Y H:i:s', strtotime($trx['tanggal'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Kasir:</span>
                        <span><?= e($trx['nama_kasir'] ?? 'Kasir'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Metode:</span>
                        <span><?= strtoupper(e($trx['metode_pembayaran'])); ?></span>
                    </div>
                </div>

                <!-- Daftar Barang Belanjaan -->
                <div class="py-2">
                    <table class="w-100" style="border-collapse: collapse;">
                        <thead>
                            <tr class="border-bottom">
                                <th class="text-start pb-1">Item</th>
                                <th class="text-center pb-1">Qty</th>
                                <th class="text-end pb-1">Harga</th>
                                <th class="text-end pb-1">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td colspan="4" class="pt-2 fw-bold text-dark">
                                        <?= e($it['nama_produk']); ?>
                                    </td>
                                </tr>
                                <tr class="border-bottom border-light">
                                    <td class="text-muted small ps-2">[<?= e($it['kode_produk']); ?>]</td>
                                    <td class="text-center"><?= (int)$it['jumlah']; ?></td>
                                    <td class="text-end"><?= number_format($it['harga'], 0, ',', '.'); ?></td>
                                    <td class="text-end fw-semibold"><?= number_format($it['subtotal'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Rincian Pembayaran -->
                <div class="border-top border-dashed pt-2 mt-2">
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span><?= format_rupiah($trx['subtotal']); ?></span>
                    </div>
                    <?php if ($trx['diskon'] > 0): ?>
                        <div class="d-flex justify-content-between text-danger">
                            <span>Diskon:</span>
                            <span>-<?= format_rupiah($trx['diskon']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between fw-bold fs-6 my-1 border-top border-bottom py-1">
                        <span>TOTAL:</span>
                        <span><?= format_rupiah($trx['total']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Bayar (<?= e($trx['metode_pembayaran']); ?>):</span>
                        <span><?= format_rupiah($trx['pembayaran']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold text-success">
                        <span>Kembalian:</span>
                        <span><?= format_rupiah($trx['kembalian']); ?></span>
                    </div>
                </div>

                <!-- Pesan Penutup Struk -->
                <div class="text-center mt-4 pt-2 border-top border-dashed small">
                    <p class="mb-1 fw-bold">TERIMA KASIH ATAS KUNJUNGAN ANDA!</p>
                    <p class="text-muted mb-0" style="font-size: 11px;">Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.</p>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.border-dashed {
    border-style: dashed !important;
}

@media print {
    body {
        background-color: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .no-print, nav, footer {
        display: none !important;
    }
    .container {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #thermal-receipt {
        border: none !important;
        box-shadow: none !important;
        width: 100% !important;
        max-width: 300px !important;
        margin: 0 auto !important;
        padding: 5px !important;
    }
}
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

