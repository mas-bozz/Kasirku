<?php
/**
 * Hapus Produk - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID produk tidak valid.');
    header('Location: ' . base_url('admin/produk/index.php'));
    exit;
}

try {
    // 1. Ambil data produk
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id_produk = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $produk = $stmt->fetch();

    if (!$produk) {
        set_flash('danger', 'Produk tidak ditemukan.');
        header('Location: ' . base_url('admin/produk/index.php'));
        exit;
    }

    // 2. Cek apakah produk sudah pernah terlibat dalam transaksi penjualan
    $stmtCheckTrx = $pdo->prepare("SELECT COUNT(*) FROM detail_penjualan WHERE id_produk = :id");
    $stmtCheckTrx->execute(['id' => $id]);
    $totalTransaksi = (int)$stmtCheckTrx->fetchColumn();

    if ($totalTransaksi > 0) {
        set_flash('danger', 'Produk "' . e($produk['nama_produk']) . '" tidak dapat dihapus karena sudah memiliki ' . $totalTransaksi . ' catatan transaksi penjualan! Untuk menonaktifkannya, cukup ubah stok menjadi 0.');
        header('Location: ' . base_url('admin/produk/index.php'));
        exit;
    }

    // 3. Hapus produk
    $stmtDelete = $pdo->prepare("DELETE FROM produk WHERE id_produk = :id");
    $stmtDelete->execute(['id' => $id]);

    // Hapus file gambar jika ada
    if (!empty($produk['gambar']) && file_exists(__DIR__ . '/../../uploads/produk/' . $produk['gambar'])) {
        @unlink(__DIR__ . '/../../uploads/produk/' . $produk['gambar']);
    }

    set_flash('success', 'Produk "' . e($produk['nama_produk']) . '" berhasil dihapus.');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal menghapus produk: ' . $e->getMessage());
}

header('Location: ' . base_url('admin/produk/index.php'));
exit;

