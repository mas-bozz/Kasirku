<?php
/**
 * Hapus Pengguna - KasirKu (Administrator)
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID pengguna tidak valid.');
    header('Location: ' . base_url('admin/user/index.php'));
    exit;
}

if ($id == $_SESSION['user_id']) {
    set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan!');
    header('Location: ' . base_url('admin/user/index.php'));
    exit;
}

try {
    // 1. Ambil data user
    $stmt = $pdo->prepare("SELECT id_user, nama, username FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('danger', 'Data pengguna tidak ditemukan.');
        header('Location: ' . base_url('admin/user/index.php'));
        exit;
    }

    // 2. Cek apakah user memiliki riwayat transaksi penjualan
    $stmtTrx = $pdo->prepare("SELECT COUNT(*) FROM penjualans WHERE id_user = :id");
    $stmtTrx->execute(['id' => $id]);
    $totalTrx = (int)$stmtTrx->fetchColumn();

    // 3. Cek apakah user memiliki riwayat di mutasi stok
    $stmtStok = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE id_user = :id");
    $stmtStok->execute(['id' => $id]);
    $totalStok = (int)$stmtStok->fetchColumn();

    if ($totalTrx > 0 || $totalStok > 0) {
        set_flash('danger', 'Pengguna "@' . e($user['username']) . '" tidak dapat dihapus karena tercatat dalam ' . $totalTrx . ' transaksi penjualan dan ' . $totalStok . ' aktivitas mutasi stok! Demi menjaga integritas data audit, silakan nonaktifkan akunnya saja.');
        header('Location: ' . base_url('admin/user/index.php'));
        exit;
    }

    // 4. Hapus pengguna jika belum ada riwayat terkait
    $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id_user = :id");
    $stmtDelete->execute(['id' => $id]);

    set_flash('success', 'Akun pengguna "@' . e($user['username']) . '" (' . e($user['nama']) . ') berhasil dihapus.');

} catch (PDOException $e) {
    set_flash('danger', 'Gagal menghapus pengguna: ' . $e->getMessage());
}

header('Location: ' . base_url('admin/user/index.php'));
exit;

