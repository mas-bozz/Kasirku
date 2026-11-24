<?php
/**
 * Edit Kategori - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID kategori tidak valid.');
    header('Location: ' . base_url('admin/kategori/index.php'));
    exit;
}

// Ambil data kategori
try {
    $stmt = $pdo->prepare("SELECT * FROM kategori WHERE id_kategori = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $kategori = $stmt->fetch();

    if (!$kategori) {
        set_flash('danger', 'Data kategori tidak ditemukan.');
        header('Location: ' . base_url('admin/kategori/index.php'));
        exit;
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}

$error = '';
$nama_kategori = $kategori['nama_kategori'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan ulangi kembali.';
    } else {
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');

        if (empty($nama_kategori)) {
            $error = 'Nama kategori wajib diisi!';
        } else {
            try {
                // Cek duplikasi dengan kategori lain
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE LOWER(nama_kategori) = LOWER(:nama) AND id_kategori != :id");
                $stmtCheck->execute(['nama' => $nama_kategori, 'id' => $id]);

                if ($stmtCheck->fetchColumn() > 0) {
                    $error = 'Kategori dengan nama "' . e($nama_kategori) . '" sudah digunakan!';
                } else {
                    $stmtUpdate = $pdo->prepare("UPDATE kategori SET nama_kategori = :nama WHERE id_kategori = :id");
                    $stmtUpdate->execute(['nama' => $nama_kategori, 'id' => $id]);

                    set_flash('success', 'Kategori berhasil diperbarui menjadi "' . e($nama_kategori) . '".');
                    header('Location: ' . base_url('admin/kategori/index.php'));
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Kategori - KasirKu';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php'); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/kategori/index.php'); ?>">Kategori</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Kategori</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Kategori
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off">
                        <?= csrf_field(); ?>

                        <div class="mb-4">
                            <label for="nama_kategori" class="form-label fw-semibold text-secondary">
                                Nama Kategori <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" value="<?= e($nama_kategori); ?>" required autofocus>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <a href="<?= base_url('admin/kategori/index.php'); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Perbarui Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

