<?php
/**
 * Manajemen Pengguna / User - KasirKu (Administrator)
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$search = trim($_GET['q'] ?? '');
$filterRole = trim($_GET['role'] ?? '');

try {
    $sql = "
        SELECT u.*, 
               (SELECT COUNT(*) FROM penjualans p WHERE p.id_user = u.id_user) AS total_transaksi 
        FROM users u 
        WHERE 1=1
    ";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (u.nama LIKE :search OR u.username LIKE :search)";
        $params['search'] = "%$search%";
    }

    if (!empty($filterRole) && in_array($filterRole, ['admin', 'kasir'])) {
        $sql .= " AND u.role = :role";
        $params['role'] = $filterRole;
    }

    $sql .= " ORDER BY u.id_user ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $userList = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Gagal memuat data pengguna: " . htmlspecialchars($e->getMessage()));
}

$pageTitle = 'Manajemen Pengguna - KasirKu';
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
                    <li class="breadcrumb-item active" aria-current="page">Pengguna</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark">Manajemen Akun Pengguna & Kasir</h4>
            <p class="text-muted small mb-0">Kelola kredensial, role, hak akses, dan status keaktifan kasir toko.</p>
        </div>
        <div>
            <a href="<?= base_url('admin/user/tambah.php'); ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-person-plus me-1"></i> Tambah Pengguna Baru
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cari nama atau username..." value="<?= e($search); ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">-- Semua Role --</option>
                        <option value="admin" <?= $filterRole === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        <option value="kasir" <?= $filterRole === 'kasir' ? 'selected' : ''; ?>>Kasir</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($filterRole)): ?>
                        <a href="<?= base_url('admin/user/index.php'); ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data Pengguna -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th class="text-center">Role Akses</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Total Transaksi</th>
                            <th>Dibuat Pada</th>
                            <th class="text-end" style="width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($userList)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                                    Tidak ada pengguna yang sesuai dengan filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($userList as $u): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $no++; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark d-flex align-items-center">
                                            <i class="bi bi-person-circle fs-5 me-2 text-primary"></i>
                                            <?= e($u['nama']); ?>
                                            <?php if ($u['id_user'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-primary-subtle text-primary border ms-2 small">Anda</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><code>@<?= e($u['username']); ?></code></td>
                                    <td class="text-center">
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                <i class="bi bi-shield-lock me-1"></i> Administrator
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                <i class="bi bi-cash-stack me-1"></i> Kasir
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($u['status'] === 'aktif'): ?>
                                            <span class="badge bg-success px-2 py-1">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-2 py-1">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            <?= (int)$u['total_transaksi']; ?> struk
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('d/m/Y H:i', strtotime($u['created_at'])); ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Edit Profil User -->
                                            <a href="<?= base_url('admin/user/edit.php?id=' . $u['id_user']); ?>" class="btn btn-outline-primary" title="Edit Pengguna">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <!-- Reset Password -->
                                            <a href="<?= base_url('admin/user/reset_password.php?id=' . $u['id_user']); ?>" class="btn btn-outline-warning text-dark" title="Reset Password">
                                                <i class="bi bi-key"></i>
                                            </a>
                                            <!-- Toggle Aktif/Nonaktif -->
                                            <?php if ($u['id_user'] != $_SESSION['user_id']): ?>
                                                <a href="<?= base_url('admin/user/toggle_status.php?id=' . $u['id_user']); ?>" class="btn btn-outline-secondary" onclick="return confirm('Apakah Anda yakin ingin mengubah status keaktifan user \'<?= e($u['nama']); ?>\'?');" title="<?= $u['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                                    <i class="bi bi-power"></i>
                                                </a>
                                                <!-- Hapus Akun -->
                                                <a href="<?= base_url('admin/user/hapus.php?id=' . $u['id_user']); ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus akun \'<?= e($u['nama']); ?>\' secara permanen?');" title="Hapus Pengguna">
                                                    <i class="bi bi-trash"></i>
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
            Menampilkan <strong><?= count($userList); ?></strong> pengguna
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

