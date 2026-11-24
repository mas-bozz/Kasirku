<?php
/**
 * Detail Produk - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID produk tidak valid.');
    header('Location: ' . base_url('admin/produk/index.php'));
    exit;
}

try {
    // Ambil detail produk beserta kategori
    $stmt = $pdo->prepare("
        SELECT p.*, k.nama_kategori 
        FROM produk p 
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
        WHERE p.id_produk = :id 
        LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
    $produk = $stmt->fetch();

    if (!$produk) {
        set_flash('danger', 'Produk tidak ditemukan.');
        header('Location: ' . base_url('admin/produk/index.php'));
        exit;
    }

    // Ambil riwayat pergerakan stok untuk produk ini
    $stmtMovements = $pdo->prepare("
        SELECT sm.*, u.nama AS nama_user 
        FROM stock_movements sm 
        LEFT JOIN users u ON sm.id_user = u.id_user 
        WHERE sm.id_produk = :id 
        ORDER BY sm.created_at DESC 
        LIMIT 10
    ");
    $stmtMovements->execute(['id' => $id]);
    $riwayatStok = $stmtMovements->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}

// Hitung margin keuntungan
$marginRp = $produk['harga_jual'] - $produk['harga_beli'];
$marginPersen = ($produk['harga_beli'] > 0) ? ($marginRp / $produk['harga_beli']) * 100 : 0;

$pageTitle = 'Detail Produk: ' . $produk['nama_produk'] . ' - KasirKu';
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
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/produk/index.php'); ?>">Produk</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($produk['kode_produk']); ?></li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark"><?= e($produk['nama_produk']); ?></h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/produk/index.php'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <a href="<?= base_url('admin/produk/edit.php?id=' . $produk['id_produk']); ?>" class="btn btn-primary">
                <i class="bi bi-pencil-square me-1"></i> Edit Data
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Info Produk & Gambar -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 text-center">
                    <?php if (!empty($produk['gambar']) && file_exists(__DIR__ . '/../../uploads/produk/' . $produk['gambar'])): ?>
                        <img src="<?= base_url('uploads/produk/' . $produk['gambar']); ?>" alt="<?= e($produk['nama_produk']); ?>" class="img-fluid rounded-4 shadow-sm border mb-3" style="max-height: 280px; object-fit: contain;">
                    <?php else: ?>
                        <div class="bg-light border rounded-4 py-5 text-muted mb-3 d-flex flex-column align-items-center justify-content-center" style="height: 220px;">
                            <i class="bi bi-image fs-1 mb-2"></i>
                            <span class="small">Tidak ada foto produk</span>
                        </div>
                    <?php endif; ?>

                    <h5 class="fw-bold text-dark mb-1"><?= e($produk['nama_produk']); ?></h5>
                    <div class="badge bg-light text-dark border fs-6 mb-3"><?= e($produk['kode_produk']); ?></div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 fs-6">
                            <?= e($produk['nama_kategori'] ?? 'Tanpa Kategori'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card Ringkasan Harga & Margin -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom fw-bold text-dark">
                    <i class="bi bi-cash-stack text-success me-2"></i> Informasi Finansial
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Harga Beli (Modal):</span>
                            <span class="fw-semibold text-dark"><?= format_rupiah($produk['harga_beli']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Harga Jual:</span>
                            <span class="fw-bold text-primary fs-6"><?= format_rupiah($produk['harga_jual']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Margin Keuntungan:</span>
                            <span class="fw-bold text-success">
                                <?= format_rupiah($marginRp); ?> (<?= number_format($marginPersen, 1); ?>%)
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Kolom Status Stok & Riwayat Mutasi Stok -->
        <div class="col-lg-7">
            <!-- Card Status Stok -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark"><i class="bi bi-box-seam me-2 text-primary"></i> Kondisi Stok Barang</span>
                    <a href="<?= base_url('admin/stok/index.php?id_produk=' . $produk['id_produk']); ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle me-1"></i> Sesuaikan Stok
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="row text-center g-3">
                        <div class="col-4 border-end">
                            <div class="text-muted small text-uppercase fw-semibold">Stok Saat Ini</div>
                            <h3 class="fw-bold mt-2 mb-0 <?= $produk['stok'] <= $produk['stok_minimum'] ? 'text-danger' : 'text-success'; ?>">
                                <?= (int)$produk['stok']; ?>
                            </h3>
                            <div class="small text-muted"><?= e($produk['satuan']); ?></div>
                        </div>
                        <div class="col-4 border-end">
                            <div class="text-muted small text-uppercase fw-semibold">Batas Minimum</div>
                            <h3 class="fw-bold mt-2 mb-0 text-dark"><?= (int)$produk['stok_minimum']; ?></h3>
                            <div class="small text-muted"><?= e($produk['satuan']); ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small text-uppercase fw-semibold">Status Stok</div>
                            <div class="mt-2">
                                <?php if ($produk['stok'] <= 0): ?>
                                    <span class="badge bg-danger fs-6 py-2 px-3">Habis</span>
                                <?php elseif ($produk['stok'] <= $produk['stok_minimum']): ?>
                                    <span class="badge bg-warning text-dark fs-6 py-2 px-3">Menipis</span>
                                <?php else: ?>
                                    <span class="badge bg-success fs-6 py-2 px-3">Aman</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Riwayat Mutasi Stok -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom fw-bold text-dark">
                    <i class="bi bi-clock-history text-secondary me-2"></i> 10 Riwayat Mutasi Stok Terakhir
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Waktu</th>
                                    <th>Jenis</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Sebelum & Sesudah</th>
                                    <th>Keterangan</th>
                                    <th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($riwayatStok)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            Belum ada catatan mutasi stok untuk produk ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($riwayatStok as $m): ?>
                                        <tr>
                                            <td class="text-muted"><?= date('d/m/Y H:i', strtotime($m['created_at'])); ?></td>
                                            <td>
                                                <?php if ($m['jenis'] === 'masuk'): ?>
                                                    <span class="badge bg-success-subtle text-success border">Masuk</span>
                                                <?php elseif ($m['jenis'] === 'keluar'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border">Keluar</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border">Penyesuaian</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center fw-bold">
                                                <?= ($m['jenis'] === 'masuk') ? '+' : (($m['jenis'] === 'keluar') ? '-' : ''); ?>
                                                <?= (int)$m['jumlah']; ?>
                                            </td>
                                            <td class="text-center text-muted">
                                                <?= (int)$m['stok_sebelum']; ?> &rarr; <span class="fw-semibold text-dark"><?= (int)$m['stok_sesudah']; ?></span>
                                            </td>
                                            <td><?= e($m['keterangan'] ?? '-'); ?></td>
                                            <td class="text-muted"><?= e($m['nama_user'] ?? 'Sistem'); ?></td>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

