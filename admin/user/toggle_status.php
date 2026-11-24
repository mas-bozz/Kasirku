<?php
/**
 * Ubah Status Aktif / Nonaktif Pengguna - KasirKu (Administrator)
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID pengguna tidak valid.');
    header('Location: ' . base_url('admin/user/index.php'));
    exit;
}

if ($id == $_SESSION['user_id']) {
    set_flash('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri!');
    header('Location: ' . base_url('admin/user/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_user, nama, username, status FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('danger', 'Pengguna tidak ditemukan.');
        header('Location: ' . base_url('admin/user/index.php'));
        exit;
    }

    $newStatus = ($user['status'] === 'aktif') ? 'nonaktif' : 'aktif';

    $stmtUpdate = $pdo->prepare("UPDATE users SET status = :status WHERE id_user = :id");
    $stmtUpdate->execute([
        'status' => $newStatus,
        'id'     => $id
    ]);

    set_flash('success', 'Status akun "@' . e($user['username']) . '" berhasil diubah menjadi ' . strtoupper($newStatus) . '.');

} catch (PDOException $e) {
    set_flash('danger', 'Gagal mengubah status: ' . $e->getMessage());
}

header('Location: ' . base_url('admin/user/index.php'));
exit;

