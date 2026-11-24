<?php
/**
 * Laporan Analisis Produk & Nilai Stok - KasirKu (Administrator)
 * Dilengkapi Visualisasi Grafik Chart.js
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

try {
    // 1. Nilai Aset Stok Toko (Valuasi Modal & Potensi Omset)
    $stmtValuasi = $pdo->query("
        SELECT 
            COUNT(*) AS total_sku,
            COALESCE(SUM(stok), 0) AS total_unit_stok,
            COALESCE(SUM(stok * harga_beli), 0) AS total_nilai_modal,
            COALESCE(SUM(stok * harga_jual), 0) AS total_nilai_jual
        FROM produk
    ");
    $valuasi = $stmtValuasi->fetch();

    $nilaiModal   = (float)$valuasi['total_nilai_modal'];
    $nilaiJual    = (float)$valuasi['total_nilai_jual'];
    $potensiLaba  = $nilaiJual - $nilaiModal;

    // 2. Produk Terlaris (Top 5 Best Seller)
    $stmtTop = $pdo->query("
        SELECT pr.id_produk, pr.kode_produk, pr.nama_produk, pr.satuan, k.nama_kategori,
               COALESCE(SUM(d.jumlah), 0) AS total_terjual,
               COALESCE(SUM(d.subtotal), 0) AS total_omset
        FROM produk pr
        JOIN detail_penjualan d ON pr.id_produk = d.id_produk
        LEFT JOIN kategori k ON pr.id_kategori = k.id_kategori
        GROUP BY pr.id_produk
        ORDER BY total_terjual DESC
        LIMIT 5
    ");
    $topProducts = $stmtTop->fetchAll();

    // 3. Produk Paling Sedikit Terjual / Slow Moving (Bottom 5)
    $stmtSlow = $pdo->query("
        SELECT pr.id_produk, pr.kode_produk, pr.nama_produk, pr.stok, pr.satuan, k.nama_kategori,
               COALESCE(SUM(d.jumlah), 0) AS total_terjual
        FROM produk pr
        LEFT JOIN detail_penjualan d ON pr.id_produk = d.id_produk
        LEFT JOIN kategori k ON pr.id_kategori = k.id_kategori
        GROUP BY pr.id_produk
        ORDER BY total_terjual ASC, pr.stok DESC
        LIMIT 5
    ");
    $slowProducts = $stmtSlow->fetchAll();

    // 4. Data Tren Penjualan 7 Hari Terakhir untuk Chart.js
    $stmtChart = $pdo->query("
        SELECT DATE(tanggal) AS tgl, 
               COALESCE(SUM(total), 0) AS omset, 
               COUNT(*) AS jml_trx 
        FROM penjualans 
        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(tanggal) 
        ORDER BY tgl ASC
    ");
    $chartRows = $stmtChart->fetchAll();

    // Format data untuk Chart.js
    $chartLabels = [];
    $chartOmset  = [];
    $chartTrx    = [];

    // Buat array 7 hari terakhir lengkap (bahkan jika ada hari yang omsetnya 0)
    for ($i = 6; $i >= 0; $i--) {
        $tglKey = date('Y-m-d', strtotime("-$i days"));
        $tglLabel = date('d M', strtotime($tglKey));
        $chartLabels[] = $tglLabel;

        $found = false;
        foreach ($chartRows as $cr) {
            if ($cr['tgl'] === $tglKey) {
                $chartOmset[] = (float)$cr['omset'];
                $chartTrx[]   = (int)$cr['jml_trx'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $chartOmset[] = 0;
            $chartTrx[]   = 0;
        }
    }

} catch (PDOException $e) {
    die("Terjadi kesalahan memuat data: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Laporan Produk & Valuasi Stok - KasirKu';
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
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/laporan/index.php'); ?>">Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Analisis Produk</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">Laporan Analisis Produk & Nilai Stok</h4>
            <p class="text-muted small mb-0">Informasi produk terlaris, barang slow-moving, valuasi modal persediaan, dan grafik tren.</p>
        </div>
        <div>
            <a href="<?= base_url('admin/laporan/index.php'); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Laporan Penjualan
            </a>
        </div>
    </div>

    <!-- 4 Card Ringkasan Nilai Aset Stok -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4 p-3 h-100">
                <div class="text-muted small fw-semibold text-uppercase">Total Modal Stok (HPP)</div>
                <h3 class="fw-bold text-dark mt-2 mb-0"><?= format_rupiah($nilaiModal); ?></h3>
                <div class="small text-muted mt-1">Nilai aset modal saat ini</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-success border-4 p-3 h-100">
                <div class="text-muted small fw-semibold text-uppercase">Potensi Nilai Jual</div>
                <h3 class="fw-bold text-success mt-2 mb-0"><?= format_rupiah($nilaiJual); ?></h3>
                <div class="small text-muted mt-1">Jika seluruh stok habis terjual</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-info border-4 p-3 h-100">
                <div class="text-muted small fw-semibold text-uppercase">Potensi Keuntungan Bersih</div>
                <h3 class="fw-bold text-info mt-2 mb-0"><?= format_rupiah($potensiLaba); ?></h3>
                <div class="small text-muted mt-1">Margin laba kotor persediaan</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4 p-3 h-100">
                <div class="text-muted small fw-semibold text-uppercase">Total Unit Fisik Barang</div>
                <h3 class="fw-bold text-dark mt-2 mb-0"><?= number_format((int)$valuasi['total_unit_stok']); ?> <span class="fs-6 text-muted fw-normal">unit</span></h3>
                <div class="small text-muted mt-1"><?= (int)$valuasi['total_sku']; ?> macam SKU produk</div>
            </div>
        </div>
    </div>

    <!-- Grafik Tren Penjualan 7 Hari Terakhir -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark"><i class="bi bi-graph-up text-primary me-2"></i> Grafik Tren Omset Penjualan (7 Hari Terakhir)</span>
            <span class="badge bg-primary-subtle text-primary">Live Chart.js</span>
        </div>
        <div class="card-body p-3">
            <div style="height: 280px;">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tabel Produk Terlaris (Best Seller) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-success"><i class="bi bi-trophy-fill me-2"></i> Top 5 Produk Terlaris</span>
                    <span class="badge bg-success-subtle text-success">Best Seller</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">#</th>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Terjual</th>
                                    <th class="text-end">Total Omset</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topProducts)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi barang terjual.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $rank = 1; foreach ($topProducts as $tp): ?>
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge rounded-circle <?= $rank === 1 ? 'bg-warning text-dark' : 'bg-light text-dark border'; ?>" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;">
                                                    <?= $rank++; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('admin/produk/detail.php?id=' . $tp['id_produk']); ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= e($tp['nama_produk']); ?>
                                                </a>
                                                <div class="small text-muted"><?= e($tp['kode_produk']); ?></div>
                                            </td>
                                            <td><span class="badge bg-secondary-subtle text-secondary small"><?= e($tp['nama_kategori'] ?? '-'); ?></span></td>
                                            <td class="text-center fw-bold text-primary">
                                                <?= (int)$tp['total_terjual']; ?> <span class="small text-muted fw-normal"><?= e($tp['satuan']); ?></span>
                                            </td>
                                            <td class="text-end fw-bold text-success"><?= format_rupiah($tp['total_omset']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Produk Slow Moving (Sedikit Terjual) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-warning text-dark"><i class="bi bi-hourglass-split me-2 text-warning"></i> 5 Produk Paling Sedikit Terjual</span>
                    <span class="badge bg-warning-subtle text-dark">Slow Moving</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">#</th>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Terjual</th>
                                    <th class="text-center">Sisa Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($slowProducts)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Tidak ada data.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $rank = 1; foreach ($slowProducts as $sp): ?>
                                        <tr>
                                            <td class="text-center text-muted"><?= $rank++; ?></td>
                                            <td>
                                                <a href="<?= base_url('admin/produk/detail.php?id=' . $sp['id_produk']); ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= e($sp['nama_produk']); ?>
                                                </a>
                                                <div class="small text-muted"><?= e($sp['kode_produk']); ?></div>
                                            </td>
                                            <td><span class="badge bg-secondary-subtle text-secondary small"><?= e($sp['nama_kategori'] ?? '-'); ?></span></td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border"><?= (int)$sp['total_terjual']; ?> <?= e($sp['satuan']); ?></span>
                                            </td>
                                            <td class="text-center fw-semibold text-secondary">
                                                <?= (int)$sp['stok']; ?> <?= e($sp['satuan']); ?>
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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesTrendChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Omset Penjualan (Rp)',
                data: <?= json_encode($chartOmset); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#0d6efd',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' Omset: Rp ' + Number(context.raw).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + Number(value).toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

