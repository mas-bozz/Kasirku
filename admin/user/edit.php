<?php
/**
 * Edit Pengguna - KasirKu (Administrator)
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID pengguna tidak valid.');
    header('Location: ' . base_url('admin/user/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('danger', 'Data pengguna tidak ditemukan.');
        header('Location: ' . base_url('admin/user/index.php'));
        exit;
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . htmlspecialchars($e->getMessage()));
}

$isSelf = ($user['id_user'] == $_SESSION['user_id']);
$error = '';
$nama     = $user['nama'];
$username = $user['username'];
$role     = $user['role'];
$status   = $user['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan ulangi pengisian form.';
    } else {
        $nama     = trim($_POST['nama'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        
        // Jika mengedit akun sendiri, role dan status dikunci agar tidak terkunci keluar
        $role     = $isSelf ? 'admin' : trim($_POST['role'] ?? 'kasir');
        $status   = $isSelf ? 'aktif' : trim($_POST['status'] ?? 'aktif');

        if (empty($nama)) {
            $error = 'Nama lengkap wajib diisi!';
        } elseif (empty($username)) {
            $error = 'Username wajib diisi!';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error = 'Username hanya boleh terdiri dari 3-30 karakter (huruf, angka, atau underscore)!';
        } elseif (!in_array($role, ['admin', 'kasir'])) {
            $error = 'Role tidak valid!';
        } elseif (!in_array($status, ['aktif', 'nonaktif'])) {
            $error = 'Status tidak valid!';
        } else {
            try {
                // Cek duplikasi username pada user lain
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND id_user != :id");
                $stmtCheck->execute(['username' => $username, 'id' => $id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $error = 'Username "@' . e($username) . '" sudah digunakan oleh pengguna lain!';
                } else {
                    $stmtUpdate = $pdo->prepare("
                        UPDATE users SET 
                            nama = :nama, 
                            username = :username, 
                            role = :role, 
                            status = :status 
                        WHERE id_user = :id
                    ");
                    $stmtUpdate->execute([
                        'nama'     => $nama,
                        'username' => $username,
                        'role'     => $role,
                        'status'   => $status,
                        'id'       => $id
                    ]);

                    // Jika yang diedit adalah akun yang sedang login, update nama & username di sesi
                    if ($isSelf) {
                        $_SESSION['nama']     = $nama;
                        $_SESSION['username'] = $username;
                    }

                    set_flash('success', 'Data pengguna "@' . e($username) . '" berhasil diperbarui.');
                    header('Location: ' . base_url('admin/user/index.php'));
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Pengguna: ' . $user['nama'] . ' - KasirKu';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php'); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/user/index.php'); ?>">Pengguna</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Pengguna</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Data Pengguna
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($isSelf): ?>
                        <div class="alert alert-info py-2 px-3 small mb-3">
                            <i class="bi bi-info-circle me-1"></i> Anda sedang mengedit akun yang sedang Anda gunakan sendiri. Role dan status dikunci demi keamanan.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off">
                        <?= csrf_field(); ?>

                        <div class="mb-3">
                            <label for="nama" class="form-label fw-semibold text-secondary">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= e($nama); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold text-secondary">
                                Username Login <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">@</span>
                                <input type="text" class="form-control" id="username" name="username" value="<?= e($username); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="role" class="form-label fw-semibold text-secondary">Role Akses</label>
                                <select class="form-select" id="role" name="role" <?= $isSelf ? 'disabled' : ''; ?> required>
                                    <option value="kasir" <?= $role === 'kasir' ? 'selected' : ''; ?>>Kasir</option>
                                    <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                                <?php if ($isSelf): ?>
                                    <input type="hidden" name="role" value="admin">
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold text-secondary">Status Keaktifan</label>
                                <select class="form-select" id="status" name="status" <?= $isSelf ? 'disabled' : ''; ?> required>
                                    <option value="aktif" <?= $status === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                </select>
                                <?php if ($isSelf): ?>
                                    <input type="hidden" name="status" value="aktif">
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('admin/user/index.php'); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

