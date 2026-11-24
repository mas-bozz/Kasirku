<?php
/**
 * Master Data Kategori - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$search = trim($_GET['q'] ?? '');

try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT k.*, COUNT(p.id_produk) AS total_produk 
            FROM kategori k 
            LEFT JOIN produk p ON k.id_kategori = p.id_kategori 
            WHERE k.nama_kategori LIKE :search 
            GROUP BY k.id_kategori 
            ORDER BY k.nama_kategori ASC
        ");
        $stmt->execute(['search' => "%$search%"]);
    } else {
        $stmt = $pdo->query("
            SELECT k.*, COUNT(p.id_produk) AS total_produk 
            FROM kategori k 
            LEFT JOIN produk p ON k.id_kategori = p.id_kategori 
            GROUP BY k.id_kategori 
            ORDER BY k.nama_kategori ASC
        ");
    }
    $kategoriList = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Kelola Kategori - KasirKu';
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
                    <li class="breadcrumb-item active" aria-current="page">Kategori</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">Data Kategori Produk</h4>
        </div>
        <div>
            <a href="<?= base_url('admin/kategori/tambah.php'); ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Kategori
            </a>
        </div>
    </div>

    <!-- Card Pencarian & Tabel -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cari nama kategori..." value="<?= e($search); ?>">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= base_url('admin/kategori/index.php'); ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;" class="text-center">No</th>
                            <th>Nama Kategori</th>
                            <th class="text-center">Jumlah Produk Terkait</th>
                            <th>Tanggal Dibuat</th>
                            <th class="text-end" style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kategoriList)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-tag fs-3 d-block mb-1"></i>
                                    Tidak ada data kategori yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($kategoriList as $k): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $no++; ?></td>
                                    <td class="fw-semibold text-dark">
                                        <i class="bi bi-tag-fill text-primary me-2"></i>
                                        <?= e($k['nama_kategori']); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($k['total_produk'] > 0): ?>
                                            <span class="badge bg-primary-subtle text-primary border px-2 py-1">
                                                <?= (int)$k['total_produk']; ?> Produk
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-2 py-1">
                                                0 Produk
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= date('d M Y H:i', strtotime($k['created_at'])); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('admin/kategori/edit.php?id=' . $k['id_kategori']); ?>" class="btn btn-outline-primary" title="Edit Kategori">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                            <?php if ($k['total_produk'] > 0): ?>
                                                <button type="button" class="btn btn-outline-secondary" onclick="alert('Kategori \'<?= e($k['nama_kategori']); ?>\' tidak dapat dihapus karena masih digunakan oleh <?= $k['total_produk']; ?> produk! Hapus atau pindahkan produk terkait terlebih dahulu.');" title="Kategori sedang digunakan">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <a href="<?= base_url('admin/kategori/hapus.php?id=' . $k['id_kategori']); ?>" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori \'<?= e($k['nama_kategori']); ?>\'?');" title="Hapus Kategori">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            <?php endif; ?>
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
            Menampilkan <strong><?= count($kategoriList); ?></strong> kategori
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

