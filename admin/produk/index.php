<?php
/**
 * Master Data Produk - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$search = trim($_GET['q'] ?? '');
$filterKategori = (int)($_GET['kategori'] ?? 0);

try {
    // Ambil daftar kategori untuk dropdown filter
    $stmtKategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $kategoriList = $stmtKategori->fetchAll();

    // Query produk dengan pencarian & filter kategori
    $sql = "
        SELECT p.*, k.nama_kategori 
        FROM produk p 
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori 
        WHERE 1=1
    ";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (p.kode_produk LIKE :search OR p.nama_produk LIKE :search)";
        $params['search'] = "%$search%";
    }

    if ($filterKategori > 0) {
        $sql .= " AND p.id_kategori = :kategori";
        $params['kategori'] = $filterKategori;
    }

    $sql .= " ORDER BY p.id_produk DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produkList = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Kelola Produk - KasirKu';
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
                    <li class="breadcrumb-item active" aria-current="page">Produk</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">Data Master Produk</h4>
        </div>
        <div>
            <a href="<?= base_url('admin/produk/tambah.php'); ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Produk Baru
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cari kode atau nama produk..." value="<?= e($search); ?>">
                    </div>
                </div>

                <!-- Dropdown Filter Kategori -->
                <div class="col-md-4">
                    <select name="kategori" class="form-select">
                        <option value="0">-- Semua Kategori --</option>
                        <?php foreach ($kategoriList as $kat): ?>
                            <option value="<?= $kat['id_kategori']; ?>" <?= $filterKategori == $kat['id_kategori'] ? 'selected' : ''; ?>>
                                <?= e($kat['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tombol Submit & Reset -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if (!empty($search) || $filterKategori > 0): ?>
                        <a href="<?= base_url('admin/produk/index.php'); ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data Produk -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th style="width: 70px;">Gambar</th>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th class="text-end">Harga Beli</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center">Satuan</th>
                            <th class="text-end" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($produkList)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
                                    Tidak ada produk yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($produkList as $p): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $no++; ?></td>
                                    <td>
                                        <?php if (!empty($p['gambar']) && file_exists(__DIR__ . '/../../uploads/produk/' . $p['gambar'])): ?>
                                            <img src="<?= base_url('uploads/produk/' . $p['gambar']); ?>" alt="<?= e($p['nama_produk']); ?>" class="rounded border object-fit-cover" style="width: 48px; height: 48px;">
                                        <?php else: ?>
                                            <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-medium"><?= e($p['kode_produk']); ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('admin/produk/detail.php?id=' . $p['id_produk']); ?>" class="fw-bold text-dark text-decoration-none">
                                            <?= e($p['nama_produk']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary"><?= e($p['nama_kategori'] ?? '-'); ?></span>
                                    </td>
                                    <td class="text-end text-muted small"><?= format_rupiah($p['harga_beli']); ?></td>
                                    <td class="text-end fw-semibold text-primary"><?= format_rupiah($p['harga_jual']); ?></td>
                                    <td class="text-center">
                                        <?php if ($p['stok'] <= $p['stok_minimum']): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" title="Stok Menipis (Min: <?= $p['stok_minimum']; ?>)">
                                                <i class="bi bi-exclamation-circle me-1"></i><?= (int)$p['stok']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <?= (int)$p['stok']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-muted small"><?= e($p['satuan']); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('admin/produk/detail.php?id=' . $p['id_produk']); ?>" class="btn btn-outline-secondary" title="Detail Produk">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= base_url('admin/produk/edit.php?id=' . $p['id_produk']); ?>" class="btn btn-outline-primary" title="Edit Produk">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="<?= base_url('admin/produk/hapus.php?id=' . $p['id_produk']); ?>" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus produk \'<?= e($p['nama_produk']); ?>\'?');" title="Hapus Produk">
                                                <i class="bi bi-trash"></i>
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
            Menampilkan <strong><?= count($produkList); ?></strong> produk
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

