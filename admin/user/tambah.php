<?php
/**
 * Tambah Pengguna Baru - KasirKu (Administrator)
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$error = '';
$nama      = '';
$username  = '';
$role      = 'kasir';
$status    = 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan ulangi pengisian form.';
    } else {
        $nama       = trim($_POST['nama'] ?? '');
        $username   = strtolower(trim($_POST['username'] ?? ''));
        $password   = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';
        $role       = trim($_POST['role'] ?? 'kasir');
        $status     = trim($_POST['status'] ?? 'aktif');

        // Validasi input
        if (empty($nama)) {
            $error = 'Nama lengkap pengguna wajib diisi!';
        } elseif (empty($username)) {
            $error = 'Username wajib diisi!';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error = 'Username hanya boleh terdiri dari 3-30 karakter (huruf, angka, atau underscore)!';
        } elseif (empty($password)) {
            $error = 'Password wajib diisi!';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal terdiri dari 6 karakter!';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi password tidak cocok!';
        } elseif (!in_array($role, ['admin', 'kasir'])) {
            $error = 'Role yang dipilih tidak valid!';
        } elseif (!in_array($status, ['aktif', 'nonaktif'])) {
            $error = 'Status yang dipilih tidak valid!';
        } else {
            try {
                // Cek keunikan username
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                $stmtCheck->execute(['username' => $username]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $error = 'Username "@' . e($username) . '" sudah digunakan. Silakan gunakan username lain!';
                } else {
                    // Hash password menggunakan BCRYPT secara aman
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $stmtInsert = $pdo->prepare("
                        INSERT INTO users (nama, username, password, role, status) 
                        VALUES (:nama, :username, :password, :role, :status)
                    ");
                    $stmtInsert->execute([
                        'nama'     => $nama,
                        'username' => $username,
                        'password' => $hashedPassword,
                        'role'     => $role,
                        'status'   => $status
                    ]);

                    set_flash('success', 'Pengguna baru "@' . e($username) . '" (' . strtoupper($role) . ') berhasil didaftarkan.');
                    header('Location: ' . base_url('admin/user/index.php'));
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Tambah Pengguna Baru - KasirKu';
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
                    <li class="breadcrumb-item active" aria-current="page">Tambah Pengguna</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-person-plus text-primary me-2"></i> Tambah Pengguna Baru
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

                        <!-- Nama Lengkap -->
                        <div class="mb-3">
                            <label for="nama" class="form-label fw-semibold text-secondary">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= e($nama); ?>" placeholder="Contoh: Budi Santoso" required autofocus>
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold text-secondary">
                                Username Login <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">@</span>
                                <input type="text" class="form-control" id="username" name="username" value="<?= e($username); ?>" placeholder="Contoh: budi_kasir" required>
                            </div>
                            <div class="form-text small">Huruf kecil, angka, atau underscore. Tanpa spasi.</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Password -->
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold text-secondary">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required>
                            </div>

                            <!-- Konfirmasi Password -->
                            <div class="col-md-6">
                                <label for="konfirmasi_password" class="form-label fw-semibold text-secondary">
                                    Konfirmasi Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Role -->
                            <div class="col-md-6">
                                <label for="role" class="form-label fw-semibold text-secondary">
                                    Role Akses <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="kasir" <?= $role === 'kasir' ? 'selected' : ''; ?>>Kasir (Akses Transaksi POS)</option>
                                    <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>Administrator (Hak Akses Penuh)</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold text-secondary">
                                    Status Akun <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="aktif" <?= $status === 'aktif' ? 'selected' : ''; ?>>Aktif (Bisa Login)</option>
                                    <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif (Tidak Bisa Login)</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('admin/user/index.php'); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Daftarkan Pengguna
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

