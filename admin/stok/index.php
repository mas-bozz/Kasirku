<?php
/**
 * Riwayat & Manajemen Stok Barang - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

// Ambil parameter filter
$filterProduk = (int)($_GET['id_produk'] ?? 0);
$filterJenis  = trim($_GET['jenis'] ?? '');
$tglMulai     = trim($_GET['tgl_mulai'] ?? '');
$tglSelesai   = trim($_GET['tgl_selesai'] ?? '');

try {
    // 1. Ambil daftar produk untuk dropdown filter
    $stmtProdukList = $pdo->query("SELECT id_produk, kode_produk, nama_produk, stok, satuan FROM produk ORDER BY nama_produk ASC");
    $daftarProduk = $stmtProdukList->fetchAll();

    // 2. Query riwayat mutasi stok dengan filter dinamis
    $sql = "
        SELECT sm.*, p.kode_produk, p.nama_produk, p.satuan, u.nama AS nama_petugas 
        FROM stock_movements sm 
        JOIN produk p ON sm.id_produk = p.id_produk 
        LEFT JOIN users u ON sm.id_user = u.id_user 
        WHERE 1=1
    ";
    $params = [];

    if ($filterProduk > 0) {
        $sql .= " AND sm.id_produk = :id_produk";
        $params['id_produk'] = $filterProduk;
    }

    if (!empty($filterJenis) && in_array($filterJenis, ['masuk', 'keluar', 'penyesuaian'])) {
        $sql .= " AND sm.jenis = :jenis";
        $params['jenis'] = $filterJenis;
    }

    if (!empty($tglMulai)) {
        $sql .= " AND DATE(sm.created_at) >= :tgl_mulai";
        $params['tgl_mulai'] = $tglMulai;
    }

    if (!empty($tglSelesai)) {
        $sql .= " AND DATE(sm.created_at) <= :tgl_selesai";
        $params['tgl_selesai'] = $tglSelesai;
    }

    $sql .= " ORDER BY sm.created_at DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $riwayatStok = $stmt->fetchAll();

    // 3. Ringkasan Statistik Mutasi Bulan Ini
    $stmtStat = $pdo->query("
        SELECT 
            SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END) AS total_masuk,
            SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END) AS total_keluar,
            COUNT(CASE WHEN jenis = 'penyesuaian' THEN 1 END) AS total_penyesuaian 
        FROM stock_movements 
        WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    ");
    $statMutasi = $stmtStat->fetch();

} catch (PDOException $e) {
    die("Gagal memuat data stok: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Riwayat & Mutasi Stok - KasirKu';
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
                    <li class="breadcrumb-item active" aria-current="page">Manajemen Stok</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">Kartu Stok & Riwayat Mutasi</h4>
        </div>
        <div>
            <a href="<?= base_url('admin/stok/sesuaikan.php' . ($filterProduk > 0 ? '?id_produk=' . $filterProduk : '')); ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-arrow-left-right me-1"></i> Sesuaikan / Tambah Stok
            </a>
        </div>
    </div>

    <!-- Ringkasan Statistik Mutasi Bulan Ini -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-success border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Barang Masuk (Restock)</div>
                            <h3 class="fw-bold text-success mt-2 mb-0">+<?= number_format((int)($statMutasi['total_masuk'] ?? 0)); ?></h3>
                            <div class="small text-muted mt-1">Bulan: <?= date('F Y'); ?></div>
                        </div>
                        <div class="p-3 bg-success-subtle rounded-3 text-success">
                            <i class="bi bi-box-arrow-in-down fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-danger border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Barang Keluar</div>
                            <h3 class="fw-bold text-danger mt-2 mb-0">-<?= number_format((int)($statMutasi['total_keluar'] ?? 0)); ?></h3>
                            <div class="small text-muted mt-1">Termasuk penjualan & retur rusak</div>
                        </div>
                        <div class="p-3 bg-danger-subtle rounded-3 text-danger">
                            <i class="bi bi-box-arrow-up fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-info border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Penyesuaian Fisik (Opname)</div>
                            <h3 class="fw-bold text-info mt-2 mb-0"><?= number_format((int)($statMutasi['total_penyesuaian'] ?? 0)); ?> <span class="fs-6 text-muted fw-normal">kali</span></h3>
                            <div class="small text-muted mt-1">Koreksi audit fisik toko</div>
                        </div>
                        <div class="p-3 bg-info-subtle rounded-3 text-info">
                            <i class="bi bi-sliders fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Riwayat Mutasi -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <!-- Filter Berdasarkan Produk -->
                <div class="col-md-4">
                    <select name="id_produk" class="form-select">
                        <option value="0">-- Semua Produk --</option>
                        <?php foreach ($daftarProduk as $p): ?>
                            <option value="<?= $p['id_produk']; ?>" <?= $filterProduk == $p['id_produk'] ? 'selected' : ''; ?>>
                                [<?= e($p['kode_produk']); ?>] <?= e($p['nama_produk']); ?> (Sisa: <?= $p['stok']; ?> <?= e($p['satuan']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Berdasarkan Jenis -->
                <div class="col-md-2">
                    <select name="jenis" class="form-select">
                        <option value="">-- Semua Jenis --</option>
                        <option value="masuk" <?= $filterJenis === 'masuk' ? 'selected' : ''; ?>>Masuk</option>
                        <option value="keluar" <?= $filterJenis === 'keluar' ? 'selected' : ''; ?>>Keluar</option>
                        <option value="penyesuaian" <?= $filterJenis === 'penyesuaian' ? 'selected' : ''; ?>>Penyesuaian</option>
                    </select>
                </div>

                <!-- Tanggal Mulai -->
                <div class="col-md-2">
                    <input type="date" name="tgl_mulai" class="form-control" value="<?= e($tglMulai); ?>" title="Dari Tanggal">
                </div>

                <!-- Tanggal Selesai -->
                <div class="col-md-2">
                    <input type="date" name="tgl_selesai" class="form-control" value="<?= e($tglSelesai); ?>" title="Sampai Tanggal">
                </div>

                <!-- Tombol Submit & Reset -->
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if ($filterProduk > 0 || !empty($filterJenis) || !empty($tglMulai) || !empty($tglSelesai)): ?>
                        <a href="<?= base_url('admin/stok/index.php'); ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat Mutasi Stok -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark"><i class="bi bi-clock-history text-primary me-2"></i> Log Aktivitas Perubahan Stok Barang</span>
            <span class="badge bg-secondary-subtle text-secondary">Maks. 100 data terbaru</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Waktu & Tanggal</th>
                            <th>Produk</th>
                            <th class="text-center">Jenis</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-center">Sebelum &rarr; Sesudah</th>
                            <th>Keterangan / Alasan</th>
                            <th>Petugas / Kasir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riwayatStok)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Tidak ada catatan mutasi stok yang sesuai dengan filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($riwayatStok as $row): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $no++; ?></td>
                                    <td class="small text-muted">
                                        <div><?= date('d/m/Y', strtotime($row['created_at'])); ?></div>
                                        <div class="text-secondary"><?= date('H:i:s', strtotime($row['created_at'])); ?> WIB</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($row['nama_produk']); ?></div>
                                        <span class="badge bg-light text-dark border small"><?= e($row['kode_produk']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['jenis'] === 'masuk'): ?>
                                            <span class="badge bg-success-subtle text-success border px-2 py-1">
                                                <i class="bi bi-arrow-down-left me-1"></i> Masuk
                                            </span>
                                        <?php elseif ($row['jenis'] === 'keluar'): ?>
                                            <span class="badge bg-danger-subtle text-danger border px-2 py-1">
                                                <i class="bi bi-arrow-up-right me-1"></i> Keluar
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info border px-2 py-1">
                                                <i class="bi bi-arrow-repeat me-1"></i> Penyesuaian
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <?php if ($row['jenis'] === 'masuk'): ?>
                                            <span class="text-success">+<?= (int)$row['jumlah']; ?></span>
                                        <?php elseif ($row['jenis'] === 'keluar'): ?>
                                            <span class="text-danger">-<?= (int)$row['jumlah']; ?></span>
                                        <?php else: ?>
                                            <span class="text-info"><?= (int)$row['jumlah']; ?></span>
                                        <?php endif; ?>
                                        <span class="small text-muted fw-normal"><?= e($row['satuan']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-muted border"><?= (int)$row['stok_sebelum']; ?></span>
                                        <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                        <span class="badge bg-primary-subtle text-primary border"><?= (int)$row['stok_sesudah']; ?></span>
                                    </td>
                                    <td>
                                        <?= e($row['keterangan'] ?? '-'); ?>
                                    </td>
                                    <td>
                                        <span class="small fw-semibold text-secondary">
                                            <i class="bi bi-person me-1"></i><?= e($row['nama_petugas'] ?? 'Sistem'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 text-muted small">
            Menampilkan <strong><?= count($riwayatStok); ?></strong> data riwayat mutasi stok
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

