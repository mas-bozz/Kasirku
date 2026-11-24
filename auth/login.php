<?php
/**
 * Halaman Login KasirKu
 */
require_once __DIR__ . '/../config/database.php';

// Jika sudah login, langsung arahkan ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ' . base_url('admin/index.php'));
    } else {
        header('Location: ' . base_url('kasir/index.php'));
    }
    exit;
}

$error = '';
$username_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF Token
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $username_val = $username;

        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi!';
        } else {
            try {
                // Gunakan prepared statement untuk keamanan SQL Injection
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'aktif') {
                        $error = 'Akun Anda sedang dinonaktifkan. Hubungi Administrator.';
                    } else {
                        // Login Berhasil - Set session
                        $_SESSION['user_id']   = $user['id_user'];
                        $_SESSION['nama']      = $user['nama'];
                        $_SESSION['username']  = $user['username'];
                        $_SESSION['role']      = $user['role'];

                        // Regenerasi session ID untuk mencegah session fixation
                        session_regenerate_id(true);

                        // Pengalihan berdasarkan role
                        if ($user['role'] === 'admin') {
                            set_flash('success', 'Selamat datang kembali, Administrator ' . e($user['nama']) . '!');
                            header('Location: ' . base_url('admin/index.php'));
                        } else {
                            set_flash('success', 'Selamat bertugas, ' . e($user['nama']) . '!');
                            header('Location: ' . base_url('kasir/index.php'));
                        }
                        exit;
                    }
                } else {
                    $error = 'Username atau password yang Anda masukkan salah.';
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KasirKu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css'); ?>">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle shadow-sm mb-2" style="width: 64px; height: 64px;">
                    <i class="bi bi-cart-check-fill fs-2"></i>
                </div>
                <h3 class="fw-bold text-dark mb-0">KasirKu</h3>
                <p class="text-muted small">Sistem Informasi Toko Kasir Modern</p>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-4">
                    <h5 class="card-title fw-semibold text-center mb-4">Masuk ke Akun Anda</h5>

                    <!-- Flash Message -->
                    <?php if ($flash): ?>
                        <div class="alert alert-<?= e($flash['type']); ?> alert-dismissible fade show small" role="alert">
                            <i class="bi bi-info-circle-fill me-1"></i> <?= e($flash['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Error Alert -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off">
                        <?= csrf_field(); ?>

                        <div class="mb-3">
                            <label for="username" class="form-label small fw-medium text-secondary">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" value="<?= e($username_val); ?>" placeholder="Masukkan username" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label small fw-medium text-secondary">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Masukkan password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm rounded-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
                        </button>
                    </form>

                    <!-- Info Akun Demo / Default -->
                    <div class="mt-4 pt-3 border-top text-center">
                        <span class="badge bg-secondary-subtle text-secondary mb-2">Akun Default Sistem</span>
                        <div class="row g-2 small text-muted text-start mt-1">
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light">
                                    <div class="fw-bold text-dark">Admin:</div>
                                    <div>user: <code>admin</code></div>
                                    <div>pass: <code>admin123</code></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light">
                                    <div class="fw-bold text-dark">Kasir:</div>
                                    <div>user: <code>kasir</code></div>
                                    <div>pass: <code>kasir123</code></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="text-center mt-4 text-muted small">
                &copy; <?= date('Y'); ?> KasirKu. PHP Native & MariaDB.
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

