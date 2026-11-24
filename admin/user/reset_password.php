<?php
/**
 * Reset Password Pengguna - KasirKu (Administrator)
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID pengguna tidak valid.');
    header('Location: ' . base_url('admin/user/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_user, nama, username, role FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('danger', 'Pengguna tidak ditemukan.');
        header('Location: ' . base_url('admin/user/index.php'));
        exit;
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan ulangi form.';
    } else {
        $password   = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';

        if (empty($password)) {
            $error = 'Password baru tidak boleh kosong!';
        } elseif (strlen($password) < 6) {
            $error = 'Password baru minimal harus 6 karakter!';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi password baru tidak cocok!';
        } else {
            try {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $stmtUpdate = $pdo->prepare("UPDATE users SET password = :pass WHERE id_user = :id");
                $stmtUpdate->execute([
                    'pass' => $newHash,
                    'id'   => $id
                ]);

                set_flash('success', 'Password untuk pengguna "@' . e($user['username']) . '" berhasil direset.');
                header('Location: ' . base_url('admin/user/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal mereset password: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Reset Password: ' . $user['nama'] . ' - KasirKu';
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
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/user/index.php'); ?>">Pengguna</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Reset Password</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-key-fill text-warning text-dark me-2"></i> Reset Password Pengguna
                    </h5>
                </div>
                <div class="card-body p-4">
                    <!-- Ringkasan Akun Target -->
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="text-muted small">Target Pengguna:</div>
                        <div class="fw-bold fs-6 text-dark"><?= e($user['nama']); ?></div>
                        <div class="small text-secondary">Username: <code>@<?= e($user['username']); ?></code> &bull; Role: <?= strtoupper(e($user['role'])); ?></div>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off">
                        <?= csrf_field(); ?>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-secondary">
                                Password Baru <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label for="konfirmasi_password" class="form-label fw-semibold text-secondary">
                                Konfirmasi Password Baru <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password baru" required>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('admin/user/index.php'); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-warning px-4 text-dark fw-semibold">
                                <i class="bi bi-check-lg me-1"></i> Simpan Password Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

