<?php
/**
 * Hapus Kategori - KasirKu
 */
require_once __DIR__ . '/../../includes/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID kategori tidak valid.');
    header('Location: ' . base_url('admin/kategori/index.php'));
    exit;
}

try {
    // 1. Cek keberadaan kategori
    $stmt = $pdo->prepare("SELECT * FROM kategori WHERE id_kategori = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $kategori = $stmt->fetch();

    if (!$kategori) {
        set_flash('danger', 'Data kategori tidak ditemukan.');
        header('Location: ' . base_url('admin/kategori/index.php'));
        exit;
    }

    // 2. Cek apakah kategori masih dipakai oleh produk
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE id_kategori = :id");
    $stmtCheck->execute(['id' => $id]);
    $totalProduk = (int)$stmtCheck->fetchColumn();

    if ($totalProduk > 0) {
        set_flash('danger', 'Kategori "' . e($kategori['nama_kategori']) . '" tidak dapat dihapus karena masih digunakan oleh ' . $totalProduk . ' produk!');
        header('Location: ' . base_url('admin/kategori/index.php'));
        exit;
    }

    // 3. Hapus kategori
    $stmtDelete = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = :id");
    $stmtDelete->execute(['id' => $id]);

    set_flash('success', 'Kategori "' . e($kategori['nama_kategori']) . '" berhasil dihapus.');
} catch (PDOException $e) {
    set_flash('danger', 'Gagal menghapus kategori: ' . $e->getMessage());
}

header('Location: ' . base_url('admin/kategori/index.php'));
exit;

